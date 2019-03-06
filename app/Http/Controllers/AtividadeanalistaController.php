<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


use App\Http\Requests;

class AtividadeanalistaController extends Controller
{
    public $answerPath;
    protected $s_emp = null;

    public function __construct(Request $request = null)
    { 
        if (!Auth::guest() && !empty(session()->get('seid')))
            $this->s_emp = Empresa::findOrFail(session('seid'));
    }

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = User::findOrFail(Auth::user()->id);

    	$query = 'SELECT 
                        GROUP_CONCAT((C.name) SEPARATOR " - ") as name,
                        B.razao_social,
                        G.nome,
                        A.Regra_geral,
                        A.UF,
                        A.Emp_id,
                        A.Tributo_id
                    FROM
                        atividadeanalista A
                            INNER JOIN
                        empresas B ON A.Emp_id = B.id
                            INNER JOIN
                        users C ON A.Id_usuario_analista = C.id
                            INNER JOIN 
                        tributos G ON A.Tributo_id = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY B.razao_social, G.nome';
        $table = DB::select($query);

        $table = json_decode(json_encode($table),true);
        return view('atividadeanalista.index')->with('table', $table);
    }

    public function anyData(Request $request)
    {
    	//
    }



    public function job()
    {
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:') {
            $path = 'W:';
        }
        $path .= '/storagebravobpo/';
        $arquivos = scandir($path);

        $path_name = $path.'BK_13574594/';
        $data[1] = scandir($path_name.'/analista');
        $data[2]['path'] = $path_name.'analista/';
        $this->answerPath = $path_name.'resposta_analista/log/';

        foreach ($data[1] as $key => $file) {
            if (strlen($file) > 2) {
                $files[] = $file;
            }
        }  


        $dados = array();
        if (!empty($files)) {
            foreach ($files as $index => $singleFile) {
                $row = 1;
                $linha = array();
                if (($arquivo = fopen($data[2]['path'].$singleFile, 'r')) !== FALSE) {
                    while (($content = fgetcsv($arquivo, 1000, ",")) !== FALSE) {
                    $num = count($content);
                    $row++;
                    for ($c=0; $c < $num; $c++) {
                       $linha[$row] = $content; 
                    }
                 }
                 fclose($arquivo);
                }
                $dados[] = $this->limpaArray($linha);            
            }
        }

        if (!empty($dados)) {
            foreach ($dados as $key => $singleDado) {
                if ($this->saveFile($singleDado)) {
                    foreach ($files as $someIndex => $someFile) {
                        $origem = $data[2]['path'].$someFile;
                        $destino =str_replace('analista', 'resposta_analista', $data[2]['path'].$someFile);
                        if (is_file($origem)) {
                            copy($origem, $destino);
                            unlink($origem);
                        }
                    }
                }
            }
            
        } else {
            echo "Não foram encontrados arquivos para realizar a leitura";exit;
        }
        
        echo "Job rodado com sucesso";
    }

    private function saveFile($linhas)
    {
        $Nomearquivo = 'ERRO'.date('dmYHis').'.txt';
        $file = fopen($Nomearquivo, "a");
        $erro = 0;


        foreach ($linhas as $index => $linha) {
            if (!$this->checkData($linha)) {
                fwrite($file, "O registro com tributo : ".$linha['tributo'].", da empresa ".$linha['empresa']." do estabelecimento ".$linha['cnpj']." do usuario ".$linha['analista']." falhou no carregamento dos dados;\n \n");

                unset($linhas[$index]);
                $erro = 1;
            }
        }
        fclose($file);

        if ($erro == 0) {
            unlink($Nomearquivo);
        }

        if ($erro == 1) {
            $destino = $this->answerPath.$Nomearquivo;
            copy($Nomearquivo, $destino);
            unlink($Nomearquivo);
        }
        return $this->saveAnalista($linhas);
    }

    private function saveAnalista($dados)
    {
        foreach ($dados as $key => $linha) {
            
            $atividadeanalista = $this->loadAnalista($linha);
            
            if ($atividadeanalista) {
                $linha['id'] = $atividadeanalista;
                $this->updateAnalista($linha);
            } else {
                $this->insertAnalista($linha);
            }
        }
        return true;
    }

    private function updateAnalista($linha)
    {   
        $value = $this->formatArray($linha);
        $Atividade = AtividadeAnalista::findOrFail($linha['id']);
        $Atividade->fill($value)->save();

        $this->saveAnalistaFilial($linha);
    }

    private function insertAnalista($linha)
    {
        $value = $this->formatArray($linha);
        $Atividade = AtividadeAnalista::create($value);
        
        $linha['id'] = $Atividade->id;
        $this->saveAnalistaFilial($linha);
    }

    private function loadAnalistaFilial($array)
    {
        $find = DB::table('atividadeanalistafilial')->select('id')->where('Id_estabelecimento', $array['estabelecimento_id'])->where('Id_atividadeanalista', $array['id'])->get();   

        if (count($find) > 0) {
            return true;
        }
        return false;

    }

    private function saveAnalistaFilial($linha)
    {   
        if (!$this->loadAnalistaFilial($linha)) {
            $this->insertAnalistaFilial($linha);
        }

        return true;
    }

    private function insertAnalistaFilial($linha)
    {   
        $value = $this->formatArrayFilial($linha);
        $Atividade = AtividadeAnalistaFilial::create($value);
        return true;
    }

    private function formatArrayFilial($array){
        $return = array();

        $return['Id_estabelecimento'] = $array['estabelecimento_id'];
        $return['Id_atividadeanalista'] = $array['id'];
        
        return $return;
    }

    private function formatArray($array){
        $return = array();

        $return['Emp_id'] = $array['empresa_id'];
        $return['Tributo_id'] = $array['tributo_id'];
        $return['Id_usuario_analista'] = $array['analista_id'];
        $return['Regra_geral'] = 'N';
        
        return $return;
    }

    private function loadAnalista($campo)
    {
        $find = DB::table('atividadeanalista')->select('id')->where('Id_usuario_analista', $campo['analista_id'])->where('Tributo_id', $campo['tributo_id'])->where('Emp_id', $campo['empresa_id'])->get();   
        
        if (count($find) > 0) {
            return $find[0]->id;
        }
        return 0;
    }

    private function checkData($data)
    {
        if (empty($data['estabelecimento_id'])) {
            return false;
        }

        if (empty($data['analista_id'])) {
            return false;
        }

        if (empty($data['empresa_id'])) {
            return false;
        }

        if (empty($data['tributo_id'])) {
            return false;
        }

        return true;
    }


    private function limpaArray($array)
    {
        $formated = array();
        foreach ($array as $key => $single) {
            $a = 0;
            $superArray = explode(';', $single[0]);
            foreach ($superArray as $index => $value) {
                
                if (!empty($value)) {
                    $a++;
                }
                if ($value == 'CNPJ') {
                    $a = 0;
                }
                if (empty($value)) {
                    unset($single[$index]);
                }
            }

            if ($a > 2) {
                $formated[] = $superArray;
            }
        }

        $data = $this->loadData($formated);
        return $data;
    }


    private function loadData($array){
        $data = array();
        foreach ($array as $key => $registro) {
            $data[$key]['estabelecimento_id'] = $this->getEstabelecimento($registro[5], $registro[0]);
            $data[$key]['analista_id'] = $this->getAnalista($registro[1]);
            $data[$key]['empresa_id'] = $this->getEmpresa($registro[4]);
            $data[$key]['tributo_id'] = $this->getTributo($registro[2]);
            $data[$key]['cnpj'] = ($registro[5]);
            $data[$key]['analista'] = ($registro[1]);
            $data[$key]['empresa'] = ($registro[4]);
            $data[$key]['tributo'] = ($registro[2]);
        }

    return $data;
    }

    private function getEstabelecimento($cnpj, $codigo)
    {
        $query = 'SELECT id FROM estabelecimentos WHERE cnpj = "'.$cnpj.'" AND codigo = "'.$codigo.'"';
        $validate = DB::select($query);
        
        if (!empty($validate)) {
            return $validate[0]->id;
        }
        return false;
    }

    private function getAnalista($nome)
    {
        $query = 'SELECT id FROM users WHERE name LIKE "%'.$nome.'%"';
        $validate = DB::select($query);

        if (!empty($validate)) {
            return $validate[0]->id;
        }
        return false;
    }

    private function getEmpresa($empresa)
    {
        $query = 'SELECT id FROM empresas WHERE razao_social LIKE "%'.$empresa.'%"';
        $validate = DB::select($query);

        if (!empty($validate)) {
            return $validate[0]->id;
        }
        return false;
    }

    private function getTributo($tributo)
    {
        $query = 'SELECT id FROM tributos WHERE nome = "'.$tributo.'"';
        $validate = DB::select($query);

        if (!empty($validate)) {
            return $validate[0]->id;
        }
        return false;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    	//carregando dados da tela
        $usuarios = User::selectRaw("name, id")->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');

        return view('atividadeanalista.adicionar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $situation = 'status';
        $message = 'Registro inserido com sucesso';
        $usuarios = User::selectRaw("name, id")->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $var = array();
        $input = $request->all();
        
        if (!empty($input)) {

            $estabelecimentos = array();
            if (!empty($input['uf'])) {
                $estabelecimentos = Estabelecimento::LoadByUf($input['uf'], $input['Emp_id']);
            }

            if (empty($estabelecimentos)) {
                 return redirect()->back()->with('alert', 'Não existem estabelecimentos para essa UF + Empresa.');
            }

            if (empty($input['Id_usuario_analista'])) {
                return redirect()->back()->with('alert', 'Ao menos um usuário deve ser selecionado.');
            }

            if (empty($input['Tributo_id'])) {
                return redirect()->back()->with('alert', 'Ao menos um Tributo deve ser selecionado.');
            }

            if ($input['Regra_geral'] == 'S' && count($input['Id_usuario_analista']) > 1) {
                return redirect()->back()->with('alert', 'Não é possível ter mais de um analista responsável por todos os estabelecimentos da empresa.');
            }

            if (!empty($input['Tributo_id'])) {
                foreach ($input['Tributo_id'] as $key => $value) {
                    $var[$key]['Emp_id'] = $input['Emp_id'];
                    $var[$key]['Tributo_id'] = $value;
                    $var[$key]['Regra_geral'] = $input['Regra_geral'];
                    $var[$key]['uf'] = strtoupper($input['uf']);
                    if ($input['Regra_geral'] == 'S') {
                        $var[$key]['Id_usuario_analista'] = $input['Id_usuario_analista'][0];
                    }
                }
            }

            if ($input['Regra_geral'] == 'N') {
                $final_array = array();
                if (!empty($var) && !empty($input['Id_usuario_analista'])) {
                    foreach ($input['Id_usuario_analista'] as $index => $id_user) {
                        foreach ($var as $x => $k) {
                            $var[$x]['Id_usuario_analista'] = $id_user;
                        }

                        $final_array[] = $var;
                    }
                }

                if (count($final_array) > 1) {
                    $qtd_analistas = count($final_array);
                    $qtd_analistas_c = count($final_array);
                    $qtd_estabs = count($estabelecimentos);
                    $to_own = floor($qtd_estabs/$qtd_analistas);
                    $arr_analistas = array();
                    while ($qtd_analistas > 0) {
                        $arr_analistas[] = $to_own;
                        $qtd_analistas--;
                    }

                    while (array_sum($arr_analistas) < $qtd_estabs) {
                        $arr_analistas[0] = $arr_analistas[0]+1;
                    }
                   
                    foreach ($arr_analistas as $x => $analista_single) {
                        $estab_for_analyst[$x] = array();
                        $a = 0;
                        foreach ($estabelecimentos as $indexing => $estabelecimento) {
                            
                            $estab_for_analyst[$x][$indexing]['Id_estabelecimento'] = $estabelecimento['id'];
                            unset($estabelecimentos[$indexing]);
                            $a++;

                            if ($a == $analista_single) {
                                break;
                            }

                        }
                    }
                }
            }

            if (empty($final_array)) {
                $final_array[] = $var;
            }

            foreach ($final_array as $x => $var) {
                foreach ($var as $k => $v) {

                    if (count($final_array) == 1) {
                        $v['Regra_geral'] = 'S';
                    }

                    if (!$this->validation($v)) {
                        return redirect()->back()->with('alert', 'Já existem analista(s) cadastrado(s) para essa atividade.');
                    }  

                    $create = AtividadeAnalista::create($v);
                    if ($v['Regra_geral'] == 'N') {
                        $v['Estabelecimentos'] = $estab_for_analyst[$x];
                        foreach ($v['Estabelecimentos'] as $kk => $create_register) {
                            $create_register['Id_atividadeanalista'] = $create->id;
                            AtividadeAnalistaFilial::Create($create_register);
                        }
                    }
                }
            }
            
            return redirect()->back()->with('status', 'Analista(s) cadastrado(s)  com sucesso.');

        }

        return view('atividadeanalista.adicionar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios);
    }

    public function validation($array)
    {
        if ($array['Regra_geral'] == 'N') {
            $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->get();
        } else {
            $find = DB::table('atividadeanalista')->select('*')->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->get();            
        }

        $find = json_decode(json_encode($find),true);
        if (count($find) > 0) {
            return false;
        }

        if ($array['Regra_geral'] == 'N') {
            $find = DB::table('atividadeanalista')->select('*')->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->where('Regra_geral', 'S')->get();
        }
        
        $find = json_decode(json_encode($find),true);
        if (count($find) > 0) {
            return false;
        }

        return true;
    }

    public function validationEdit($array, $input)
    {
        $atividades = AtividadeAnalista::where('Emp_id', $input['old_empid'])->where('Tributo_id', $input['old_tributoid'])->where('Regra_geral', $input['old_regrageral'])->where('uf', $input['old_uf'])->get();

        $str = array();
        if (!empty($atividades)) {
            foreach ($atividades as $x => $k) {
                $str[] = $k->id;
            }
        }

        if ($array['Regra_geral'] == 'N') {
            $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->whereNotIn('id', $str)->get();
        } else {
            $find = DB::table('atividadeanalista')->select('*')->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->whereNotIn('id', $str)->get();            
        }

        $find = json_decode(json_encode($find),true);
        if (count($find) > 0) {
            return false;
        }

        if ($array['Regra_geral'] == 'N') {
            $find = DB::table('atividadeanalista')->select('*')->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->where('uf', $array['uf'])->where('Regra_geral', 'S')->whereNotIn('id', $str)->get();
        }
        
        $find = json_decode(json_encode($find),true);
        if (count($find) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    	//
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $situation = 'status';
        $message = 'Registro atualizado com sucesso';

        $var = array();
        $input = $request->all();

        if (!empty($input)) {

            $estabelecimentos = array();
            if (!empty($input['uf'])) {
                $estabelecimentos = Estabelecimento::LoadByUf($input['uf'], $input['Emp_id']);
            }

            if (empty($estabelecimentos)) {
                 return redirect()->back()->with('alert', 'Não existem estabelecimentos para essa UF + Empresa.');
            }

            if (empty($input['Id_usuario_analista'])) {
                return redirect()->back()->with('alert', 'Ao menos um usuário deve ser selecionado.');
            }

            if (empty($input['Tributo_id'])) {
                return redirect()->back()->with('alert', 'Ao menos um Tributo deve ser selecionado.');
            }

            if ($input['Regra_geral'] == 'S' && count($input['Id_usuario_analista']) > 1) {
                return redirect()->back()->with('alert', 'Não é possível ter mais de um analista responsável por todos os estabelecimentos da empresa.');
            }

            if (!empty($input['Tributo_id'])) {
                foreach ($input['Tributo_id'] as $key => $value) {
                    $var[$key]['Emp_id'] = $input['Emp_id'];
                    $var[$key]['Tributo_id'] = $value;
                    $var[$key]['Regra_geral'] = $input['Regra_geral'];
                    $var[$key]['uf'] = strtoupper($input['uf']);
                    if ($input['Regra_geral'] == 'S') {
                        $var[$key]['Id_usuario_analista'] = $input['Id_usuario_analista'][0];
                    }
                }
            }

            if ($input['Regra_geral'] == 'N') {
                $final_array = array();
                if (!empty($var) && !empty($input['Id_usuario_analista'])) {
                    foreach ($input['Id_usuario_analista'] as $index => $id_user) {
                        foreach ($var as $x => $k) {
                            $var[$x]['Id_usuario_analista'] = $id_user;
                        }

                        $final_array[] = $var;
                    }
                }

                if (count($final_array) > 1) {
                    $qtd_analistas = count($final_array);
                    $qtd_analistas_c = count($final_array);
                    $qtd_estabs = count($estabelecimentos);
                    $to_own = floor($qtd_estabs/$qtd_analistas);
                    $arr_analistas = array();
                    while ($qtd_analistas > 0) {
                        $arr_analistas[] = $to_own;
                        $qtd_analistas--;
                    }

                    while (array_sum($arr_analistas) < $qtd_estabs) {
                        $arr_analistas[0] = $arr_analistas[0]+1;
                    }
                   
                    foreach ($arr_analistas as $x => $analista_single) {
                        $estab_for_analyst[$x] = array();
                        $a = 0;
                        foreach ($estabelecimentos as $indexing => $estabelecimento) {
                            
                            $estab_for_analyst[$x][$indexing]['Id_estabelecimento'] = $estabelecimento['id'];
                            unset($estabelecimentos[$indexing]);
                            $a++;

                            if ($a == $analista_single) {
                                break;
                            }

                        }
                    }
                }
            }

            if (empty($final_array)) {
                $final_array[] = $var;
            }
            $a = 1;
            foreach ($final_array as $x => $var) {
                foreach ($var as $k => $v) {


                    if (count($final_array) == 1) {
                        $v['Regra_geral'] = 'S';
                    }

                    if (!$this->validationEdit($v, $input)) {
                        return redirect()->back()->with('alert', 'Já existem analista(s) cadastrado(s) para essa atividade.');
                    }  

                    if ($a) {
                        $this->clearOlder($input, $a);
                    }

                    $a = 0;

                    $create = AtividadeAnalista::create($v);
                    if ($v['Regra_geral'] == 'N') {
                        $v['Estabelecimentos'] = $estab_for_analyst[$x];
                        foreach ($v['Estabelecimentos'] as $kk => $create_register) {
                            $create_register['Id_atividadeanalista'] = $create->id;
                            AtividadeAnalistaFilial::Create($create_register);
                        }
                    }
                }
            }

            $situation = 'status';
            $message = 'Registro alterado com sucesso';
        }

        return redirect()->route('atividadesanalista.index')->with($situation, $message);
    }


    private function clearOlder($input, $delete)
    {
        $atividades = AtividadeAnalista::where('Emp_id', $input['old_empid'])->where('Tributo_id', $input['old_tributoid'])->where('Regra_geral', $input['old_regrageral'])->where('uf', $input['old_uf'])->get();

        if (!empty($atividades) && $delete) {
            foreach ($atividades as $x => $k) {
                AtividadeAnalistaFilial::where('Id_atividadeanalista', $k->id)->delete();
                AtividadeAnalista::destroy($k->id);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRLT(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            $identificador = $key; 
        }

        $params = array();
        if (!empty($identificador)) {
            $params = explode('-', $identificador);
        }

        $atividade = AtividadeAnalista::where('Emp_id', $params[0])->where('Tributo_id', $params[1])->where('Regra_geral', $params[2])->where('uf', $params[3])->get();

        $selected_users = array();
        foreach ($atividade as $x => $k) {
            $selected_users[] = $k->Id_usuario_analista;
        }

        $selected_empresa = $params[0];
        $selected_tributo = $params[1];
        $selected_regra_geral = $params[2]; 
        $selected_uf = $params[3];

        $usuarios = User::selectRaw("name, id")->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->orderby('nome', 'asc')->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->orderby('razao_social', 'asc')->lists('razao_social','id');

        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with('selected_users', $selected_users)->with('selected_uf', $selected_uf)->with('selected_regra_geral', $selected_regra_geral)->with('selected_tributo', $selected_tributo)->with('selected_empresa', $selected_empresa)->with('returning', true);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

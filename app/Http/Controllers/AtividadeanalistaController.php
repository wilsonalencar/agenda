<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Tributo;
use App\Models\Empresa;
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
                        A.id,
                        C.name,
                        B.razao_social,
                        G.nome,
                        GROUP_CONCAT((SELECT 
                                    E.codigo
                                FROM
                                    estabelecimentos E
                                WHERE
                                    E.id = D.Id_estabelecimento)
                            SEPARATOR " - ") as estabelecimento
                    FROM
                        atividadeanalista A
                            INNER JOIN
                        empresas B ON A.Emp_id = B.id
                            INNER JOIN
                        users C ON A.Id_usuario_analista = C.id
                            LEFT JOIN
                        atividadeanalistafilial D ON (D.Id_atividadeanalista = A.id)
                            INNER JOIN 
                        tributos G ON A.Tributo_id = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY C.name , B.razao_social , A.id, G.nome';

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
            foreach ($single as $index => $value) {
                if (!empty($value)) {
                    $a++;
                }
                if ($value == 'CNPJ Estabelecimento') {
                    $a = 0;
                }
                if (empty($value)) {
                    unset($single[$index]);
                }
            }
            if ($a > 2) {
                $formated[] = $single;
            }
        }
        $data = $this->loadData($formated);
        return $data;
    }


    private function loadData($array){
        $data = array();
        foreach ($array as $key => $registro) {
            $data[$key]['estabelecimento_id'] = $this->getEstabelecimento($registro[6], $registro[1]);
            $data[$key]['analista_id'] = $this->getAnalista($registro[2]);
            $data[$key]['empresa_id'] = $this->getEmpresa($registro[5]);
            $data[$key]['tributo_id'] = $this->getTributo($registro[3]);
            $data[$key]['cnpj'] = ($registro[6]);
            $data[$key]['analista'] = ($registro[2]);
            $data[$key]['empresa'] = ($registro[5]);
            $data[$key]['tributo'] = ($registro[3]);
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
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
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
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $var = array();
        $input = $request->all();
        if (!empty($input['Tributo_id'])) {
            foreach ($input['Tributo_id'] as $key => $value) {
                $var[$key]['Emp_id'] = $input['Emp_id'];
                $var[$key]['Tributo_id'] = $value;
                $var[$key]['Id_usuario_analista']= $input['Id_usuario_analista'];
                $var[$key]['Regra_geral'] = $input['Regra_geral'];
            }
        }

        if (is_array($var)) {
            foreach ($var as $k => $v) {
                if (!$this->validation($v)) {
                    return redirect()->back()->with('alert', 'Já existe esta atividade para este analista.');
                }
                $create = AtividadeAnalista::create($v);
                $dados = AtividadeAnalista::findOrFail($create->id);
            }
        }

        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.id', 'atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $create->id)
                ->get();

        $cnpjs = json_decode(json_encode($cnpjs),true);

        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
    }

    public function validation($array)
    {
        $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->get();
       
        $find = json_decode(json_encode($find),true);

        if (count($find) > 0) {
            return false;
        }

    return true;
    }

    public function validationEdit($array)
    {
        $id = explode(',', $array['id']);
        $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->whereNotIn('id', $id)->get();
       
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
        //carregando dados da tela 
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $input = $request->all();        
        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.Id_estabelecimento','atividadeanalistafilial.id' ,'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $input['id'])
                ->get();
        
        $cnpjs = json_decode(json_encode($cnpjs),true);
        $var = array();
        if (!empty($input['Tributo_id'])) {
            foreach ($input['Tributo_id'] as $x => $v) {
                $var[$x]['Emp_id'] = $input['Emp_id'];
                $var[$x]['Tributo_id'] = $v;
                $var[$x]['Id_usuario_analista'] = $input['Id_usuario_analista'];
                $var[$x]['Regra_geral'] = $input['Regra_geral'];
                $var[$x]['id'] = $input['id'];
            }
        }
        if (is_array($var) && !empty($var)) {
            foreach ($var as $key => $value) {
                if (!$this->validationEdit($value)) {
                    $situation = 'error';
                    $message = 'Já existe atividade para o analista selecionado';
                    $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($value['id'])),true);
                    return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
                }
                $Atividade = AtividadeAnalista::findOrFail($value['id']);
                $Atividade->fill($value)->save();
            }
        }
        $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['id'])),true);
    
        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRLT(Request $request)
    {
        $situation = 'status';
        $message = 'Registro carregado com sucesso';
        foreach ($request->all() as $key => $value) {
            $privateid = $key; 
        }
        //carregando dados da tela 
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.Id_estabelecimento','atividadeanalistafilial.id' ,'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $privateid)
                ->get();
        
        $cnpjs = json_decode(json_encode($cnpjs),true);

        $Atividade = AtividadeAnalista::findOrFail($privateid);
        $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($privateid)),true);
    
        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs)->with('returning', true);
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

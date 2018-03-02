<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;


class RegrasController extends Controller
{
    protected $eService;   
    public $msg;
    public $estabelecimento_id;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('regras.index');
    }

    public function anyData(Request $request)
    {
        $regras = Regra::
        with([
                'tributo' => function($q)
                {
                    $q->select('id', 'nome');
                }
            ])->
        get();

        return Datatables::of($regras)->make(true);
    }

    public function lote_consulta(Request $request)
    {
        $standing = DB::select("SELECT 
            A.id, C.razao_social, B.nome, A.regra_geral
        FROM
            regraenviolote A
                INNER JOIN
            empresas C ON A.id_empresa = C.id
                INNER JOIN
            tributos B ON A.id_tributo = B.id");
        
        $array = json_decode(json_encode($standing),true);
        return view('regras.consulta_lote')->with('array', $array);
    }

    public function envio_lote(Request $request)
    {
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        return view('regras.envio_lote')->withTributos($tributos)->withEmpresas($empresas);
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    private function validaCampos($input){
        
        if (empty($input['email_1'])) {
            $this->msg = "Favor informar o e-mail obrigatório";
            return false;
        }
        if (!empty($input['email_1']) && !$this->validaEmail($input['email_1'])) {
            return false;
        }
        if (!empty($input['email_2']) && !$this->validaEmail($input['email_2'])) {
            return false;
        }

        if (!empty($input['email_3']) && !$this->validaEmail($input['email_3'])) {
            return false;
        }

        if (!$this->validaExistencia($input)) {
            return false;
        }

        return true;
    }    

    private function validaCamposCNPJ($input){
        if (empty($input['cnpj'])) {
            $this->msg = "Favor informar o cnpj para salvar";
            return false;
        }

        if (!$this->validaCNPJ($input['cnpj'], $input['id_empresa'])) {
            return false;
        }

        return true;
    }

    private function validaEmail($email) 
    {
        $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, $email)){
        return true;
        } else {
        $this->msg = 'Email é inválido, favor verificar !!';
        return false;
        }
    }

    private function validaCNPJ($cnpj, $id_empresa) 
    {        
        $existe = DB::SELECT("SELECT id FROM estabelecimentos WHERE empresa_id = ".$id_empresa." AND cnpj = ".$cnpj."");
        if (empty($existe)) {
            $this->msg = 'Esse CNPJ não consta como estabelecimento dessa empresa.';
            return false;
        }        
        $var = json_decode(json_encode($existe),true);
        foreach ($var as $key => $value) {        
        }

        $this->estabelecimento_id = $value['id'];
        return true;
    }

    private function validaExistencia($input) 
    {        
        $dados = DB::SELECT("SELECT id FROM regraenviolote WHERE id_empresa = ".$input['select_empresas']." AND id_tributo = ".$input['select_tributos']." AND regra_geral = '".$input['regra_geral']."' AND id <> ".$input['id']."");
        if (!empty($dados)) {
            $this->msg = 'Duplicidade detectada, favor verificar os dados informados';
            return false;
        }        
        return true;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        $input = $request->all();
     
        if ($input['add_cnpj']) {
            if (!$this->cnpj_save($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
            return redirect()->back()->with('status', $this->msg);
        }

        $input['regra_geral'] = 'N';
        if ($input['label_regra']) {
            $input['regra_geral'] = 'S';
        }

        
        if (!empty($input)) {
            if (!$this->validaCampos($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
        }

        foreach ($input as $key => $value) {
            if (empty($input[$key])) {
                $input[$key] = 'NULL';
            }
        }

        //edit
        if ($input['id'] > 0) {
            $standing = "UPDATE regraenviolote SET id_empresa = ".$input['select_empresas'].", id_tributo = ".$input['select_tributos'].", email_1 = '".$input['email_1']."', regra_geral = '".$input['regra_geral']."', ";
                if ($input['email_2'] == 'NULL') {
                    $standing .= "email_2 = ".$input['email_2'].", ";
                }

                if ($input['email_2'] != 'NULL') {
                    $standing .= "email_2 = '".$input['email_2']."', ";
                }

                if ($input['email_3'] == 'NULL') {
                    $standing .= "email_3 = ".$input['email_3']."";
                }

                if ($input['email_3'] != 'NULL') {
                    $standing .= "email_3 = '".$input['email_3']."'";
                }

                $standing .= " WHERE id = ".$input['id']."";
                $insert = DB::update($standing);
                return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
        }

        //insert
        $standing = "INSERT INTO regraenviolote(id_empresa, id_tributo, email_1, regra_geral, email_2, email_3) VALUES (".$input['select_empresas'].",".$input['select_tributos'].",'".$input['email_1']."','".$input['regra_geral']."',";

        if ($input['email_2'] == 'NULL') {
            $standing .= $input['email_2'].",";
        }

        if ($input['email_2'] != 'NULL') {
            $standing .= "'".$input['email_2']."',";
        }

        if ($input['email_3'] == 'NULL') {
            $standing .= $input['email_3'].")";
        }

        if ($input['email_3'] != 'NULL') {
            $standing .= "'".$input['email_3']."')";
        }
    
        $insert = DB::insert($standing);

        //return redirect()->route('regras.store')->with('status', 'Atividade adicionada com sucesso!');
        return redirect()->back()->with('status', 'Regra adicionada com sucesso!');
    }

    private function clearCNPJ($cnpj)
    {
        $cnpj = trim($cnpj);
        $cnpj = str_replace(".", "", $cnpj);
        $cnpj = str_replace(",", "", $cnpj);
        $cnpj = str_replace("-", "", $cnpj);
        $cnpj = str_replace("/", "", $cnpj);
        return $cnpj;
    }

    public function cnpj_save($dados)
    {       
        if (!empty($dados)) {

            if (!empty($dados['cnpj'])) {
                $dados['cnpj'] = $this->clearCNPJ($dados['cnpj']);
            }

            if (!$this->validaCamposCNPJ($dados)) {
                return false;
            }
        }

        $standing = "INSERT INTO regraenviolotefilial(id_regraenviolote, id_estabelecimento) VALUES (".$dados['id'].",".$this->estabelecimento_id.")";
        
        $insert = DB::insert($standing);
        $this->msg = 'CNPJ inserido com sucesso';
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
        $regra = Regra::with('estabelecimentos')->findOrFail($id);
        $tributo = Tributo::findOrFail($regra->tributo_id);

        //Verifica data proxima entrega/as
        $datas_entrega = array();
        // Regras Especiais têm mais de um resultado
        if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

            $retval_array = $this->eService->calculaProximaDataRegrasEspeciais($regra->regra_entrega);

            foreach ($retval_array as $el) {

                $val = array('data'=>$el['data'],'desc'=>$el['desc']);
                $datas_entrega[]=$val;

            }

        } else {  // Regra standard

            $ref = $regra->ref;
            if ($municipio = Municipio::find($regra->ref)) {
                $ref = $municipio->nome.' ( '.$municipio->uf.' )';
            }
            $desc = $regra->tributo->nome.' '.$ref;

            $data = $this->eService->calculaProximaData($regra->regra_entrega);
            $val = array('data'=>$data,'desc'=>$desc);
            $datas_entrega[]=$val;

        }
        //Empresas/Estabelecimentos
        $estabs = array();
        $empresas = array();

        if ($tributo->tipo == 'F') { //Federal

            $empresas = Empresa::all();

        } else if ($tributo->tipo == 'E') { //Estadual

            $ref = $regra->ref;

            $empresas = DB::table('empresas')
                ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                ->select('empresas.id','empresas.cnpj','empresas.codigo','municipios.nome', 'municipios.uf')
                ->where('municipios.uf', $ref)
                ->get();

            $estabs = DB::table('estabelecimentos')
                ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
                ->select('estabelecimentos.id','estabelecimentos.cnpj','estabelecimentos.codigo', 'municipios.nome', 'municipios.uf')
                ->where('municipios.uf', $ref)
                ->get();



        } else { //Municipal
            $ref = $regra->ref;
            $empresas = DB::table('empresas')
                ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                ->select('empresas.id','empresas.cnpj','empresas.codigo','municipios.nome', 'municipios.uf')
                ->where('municipios.codigo', $ref)
                ->get();

            $estabs = DB::table('estabelecimentos AS est')
                ->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
                ->select('est.id','est.cnpj','est.codigo', 'mun.nome', 'mun.uf')
                ->where('mun.codigo', $ref)
                ->get();

        }
        //Verifica Municipio
        if ($municipio = Municipio::find($regra->ref)) {
            $regra->ref = $municipio->nome.' ( '.$municipio->uf.' )';
        }

        $blacklist = array();  //Lista dos estab (id) que não estão ativos para esta regra
        foreach($regra->estabelecimentos as $el) {
            $blacklist[] = $el->id;
        }
        //var_dump($estabs);
        return view('regras.show')->withRegra($regra)->withTributo($tributo)->withEntregas($datas_entrega)->withEmpresas($empresas)->withEstabs($estabs)->withBlacklist($blacklist);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $regra = Regra::findOrFail($id);
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');

        return view('regras.edit')->withRegra($regra)->with('tributos', $tributos);
    }

    public function edit_lote(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        $dados = DB::select("SELECT 
            id, id_empresa, id_tributo, email_1, email_2, email_3, regra_geral FROM regraenviolote WHERE id = ".$key."");

        $dadosfiliais = DB::select("SELECT 
            A.id, B.CNPJ, B.codigo FROM regraenviolotefilial A INNER JOIN estabelecimentos B on A.id_estabelecimento = B.id WHERE A.id_regraenviolote = ".$key."");

        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $dados = json_decode(json_encode($dados),true);
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);

        return view('regras.edit_lote')->with('dados', $dados)->with('dadosfiliais', $dadosfiliais)->withTributos($tributos)->withEmpresas($empresas);   
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
        $regra = Regra::findOrFail($id);
        $uf = DB::table('municipios')->select('uf')->distinct()->get();
        $codigos = DB::table('municipios')->select('codigo')->distinct()->get();
        $output = array_merge(array_map(function ($object) { return $object->uf; }, $uf), array_map(function ($object) { return $object->codigo; }, $codigos));
        $valid = implode(',', $output); //var_dump($valid);

        $this->validate($request, [
            'freq_entrega' => 'in:A,M',
            'ref' => 'in:MATRIZ,'.$valid,
            'regra_entrega' => array('required','valida_regra','regex:/(^["MS","QS","AS"]{2}\d["DF","DU"]{2}[+,-]{1}\d{2,4})(?!.)|(^["RE"]{2}\d{2})+$/')
        ],
         $messages = [
                'freq_entrega.in' => 'A frequência de entrega pode ser A (Anual) ou M (Mensal).',
                'ref.in' => 'A referência aceita como input MATRIZ, a UF ou um CODIGO de municipio cadastrado.',
                'regra_entrega.regex' => 'O formato da regra está errado, verificar os possivéis formatos no manual.',
                'regra_entrega.valida_regra' => 'A regra não é valida, verificar no manual.'
        ]);

        $input = $request->all();
        $input['ativo'] = (Input::has('ativo')) ? 1 : 0;
        $input['afds'] = (Input::has('afds')) ? 1 : 0;
        $regra->fill($input)->save();

        return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
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

    public function excluir(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            $insert = DB::delete('DELETE FROM regraenviolote WHERE id = '.$key.' ');
        }


        return redirect()->back()->with('status', 'Regra excluída com sucesso!');
    }

    public function excluirFilial(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            $insert = DB::delete('DELETE FROM regraenviolotefilial WHERE id = '.$key.' ');
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
    }

    /**
     * Enable or Disable the specified regra from estabelecimento.
     *
     * @param  int  $regra_id
     * @param  int  $estabelecimento_id
     * @param  bool  $enable
     * @return \Illuminate\Http\Response
     */
    public function setBlacklist($regra_id,$estabelecimento_id,$enable)
    {
        $regra = Regra::with('estabelecimentos')->findOrFail($regra_id);
        if ($enable) {
            $regra->estabelecimentos()->attach($estabelecimento_id);
        } else {
            $regra->estabelecimentos()->detach($estabelecimento_id);
        }

        return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
    }
}

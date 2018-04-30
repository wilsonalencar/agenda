<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Processosadm;
use App\Models\Observacaoprocadm;
use App\Models\Statusprocadm;
use App\Services\EntregaService;  
use App\Models\Respfinanceiro;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use DB;

class ProcessosadmsController extends Controller
{
    protected $eService;
    protected $s_emp = null;

    public function __construct()
    {
        if (!session()->get('seid')) {
            echo "Nenhuma empresa Selecionada.<br/><br/><a href='home'>VOLTAR</a>";
            exit;
        }
        
        $this->middleware('auth');
        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session('seid'));
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('processosadms.index');
    }

    public function import(Request $request = null)
    {
        return view('processosadms.import');
    }

    public function action_valid_import(Request $request)
    {
        /*
        $input = $request->all();
        
        if (empty($input['file_csv'])) {

            echo json_encode(array('success'=>false, 'mensagem'=>'Arquivo Inválido'));
            exit;
        }

        $path = Input::file('file_csv')->getRealPath();
        $f = fopen($path, 'r');
        
        if (!$f) {

            echo json_encode(array('success'=>false, 'mensagem'=>'Dados inválidos'));
            exit;
        }

        while (!feof($f)) {

            $registro = fgetcsv($f, 0, ';', '"');
            if (!empty($registro[1]) && $registro[1] == 'cnpj') {
                continue;
            } 

            if ($registro[0] == '' && empty($registro[1])) {
                continue;
            }

            if (empty($dataApuracao)) {
                $dataApuracao = $registro[0];
            }

            if ($dataApuracao != $registro[0]) {
                echo json_encode(array('success'=>true, 'dataApuracaoDiferente'=>true));
                exit;
            }

            $dataApuracao = $registro[0];
        }
        
        */
        echo json_encode(array('success'=>true, 'dataApuracaoDiferente'=>false));exit;
    }

    public function consulta_procadm(Request $request)
    {
        $fim = Input::get("periodo_fim");
        $inicio = Input::get("periodo_inicio");
        $dataBuscaIni = array();
        if (empty($fim) || empty($inicio)) {
            $timestamp = strtotime("-4 months");
            $datInicial = date('d-m-Y', $timestamp);
            $datAtual = date('d-m-Y');

            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
         
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }
        }
        if (!empty($inicio) && !empty($fim)) {
            $datInicial = date('d/'.$inicio.'');
            $datAtual = date('d/'.$fim.'');
            $datInicial = str_replace('/', '-', $datInicial);
            $datAtual = str_replace('/', '-', $datAtual);
            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
            if ($meses < 0) {
                return redirect()->back()->with('alert','Favor informar uma data Válida');
            }
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }
            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }

        }

        $dataBusca = substr($dataBusca,0,-1);   
        $datas = $dataBusca;
        $datas = substr($datas ,0,-1);
        $datas = substr($datas,1);
        $datas = explode("','",$datas);

        $Grupo_Empresa = new GrupoEmpresasController;
        $emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
        $empsArray = explode(',', $emps);
        $datas = array_unique($datas, SORT_STRING);
        
        foreach ($datas as $key => $final) {
            $standing[$final] = DB::select(" SELECT ( select count(*)from processosadms A INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id where A.periodo_apuracao in ('".$final."') and B.empresa_id in (".$emps.")) as total ,
           (select count(*)from processosadms A INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id where A.periodo_apuracao in ('".$final."') and A.status_id = 1 and B.empresa_id in (".$emps.")) as baixados,
           (select count(*)from processosadms A INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id where A.periodo_apuracao in ('".$final."') and A.status_id = 2 and B.empresa_id in (".$emps.")) as em_andamento;");
        }

    return view('processosadms.consulta')->with('standing',$standing)->with('datas', $datas)->with('dataBusca', $dataBusca);
    }
    public function rlt_detalhado(Request $request)
    {
        $fim = Input::get("periodo_fim");
        $inicio = Input::get("periodo_inicio");
        
        $dataBuscaIni = array();
        if (empty($fim) || empty($inicio)) {
            $timestamp = strtotime("-4 months");
            $datInicial = date('d-m-Y', $timestamp);
            $datAtual = date('d-m-Y');

            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
             
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }
        }
        if (!empty($inicio) && !empty($fim)) {
            $datInicial = date('d/'.$inicio.'');
            $datAtual = date('d/'.$fim.'');
            $datInicial = str_replace('/', '-', $datInicial);
            $datAtual = str_replace('/', '-', $datAtual);
            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
            if ($meses < 0) {
                return redirect()->back()->with('alert','Favor informar uma data Válida');
            }
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }

        }

        $dataBusca = substr($dataBusca,0,-1);   
        $datas = $dataBusca;
        $datas = substr($datas ,0,-1);
        $datas = substr($datas,1);
        $datas = explode("','",$datas);

        $graphs = array();

        //$request->session()->put('filter_cnpj', $input['periodo_apuracao']);
        if (!empty(Input::get("vcn")) || !empty(Input::get("vco")) || !empty(Input::get("vcp"))) {

            $request->session()->put('vcn', Input::get("vcn"));
            $request->session()->put('vco', Input::get("vco"));
            $request->session()->put('vcp', Input::get("vcp"));
        }

        if (!empty(Input::get("clear"))) {

            Session::forget('vcn');
            Session::forget('vcp');
            Session::forget('vco');
        }

        if (!sizeof(Input::get())) {

            $data = $request->session()->all();
            if (!empty($data['vcn']) || !empty($data['vco']) || !empty($data['vcp'])) {
                Input::merge(array('vcn' => $data['vcn']));
                Input::merge(array('vco' => $data['vco']));
                Input::merge(array('vcp' => $data['vcp']));
            } 
        }

        $Grupo_Empresa = new GrupoEmpresasController;
        $emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
        $where = 'b.empresa_id in ('.$emps.') AND a.periodo_apuracao in ('.$dataBusca.')';

        $graphs = DB::select('select c.uf,
                                      SUM(if(status_id = 1, 1, 0)) as Baixada,
                                    SUM(if(status_id = 2, 1, 0)) as Andamento,
                                    COUNT(*) as total
                                      FROM processosadms a
                                      inner join estabelecimentos b on a.estabelecimento_id = b.id
                                      inner join municipios c on b.cod_municipio = c.codigo
                                      WHERE '.$where.'                              
                                      group by c.uf');

        return view('processosadms.graph')   
            ->with('filter_cnpj',Input::get("vcn"))
            ->with('filter_area',Input::get("vco"))
            ->with('filter_periodo',Input::get("vcp"))
            ->with('graphs', $graphs)->with('periodo_inicio', $inicio)->with('periodo_fim', $fim);

    }
    
    public function rlt_processos(Request $request)
    {
        $dataBuscaIni = array();
        if (empty($dataBuscaIni)) {
            $timestamp = strtotime("-4 months");
            $datInicial = date('d-m-Y', $timestamp);
            $datAtual = date('d-m-Y');

            list($dia, $mes, $ano) = explode( "-",$datInicial);

            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
         
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }
            $dataBusca = substr($dataBusca,0,-1);   
        }

        $Grupo_Empresa = new GrupoEmpresasController;
        $emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);

        $rpt = DB::Select("SELECT 
                            A.id,
                            A.periodo_apuracao,
                            B.cnpj,
                            C.uf,
                            A.nro_processo,
                            D.descricao as resp_financeiro,
                            A.resp_acompanhamento,
                            E.descricao
                        FROM
                            processosadms A
                                INNER JOIN
                            estabelecimentos B ON A.estabelecimento_id = B.id
                                INNER JOIN
                            municipios C ON B.cod_municipio = C.codigo
                                INNER JOIN
                            respfinanceiros D ON A.resp_financeiro_id = D.id
                                INNER JOIN
                            statusprocadms E ON A.Status_ID = E.id
                        WHERE 
                            A.periodo_apuracao in (".$dataBusca.") 
                        AND 
                            B.empresa_id in (".$emps.")");
        
        return Datatables::of($rpt)->make(true);
    }

    public function action_import(Request $request)
    {        
        $input = $request->all();
        if (empty($input['file_csv'])) {

            Session::flash('alert', 'Informar arquivo CSV para realizar importação');
            return redirect()->route('processosadms.import');
        }

        $path = Input::file('file_csv')->getRealPath();
        $f = fopen($path, 'r');
        
        if (!$f) {

            Session::flash('alert', 'Arquivo inválido para operação');
            return redirect()->route('processosadms.import');
        }
        
        DB::beginTransaction();
        $periodoApuracaoDiferente = false;

        $i = 1;
        while (!feof($f)) { 
            $registro = fgetcsv($f, 0, ';', '"');

            if (!empty($registro[1]) && $registro[1] == 'cnpj') {
                continue;
            }   

            if ($registro[0] == '' && empty($registro[1])) {
                continue;
            }

            $registro[1] = preg_replace("/[^0-9]/","",$registro[1]); 
            $estabelecimento = Estabelecimento::where('cnpj', '=', $registro[1])->where('empresa_id', $this->s_emp->id)->first();

            //busca estabelecimento
            if (!$estabelecimento) {
                DB::rollBack();
                Session::flash('alert', 'CNPJ inválido - Linha - '.$i);
                return redirect()->back()->with('processosadms.import');
            }

            //valida periodo de apuracao
            $value = explode('/', $registro[0]);
            if ((empty($value[0]) || empty($value[1])) || (!is_numeric($value[0]) || !is_numeric($value[1])) ) {

                DB::rollBack();
                Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
                return redirect()->back()->with('processosadms.import');
            }

            if (!checkdate($value[0], '01', $value[1])) {

                DB::rollBack();
                Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
                return redirect()->back()->with('processosadms.import');
            }   
            
            
            $responsavel_financeiro = str_replace(" ", "", $registro[3]);
            if ($responsavel_financeiro == 'FORNECEDOR') {
                $resp_id = 1;
            }

            else if ($responsavel_financeiro == 'CLIENTE') {
                $resp_id = 2;
            }
            else {
                DB::rollBack();
                Session::flash('alert', 'Responsável financeiro inválido - '.$i);
                return redirect()->back()->with('processosadms.import');
            }

            $status = str_replace(" ", "", $registro[6]);
            if ($status == 'EMANDAMENTO') {
                $status_id = 2;
            }

            else if ($status == 'BAIXADO') {
                $status_id = 1;
            }

            else {
                DB::rollBack();
                Session::flash('alert', 'Status inválido - '.$i);
                return redirect()->back()->with('processosadms.import');
            }

            //populando array para insert
            $array['periodo_apuracao']      = $registro[0];
            $array['estabelecimento_id']    = $estabelecimento->id;
            $array['nro_processo']          = $registro[2];
            $array['resp_financeiro_id']    = $resp_id;
            $array['resp_acompanhamento']   = $registro[4];
            $array['status_id']             = $status_id;
            $array['usuario_last_update']   = Auth::user()->email;
            
            $create = Processosadm::create($array);
            if (!$create) {
                DB::rollBack();
                Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - '.$i);
                return redirect()->route('processosadms.create');
            }

            if (!$registro[5]) {
                DB::rollBack();
                Session::flash('alert', 'Informar observação');
                return redirect()->route('processosadms.create - '.$i);
            }

            $input['processoadm_id'] = $create->id;
            $input['descricao']      = $registro[5];
            $input['usuario_update'] = Auth::user()->email;

            $createObs = Observacaoprocadm::create($input);
            if (!$createObs) {
                DB::rollBack();
                Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação - '.$i);
                return redirect()->route('processosadms.create');
            }

            $i++;
        }

        DB::commit();
        return redirect()->back()->with('status', 'Importação realizada com sucesso!');

    }

    public function anyData(Request $request)
    {   
        $processosadms = Processosadm::join('estabelecimentos', 'processosadms.estabelecimento_id', '=', 'estabelecimentos.id')->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')->select(
                'processosadms.*',
                'processosadms.id as IdProcessosAdms',
                'estabelecimentos.insc_estadual', 
                'estabelecimentos.cnpj',
                'estabelecimentos.codigo',
                'municipios.uf',
                'municipios.nome',
                DB::raw('(select GROUP_CONCAT("Observação: ", descricao SEPARATOR " - ") FROM observacaoprocadms where processoadm_id = processosadms.id) as observacoesGroupConcat')
            )
            ->with('estabelecimentos')
            ->with('estabelecimentos.municipio')
            ->with('statusprocadm')
            ->with('respfinanceiro')
            ->with('observacoes');

        if ($filter_cnpj = $request->get('cnpj')){
            $cnpj = preg_replace("/[^0-9]/","",$filter_cnpj);
            $estabelecimento = Estabelecimento::select('id')->where('cnpj', $cnpj)->get();
            if (sizeof($estabelecimento) > 0) {
                $processosadms = $processosadms->where('estabelecimento_id', $estabelecimento[0]->id);
            }else {
                $processosadms = new Collection();
            }
        }

        if ($filter_area = $request->get('area')){
            
            $estabelecimento = Estabelecimento::select('id')->where('codigo', $filter_area)->get();
            if (sizeof($estabelecimento) > 0) {
                $processosadms = $processosadms->where('estabelecimento_id', $estabelecimento[0]->id);
            }else {
                $processosadms = new Collection();
            }
        }

        if ($filter_periodo = $request->get('periodo')){
            $processosadms = $processosadms->where('periodo_apuracao', $filter_periodo);
        }

        $array = array();
        $estabelecimentos = Estabelecimento::select('id')->where('empresa_id', $this->s_emp->id)->get();
        foreach($estabelecimentos as $row) {
            $array[] = $row->id;
        }
        
        $processosadms = $processosadms->whereIn('estabelecimento_id', $array);
        
        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }
        
        return Datatables::of($processosadms)->make(true);
    }

    public function anyDataRLT(Request $request)
    {   

        //e com esses( que tinha funcionado na outra página )
        $fim = Input::get("periodo_fim");
        $inicio = Input::get("periodo_inicio");

        $dataBuscaIni = array();
        if (empty($fim) || empty($inicio)) {
            $timestamp = strtotime("-4 months");
            $datInicial = date('d-m-Y', $timestamp);
            $datAtual = date('d-m-Y');

            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
             
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }
        }
        if (!empty($inicio) && !empty($fim)) {
            $datInicial = date('d/'.$inicio.'');
            $datAtual = date('d/'.$fim.'');
            $datInicial = str_replace('/', '-', $datInicial);
            $datAtual = str_replace('/', '-', $datAtual);
            list($dia, $mes, $ano) = explode( "-",$datInicial);
            $datInicial = getdate(strtotime($datInicial));
            $datAtual = getdate(strtotime($datAtual));
            $dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
            $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
            if ($meses < 0) {
                return redirect()->back()->with('alert','Favor informar uma data Válida');
            }
            for($x = 0; $x < $meses; $x++){
                $datas[] =  date("m/Y",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
            }

            $dataBusca = '';
            foreach ($datas as $key => $value) {
                $dataBusca .= "'".$value."',";
            }

        }

        $dataBusca = substr($dataBusca,0,-1);   
        $datas = $dataBusca;
        $datas = substr($datas ,0,-1);
        $datas = substr($datas,1);
        $datas = explode("','",$datas);

        $processosadms = Processosadm::join('estabelecimentos', 'processosadms.estabelecimento_id', '=', 'estabelecimentos.id')->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')->select(
                'processosadms.*',
                'processosadms.id as IdProcessosAdms',
                'estabelecimentos.insc_estadual', 
                'estabelecimentos.cnpj',
                'estabelecimentos.codigo',
                'municipios.uf',
                'municipios.nome',
                DB::raw('(select GROUP_CONCAT("Observação: ", descricao SEPARATOR " - ") FROM observacaoprocadms where processoadm_id = processosadms.id) as observacoesGroupConcat')
            )
            ->with('estabelecimentos')
            ->with('estabelecimentos.municipio')
            ->with('statusprocadm')
            ->with('respfinanceiro')
            ->with('observacoes');

        $processosadms = $processosadms->whereIn('periodo_apuracao', $datas);
        $array = array();
        
        $Grupo_Empresa = new GrupoEmpresasController;
        $emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
        $empsArray = explode(',', $emps);

        $estabelecimentos = Estabelecimento::select('id')->whereIn('empresa_id', $empsArray)->get();
        foreach($estabelecimentos as $row) {
            $array[] = $row->id;
        }
        
        $processosadms = $processosadms->whereIn('estabelecimento_id', $array);
        
        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }
        
        return Datatables::of($processosadms)->make(true);
    }

    public function searchObservacao()
    {
        $processosadms = Processosadm::findOrFail($_GET['processosadm_id']);
        $observacoes = $processosadms->observacoes()->get();

        if (count($observacoes) > 0) {

            $i = 0;
            foreach($observacoes as $observacao) {
                $usuario = User::where('email', '=', $observacao['usuario_update'])->first();
                if (!$usuario) {
                    echo json_encode(array('success'=>false, 'data'=>array('observacoes'=>array())));
                    exit;
                }

                $observacoes[$i]['nome'] = $usuario['name'];
                $observacoes[$i]['data'] = date('d/m/Y H:i:s', strtotime($observacoes[$i]['updated_at']));
                $i++;
            }
        }

        echo json_encode(array('success'=>true, 'data'=>array('observacoes'=>$observacoes)));
        exit;
    }

    public function search(Request $request = null)
    {
        $graphs = array();
        
        $where = ' 1 = 1 ';

        //$request->session()->put('filter_cnpj', $input['periodo_apuracao']);
        if (!empty(Input::get("vcn")) || !empty(Input::get("vco")) || !empty(Input::get("vcp"))) {

            $request->session()->put('vcn', Input::get("vcn"));
            $request->session()->put('vco', Input::get("vco"));
            $request->session()->put('vcp', Input::get("vcp"));
        }

        if (!empty(Input::get("clear"))) {

            Session::forget('vcn');
            Session::forget('vcp');
            Session::forget('vco');
        }

        if (!sizeof(Input::get())) {

            $data = $request->session()->all();
            if (!empty($data['vcn']) || !empty($data['vco']) || !empty($data['vcp'])) {
                Input::merge(array('vcn' => $data['vcn']));
                Input::merge(array('vco' => $data['vco']));
                Input::merge(array('vcp' => $data['vcp']));
            } 
        }

        
        if (!empty(Input::get("vcn"))) {
            $cnpj = preg_replace("/[^0-9]/","", Input::get("vcn"));
            $where .= ' AND b.cnpj = '.$cnpj.''; 
        }

        if (!empty(Input::get("vco"))) {
            $codigo = Input::get("vco");
            $where .= ' AND b.codigo = "'.$codigo.'"'; 
        }

        if (!empty(Input::get("vcp"))) {
            $periodo = Input::get("vcp");
            $where .= ' AND a.periodo_apuracao = "'.$periodo.'"'; 
        }

        $where .= ' AND b.empresa_id = '.$this->s_emp->id;

        $graphs = DB::select('select c.uf,
                                      SUM(if(status_id = 1, 1, 0)) as Baixada,
                                    SUM(if(status_id = 2, 1, 0)) as Andamento,
                                    COUNT(*) as total
                                      FROM processosadms a
                                      inner join estabelecimentos b on a.estabelecimento_id = b.id
                                      inner join municipios c on b.cod_municipio = c.codigo
                                      WHERE '.$where.'                              
                                      group by c.uf');
        
        

        return view('processosadms.search')
            ->with('filter_cnpj',Input::get("vcn"))
            ->with('filter_area',Input::get("vco"))
            ->with('filter_periodo',Input::get("vcp"))
            ->with('graphs', $graphs);
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request = null)
    {
        $respFinanceiro = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');
        $status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
        
        $data = $request->session()->all();
        $periodo_apuracao_processos = '';
        if (!empty($data['periodo_apuracao_processos'])) {
            $periodo_apuracao_processos = $data['periodo_apuracao_processos'];
            Session::forget('periodo_apuracao_processos');
        }

       return view('processosadms.create')
       ->with('periodo_apuracao_processos', $periodo_apuracao_processos)
       ->with('respFinanceiro', $respFinanceiro)
       ->with('status', $status);
       
    }


    public function edit($id)
    {        
        $respFinanceiro = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');
        $status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');

        $processosadms = Processosadm::findOrFail($id);
        $observacoes = $processosadms->observacoes()->get();

        if (count($observacoes) > 0) {

            $i = 0;
            foreach($observacoes as $observacao) {
                $usuario = User::where('email', '=', $observacao['usuario_update'])->first();
                if (!$usuario) {
                    echo json_encode(array('success'=>false, 'data'=>array('observacoes'=>array())));
                    exit;
                }

                $observacoes[$i]['nome'] = $usuario['name'];
                $observacoes[$i]['data'] = date('d/m/Y H:i:s', strtotime($observacoes[$i]['updated_at']));
                $i++;
            }
        }

        return view('processosadms.edit')
        ->withProcessosadms($processosadms)
        ->with('observacoes', $observacoes)
        ->with('respFinanceiro', $respFinanceiro)
        ->with('status', $status);
    }


    public function update(Request $request, $id)
    {   
        $processosadms = Processosadm::findOrFail($id);

        $input = $request->all();
        $this->validate($request, [
            'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
            'estabelecimento_id' => 'required',
            'nro_processo' => 'required',
            'resp_financeiro_id' => 'required',
            'resp_acompanhamento' => 'required',
            'status_id' => 'required'
        ],
        $messages = [
            'periodo_apuracao.required' => 'Informar um periodo de apuração',
            'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
            'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
            'nro_processo.required' => 'Informar Nro do processo.',
            'resp_financeiro_id.required' => 'Informar Responsavel Financeiro.',
            'resp_acompanhamento.required' => 'Informar Responsavel Acompanhamento.',
            'status_id.required' => 'Informar Status.'
        ]);

        DB::beginTransaction();
        $input['usuario_last_update'] = Auth::user()->email;

        if (!$processosadms->fill($input)->save()) {
            DB::rollBack();
            Session::flash('alert', 'Ocorreu um erro ao editar processo administrativo');
            return redirect()->route('processosadms.edit', $id);
        }

        if (!empty($input['Observacao'])) {

            $input['processoadm_id'] = $id;
            $input['descricao']      = $input['Observacao'];
            $input['usuario_update'] = Auth::user()->email;

            $createObs = Observacaoprocadm::create($input);
            if (!$createObs) {
                DB::rollBack();
                Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação');
                return redirect()->route('processosadms.create');
            }
        }   

        DB::commit();
        $processosadms->fill($input)->save();
        return redirect()->back()->with('status', 'Processo Administrativo atualizada com sucesso!');
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
        $this->validate($request, [
            'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
            'estabelecimento_id' => 'required',
            'nro_processo' => 'required',
            'resp_financeiro_id' => 'required',
            'resp_acompanhamento' => 'required',
            'status_id' => 'required',
            'Observacao' => 'required'
        ],
        $messages = [
            'periodo_apuracao.required' => 'Informar um periodo de apuração',
            'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
            'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
            'nro_processo.required' => 'Informar Nro do processo.',
            'resp_financeiro_id.required' => 'Informar Responsavel Financeiro.',
            'resp_acompanhamento.required' => 'Informar Responsavel Acompanhamento.',
            'status_id.required' => 'Informar Status.',
            'Observacao.required' => 'Informar Observação.'
        ]);

        DB::beginTransaction();
        $input['usuario_last_update'] = Auth::user()->email;

        $create = Processosadm::create($input);
        if (!$create) {
            DB::rollBack();
            Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo');
            return redirect()->route('processosadms.create');
        }

        if (!$input['Observacao']) {
            DB::rollBack();
            Session::flash('alert', 'Informar observação');
            return redirect()->route('processosadms.create');
        }

        $input['processoadm_id'] = $create->id;
        $input['descricao']      = $input['Observacao'];
        $input['usuario_update'] = Auth::user()->email;

        $createObs = Observacaoprocadm::create($input);
        if (!$createObs) {
            DB::rollBack();
            Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação');
            return redirect()->route('processosadms.create');
        }

        DB::commit();

        $request->session()->put('periodo_apuracao_processos', $input['periodo_apuracao']);
        return redirect()->back()->with('status', 'Registro adicionada com sucesso!');
    }

    public function delete($id)
    {   
        
        if (!$id) {
            return redirect()->route('processosadms.search')->with('error', 'Informar processo administrativo para excluir');
        }

        Processosadm::destroy($id);
        return redirect()->route('processosadms.search')->with('status', 'Registro excluido com sucesso!');
    }
}

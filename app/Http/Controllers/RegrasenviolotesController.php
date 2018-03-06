<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regraenviolote;
use App\Models\Estabelecimento;
use App\Models\Regraenviolotefilial;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;


class RegrasenviolotesController extends Controller
{
    protected $eService;   
    public $msg;
    public $estabelecimento_id;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }

    public function lote_consulta(Request $request)
    {

        $envio_manual = false;
        $data_envio = '';
        $regra_geral = Regraenviolote::all("id","regra_geral");
        $parametro_regra_geral = json_decode(json_encode($regra_geral),true);
        $this->findRegrasenviolote($parametro_regra_geral, $envio_manual, $data_envio);

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

    public function Job($envio_manual=false, $data_envio = '', $id= '')
    {
        $regra_geral = Regraenviolote::all("id","regra_geral");

        if ($envio_manual) {
           $regra_geral = Regraenviolote::select("id","regra_geral")->where("id", $id)->get();    
        }

        $parametro_regra_geral = json_decode(json_encode($regra_geral),true);
        $this->findRegrasenviolote($parametro_regra_geral, $envio_manual, $data_envio);
    }

    public function findRegrasenviolote($param, $envio_manual=false, $data_envio = '')
    {

        //montando array mestre
        foreach ($param as $key => $value) {
            $value['dadosRegra'] = Regraenviolote::findOrFail($value['id']);
            $value['dadosRegra']['Matriz'] = Empresa::select('id', 'cnpj')->where('id', $value['dadosRegra']['id_empresa'])->get();

            if ($value['regra_geral'] == 'S') {
            $value = json_decode(json_encode($value), true);
                $dados = $this->getEstabelecimentos($value['dadosRegra']['Matriz'][0]['id']);
                foreach ($dados as $id => $date) {  
                    $value['dadosRegra']['dadosFiliais'][$date['id']] = $date;            
                }
            }

            if ($value['regra_geral'] == 'N') {
                $dadosfiliais = $value['dadosRegra']->filiais;
                $dadosfiliais = json_decode(json_encode($dadosfiliais),true);
                $value['dadosRegra']['dadosFiliais'] = $dadosfiliais;

                $value = json_decode(json_encode($value),true);
                foreach ($value['dadosRegra']['dadosFiliais'] as $key => $date) {
                    $dadosfiliais = Estabelecimento::select('cnpj', 'id')->where('id', $date['id_estabelecimento'])->get();
                    $dadosfiliais = json_decode(json_encode($dadosfiliais),true);
                    unset($value['dadosRegra']['dadosFiliais'][0]);
                    foreach ($dadosfiliais as $key => $id) {
                        $value['dadosRegra']['dadosFiliais'][$id['id']] = $id;                        
                    }   
                } 
            }

            //Pegando os caminhos dos arquivos
            $value = json_decode(json_encode($value),true);
            if (!empty($value['dadosRegra']['dadosFiliais'])){
                echo "<pre>";
                print_r($_SERVER);
                exit;
                foreach ($value['dadosRegra']['dadosFiliais'] as $key => $cnpjFilial) {
                    $path = "".$_SERVER['DOCUMENT_ROOT']."/uploads/".substr($value['dadosRegra']['Matriz'][0]['cnpj'], 0, 8)."/".$cnpjFilial['cnpj']."";
                    
                    
                    if (file_exists($path)) {

                        //Carrega Ultimo periodo_apuracao
                        $ult_periodo_apuracao = $this->getLastPeriodoApuracao($value['dadosRegra']['id_empresa']);
                        
                        //Carrega parametros (estadual, municipal, federal) e Pasta
                        $parametros = DB::select("SELECT B.pasta_arquivos, B.tipo FROM empresa_tributo A INNER JOIN tributos B on A.tributo_id = B.id WHERE A.empresa_id = ".$value['dadosRegra']['id_empresa']." AND B.pasta_arquivos IS NOT NULL");

                        $parametros = json_decode(json_encode($parametros), true);
                        
                        //Define Path
                        foreach ($parametros as $q => $l) {
                            $l['tipo'] = $this->getTipo($l['tipo']);
                            $link[] = $path.'/'.$l['tipo'].'/'.$l['pasta_arquivos'].'/'.$ult_periodo_apuracao.'/';
                        }

                        //Define no array o caminho da pasta
                        $value['dadosRegra']['dadosFiliais'][$key]['path'] = $link;
                    
                        //Verificando se arquivo existe e se data é igual agora a hoje.
                        foreach ($value['dadosRegra']['dadosFiliais'][$key]['path'] as $chave => $path) {
                            if (file_exists($path)) {
                                $anexo = scandir($path);
                                $item = '';
                                if(count($anexo) > 2) {
                                    $ponteiro  = opendir($path);
                                    while ($nome_itens = readdir($ponteiro)) {
                                        $itens[] = $nome_itens;
                                        foreach ($itens as $i => $q) {
                                            if (preg_match("/[A-Za-z0-9]/", $q)) {
                                                $item = $q;
                                            }
                                        }
                                    }

                                    $data_m = date('d/m/Y', filemtime($path.$item));
                                    $data_n = date('d/m/Y');
                                    if ((!$envio_manual && $data_m == $data_n) || ($envio_manual && $data_m == $data_envio)) {
                                        if (empty($path) && empty($item)) {
                                            $value['dadosRegra']['dadosFiliais'][$key]['download_link'][] = '';
                                        } 
                                        $value['dadosRegra']['dadosFiliais'][$key]['download_link'][] = $path.$item;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //Fim dos caminhos
            foreach ($value['dadosRegra']['dadosFiliais'] as $array) {
                if (!empty($array['download_link'])) {
                    $this->enviarEmailLote($array, $value['dadosRegra']['email_1'], $value['dadosRegra']['email_2'], $value['dadosRegra']['email_3']);
                }
            }
        }
    }

    private function getLastPeriodoApuracao($id_empresa)
    {
        $periodo = DB::select("SELECT periodo_apuracao FROM crons where emp_id = ".$id_empresa." ORDER BY id DESC LIMIT 1");
        $periodo = json_decode(json_encode($periodo),true);
        return $periodo[0]['periodo_apuracao'];
    }

    public function getEstabelecimentos($id_empresa)
    {
        $value = Estabelecimento::select('id', 'cnpj')->where('empresa_id', $id_empresa)->get(); 
        $value = json_decode(json_encode($value),true);
        return $value; 
    }

    public function enviarEmailLote($array, $email_1, $email_2, $email_3)
    {
        $dados = array('dados' => $array, 'emails' => array($email_1, $email_2, $email_3));
        $data['linkDownload'] = $dados['dados']['download_link'];

        $subject = "TAX CALENDAR - Entrega das obrigações em ".date('d/m/Y').".";
        $data['subject']      = $subject;
        $data['data']         = date('d/m/Y');
        foreach($dados['emails'] as $user)
        {
            $this->eService->sendMail($user, $data, 'emails.notification-envio-lote', true);
        }
        return;
    }
    public function envio_lote(Request $request)
    {
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        return view('regras.envio_lote')->withTributos($tributos)->withEmpresas($empresas);
    }

    private function validaCampos($input){

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

    private function getCNPJ($input){

        if (!$this->carregaCNPJ($input['cnpj'], $input['id_empresa'])) {
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

    private function carregaCNPJ($cnpj, $id_empresa) 
    {        
        $cnpj = $this->clearCNPJ($cnpj);
        $existe = DB::SELECT("SELECT id FROM estabelecimentos WHERE empresa_id = ".$id_empresa." AND cnpj = ".$cnpj."");
        $var = json_decode(json_encode($existe),true);
        if (empty($var)) {
            $this->msg = 'CNPJ não consta para essa empresa';
            return false;
        }
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

    private function getTipo($tipo)
    {
        if ($tipo == 'E') {
            return 'ESTADUAIS';
        }

        if($tipo == 'M'){
            return 'MUNICIPAIS';
        }

        if ($tipo == 'F') {
            return 'FEDERAIS';
        }
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
        
        if ($input['envio_manual']) {
            if (empty($input['data_envio'])) {
                return redirect()->back()->with('alert', 'A data é obrigatória para busca.');
            }
            $timestamp = strtotime($input['data_envio']);
            $input['data_envio'] = date("d/m/Y", $timestamp);
            $this->Job($input['envio_manual'], $input['data_envio'], $input['id']);
            return redirect()->back()->with('status', 'Envio manual efetuado com sucesso.');
        }

        //se estiver adicionando CNPJ
        if ($input['add_cnpj']) {
            $this->validate($request, [
            'cnpj' => 'required'
            ],
            $messages = [
                'cnpj.required' => 'Informar o CNPJ desejado.'

            ]);

            if (!$this->getCNPJ($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
                
            $value['id_regraenviolote'] = $input['id'];
            $value['id_estabelecimento'] = $this->estabelecimento_id;
            Regraenviolotefilial::create($value);
            return redirect()->back()->with('status', 'Filial adicionada com sucesso.');
        }

        //se não continua
        $this->validate($request, [
            'email_1' => 'required'
        ],
        $messages = [
            'email_1.required' => 'Informar o email obrigatório.'

        ]);
        
        $input['regra_geral'] = 'N';
        if ($input['label_regra']) {
            $input['regra_geral'] = 'S';
        }

        if (!empty($input)) {
            if (!$this->validaCampos($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
        }

        //edit
        if ($input['id'] > 0) {
            $Regraenviolote = Regraenviolote::findOrFail($input['id']);
            $Regraenviolote->fill($input)->save();
            return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
        }
        
        $value['id_empresa'] = $input['select_empresas'];
        $value['id_tributo'] = $input['select_tributos'];
        $value['email_1'] = $input['email_1'];
        $value['email_2'] = $input['email_2'];
        $value['email_3'] = $input['email_3'];
        $value['regra_geral'] = $input['regra_geral'];

        //se Não, ele cria
        Regraenviolote::create($value);
        return redirect()->route('regraslotes.edit_lote', Regraenviolote::create($value)->id)->with('status', 'Regra adicionada com sucesso!');
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

    public function edit_lote(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        $dados = Regraenviolote::findOrFail($key);
        $dadosfiliais = $dados->filiais;
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);

        foreach ($dadosfiliais as $key => $value) {
            $dadosfiliais[$key]['dadosFilial'] = Estabelecimento::select('cnpj', 'codigo')->where('id', $value['id_estabelecimento'])->get();
        }
        
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);    
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $dados = json_decode(json_encode($dados),true);

        return view('regras.edit_lote')->with('dados', $dados)->with('dadosfiliais', $dadosfiliais)->withTributos($tributos)->withEmpresas($empresas);   
    }

    private function checkFiliais($id_regra)
    {
        $dados = DB::SELECT("SELECT id FROM regraenviolotefilial WHERE id_regraenviolote = ".$id_regra."");
        if (!empty($dados)) {
            return false;
        }        
        return true;
    }

    public function excluir(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!$this->checkFiliais($key)) {
            return redirect()->back()->with('alert', 'Para excluir esse registro, você terá que excluir os registros internos (Filiais cadastradas)!');
        }

        if (!empty($key)) {
            Regraenviolote::destroy($key);
        }

        return redirect()->back()->with('status', 'Regra excluída com sucesso!');
    }

    public function excluirFilial(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            Regraenviolotefilial::destroy($key);
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
    }
}

<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\Guiaicms;
use App\Models\CriticasLeitor;
use App\Models\CriticasEntrega;
use App\Models\Atividade;
use App\Models\EntregaExtensao;
use App\Models\User;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;



class GuiaicmsController extends Controller
{
    protected $eService;   
    public $msg;
    public $estabelecimento_id;

    function __construct(EntregaService $service)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->eService = $service;
        if (!Auth::guest() && !empty(session()->get('seid')))
        $this->s_emp = Empresa::findOrFail(session('seid'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function AnyData(Request $request)
    {   
        $status = 'success';

        $src_inicio = $request->get('src_inicio');
        $src_fim = $request->get('src_fim');
        
        $Registros = Guiaicms::where('ID', '>', '0');
        
        if ((!empty($src_inicio) && !empty($src_fim)) || (!empty(Session::get('src_inicio')) && !empty(Session::get('src_fim')))) {
            
            if (!empty($src_inicio) && !empty($src_fim)) {
                Session::put('src_inicio', $src_inicio);
                Session::put('src_fim', $src_fim);
            }
            
            $Registros = $Registros->whereBetween('DATA', [Session::get('src_inicio').' 00:00:00', Session::get('src_fim').' 23:59:59']);
        }
        
        $Registros = $Registros->get();
        
        if (!empty($Registros)) {
            foreach ($Registros as $k => $Registro) {
                $Registros[$k]['codigo'] = $this->findEstabelecimento($Registro->CNPJ); 
            }
        }
        return Datatables::of($Registros)->make(true);
    }

    public function listar()
    {   
        return view('guiaicms.index')->with('src_inicio',Input::get("src_inicio"))->with('src_fim',Input::get("src_fim"));
    }

    private function findEstabelecimento($cnpj)
    {
        if (!empty($cnpj)) {

            $query = "SELECT codigo FROM estabelecimentos WHERE cnpj = '".$cnpj."'";
            $filial = DB::select($query);

            if (!empty($filial)) {
                return $filial[0]->codigo;
            }else {
                return 'Filial não encontrada';
            }
        }
        return 'Sem Cnpj';
    }

    public function create(Request $request)
    {
        $status = 'success';
        $this->msg = '';
        $input = $request->all();

        if (!empty($input)) {
            if (!$this->validation($input)) {
                $status = 'error';
                return view('guiaicms.create')->with('msg', $this->msg)->with('status', $status);
            }
            
            $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
            $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
            $input['UF'] = $municipio->uf;
            $input['USUARIO'] = Auth::user()->id;
            $input['DATA'] = date('Y-m-d H:i:s');
            $input['CNPJ'] = $this->numero($input['CNPJ']);

            $input['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $input['VLR_RECEITA']));
            $input['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $input['VLR_TOTAL']));
            $input['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $input['MULTA_MORA_INFRA']));
            $input['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $input['JUROS_MORA']));
            $input['TAXA'] = str_replace(',', '.', str_replace('.', '', $input['TAXA']));
            $input['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $input['ACRESC_FINANC']));
            $input['CODBARRAS'] = trim($this->numero($input['CODBARRAS']));

            Guiaicms::create($input);
            $this->msg = 'Guia criada com sucesso';            
        }

    return view('guiaicms.create')->with('msg', $this->msg)->with('status', $status);   
    }

    private function validation($input)
    {
        if (empty($input['CNPJ'])) {
            $this->msg = 'Favor informar o cnpj';
            return false;
        }
        if (!empty($input['CNPJ'])) {
            $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
            if (!empty($estabelecimento)) {
                $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
            }

            if (empty($estabelecimento)) {
                $this->msg = 'Estabelecimento não habilitado ou não existente';
                return false;
            }
        }

        if (empty($input['IE'])) {
            $this->msg = 'Favor informar a inscrição estadual';
            return false;
        }
        if (empty($input['COD_RECEITA'])) {
            $this->msg = 'Favor informar o código da receita';
            return false;
        }
        if (empty($input['REFERENCIA'])) {
            $this->msg = 'Favor informar a referência';
            return false;
        }
        if (empty($input['DATA_VENCTO'])) {
            $this->msg = 'Favor informar a data de vencimento';
            return false;
        }
        if (empty($input['VLR_RECEITA'])) {
            $this->msg = 'Favor informar o valor da receita';
            return false;
        }
        if (empty($input['JUROS_MORA'])) {
            $this->msg = 'Favor informar o Juros Mora ';
            return false;
        }
        if (empty($input['MULTA_MORA_INFRA'])) {
            $this->msg = 'Favor informar o valor da multa mora infra';
            return false;
        }
        if (empty($input['ACRESC_FINANC'])) {
            $this->msg = 'Favor informar o acrescimo financeiro ';
            return false;
        }
        if (empty($input['TAXA'])) {
            $this->msg = 'Favor informar a taxa';
            return false;
        }
        if (empty($input['VLR_TOTAL'])) {
            $this->msg = 'Favor informar o valor total da guia';
            return false;
        }
        
        if (strtolower($municipio->uf) != 'sp') {
            if (empty($input['CODBARRAS'])) {
                $this->msg = 'Favor informar o código de barras';
                return false;
            }
        }

    return true;
    }

    public function editar($id, Request $request)
    {
        $status = 'success';
        $this->msg = '';
        $input = $request->all();
        $guiaicms = Guiaicms::findOrFail($id);
        
        $guiaicms->VLR_RECEITA = $this->maskMoeda($guiaicms->VLR_RECEITA);
        $guiaicms->VLR_TOTAL = $this->maskMoeda($guiaicms->VLR_TOTAL);
        $guiaicms->MULTA_MORA_INFRA = $this->maskMoeda($guiaicms->MULTA_MORA_INFRA);
        $guiaicms->JUROS_MORA = $this->maskMoeda($guiaicms->JUROS_MORA);
        $guiaicms->TAXA = $this->maskMoeda($guiaicms->TAXA);
        $guiaicms->ACRESC_FINANC = $this->maskMoeda($guiaicms->ACRESC_FINANC);

        if (!empty($input)) {
     
            if (!$this->validation($input)) {
                $status = 'error';
                return view('guiaicms.editar')->with('icms', $guiaicms)->with('msg', $this->msg)->with('status', $status);
            }

            if (!empty($guiaicms)) {
                
                $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
                $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
                $input['UF'] = $municipio->uf;
                $input['USUARIO'] = Auth::user()->id;
                $input['DATA'] = date('Y-m-d');
                $input['CNPJ'] = $this->numero($input['CNPJ']);
                $input['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $input['VLR_RECEITA']));
                $input['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $input['VLR_TOTAL']));
                $input['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $input['MULTA_MORA_INFRA']));
                $input['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $input['JUROS_MORA']));
                $input['TAXA'] = str_replace(',', '.', str_replace('.', '', $input['TAXA']));
                $input['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $input['ACRESC_FINANC']));
                $input['CODBARRAS'] = trim($this->numero($input['CODBARRAS']));

                $guiaicms->fill($input);
                $guiaicms->save();
                $this->msg = 'Guia atualizada com sucesso';
            }
        }

    return view('guiaicms.editar')->with('icms', $guiaicms)->with('msg', $this->msg)->with('status', $status);   
    }

    public function excluir($id)
    {
        $this->msg = '';
        $status = 'success';

        if (!empty($id)) {
            Guiaicms::destroy($id);
            $this->msg = 'Registro excluído com sucesso';
            return redirect()->back()->with('status', 'Registro Excluido com sucesso.');
        }
    }

    public function Job()
    {
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:') {
            $path = 'W:';
        }
        $path .= '/storagebravobpo/';
        
        $arquivos = scandir($path);

        $data = array();
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                $data[$k]['arquivos'] = scandir($path_name);   
                $data[$k]['path'] = $path_name;   
            }
        }

        foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $arquivos) {
                if (is_array($arquivos)) {
                    foreach ($arquivos as $A => $arquivo) {
                        if (substr($arquivo, -3) == 'pdf') {
                            $arrayNameFile = explode("_", $arquivo);
                            if (empty($arrayNameFile[2])) {
                                continue;
                            }
                            if ($this->letras($arrayNameFile[2]) != 'ICMS' && $this->letras($arrayNameFile[2]) != 'DIFAL' && $this->letras($arrayNameFile[2]) != 'ANTECIPADO' && $this->letras($arrayNameFile[2]) != 'TAXA' && $this->letras($arrayNameFile[2]) != 'PROTEGE' && $this->letras($arrayNameFile[2]) != 'UNIVERSIDADE' && $this->letras($arrayNameFile[2]) != 'FITUR' && $this->letras($arrayNameFile[2]) != 'FECP' && $this->letras($arrayNameFile[2]) != 'FEEF' && $this->letras($arrayNameFile[2]) != 'ICMSST') {
                                continue;
                            }

                            $files[] = $FILENAME['path'].$arquivo;
                            if (count($files) >= 40) {
                                break;    
                            }
                        }
                    }
                }
            }
        }

        $funcao = 'pdftotext.exe ';
        
        if (!empty($files)) {
            foreach ($files as $K => $file) {
                $filetxt = str_replace('pdf', 'txt', $file);
                $caminho1 = explode('/', $filetxt);
                $caminho1_result = '';
                foreach ($caminho1 as $key => $value) {
                    $arquivonome = $value;
                    $caminho1_result .= $value.'/';
                    if (strpbrk($value, '0123456789１２３４５６７８９０')) {
                        $caminho1_result .= 'results/';
                    }
                }

                $caminho1_result = substr($caminho1_result, 0, -1);   
                shell_exec($funcao.$file.' '.substr($caminho1_result, 0, -8));
                $destino = str_replace('results', 'imported', str_replace('txt', 'pdf', $caminho1_result));
               
                $arr[$file]['arquivo'] = str_replace('txt', 'pdf', $arquivonome); 
                $arr[$file]['path'] = substr($destino, 0, -9); 
                $arr[$file]['arquivotxt'] = $arquivonome; 
                $arr[$file]['pathtxt'] = substr($caminho1_result, 0, -8);
            }
        }

        if (!empty($files)) {
            $this->saveICMS($arr);
        }

        if (empty($_GET['getType'])) {  
            echo "Nenhum arquivo foi encontrado disponível para salvar";exit;
        }

        $cmd = 'C:\wamp\bin\php\php7.0.10\php.exe C:\wamp\www\agenda\public\Background\LeitorMails.php';
        if (substr(php_uname(), 0, 7) == "Windows"){ 
            pclose(popen("start /B " . $cmd, "r"));  
        } else { 
                exec($cmd . " > /dev/null &");   
        } 

        $mensagem = 'Concluído com sucesso';
        return view('guiaicms.job_return')->withMensagem($mensagem);
    }

    public function saveICMS($array)
    {
        $icmsarray = array();

        foreach ($array as $key => $value) {

            $arrayExplode = explode("_", $value['arquivo']);
            
            $AtividadeID = 0;
            if (!empty($arrayExplode[0])) 
                $AtividadeID = $arrayExplode[0];

            $CodigoEstabelecimento = 0;
            if (!empty($arrayExplode[1])) 
                $CodigoEstabelecimento = $arrayExplode[1];

            $NomeTributo = '';
            if (!empty($arrayExplode[2])) 
                $NomeTributo = $this->letras($arrayExplode[2]);

            $PeriodoApuracao = '';
            if (!empty($arrayExplode[3])) 
                $PeriodoApuracao = $arrayExplode[3]; 

            $UF = '';
            if (!empty($arrayExplode[4])) 
                $UF = substr($arrayExplode[4], 0, 2); 

            $estemp_id = 0;
            $arrayEstempId = DB::select('select id FROM estabelecimentos where codigo = '.$CodigoEstabelecimento.' ');
            if (!empty($arrayEstempId[0]->id)) {
                $estemp_id = $arrayEstempId[0]->id;
            }

            $validateAtividade = DB::select("Select count(1) as countAtividade from atividades where id = ".$AtividadeID."");
            if (!$validateAtividade[0]->countAtividade) {
                $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'A Atividade não existe', 'N');
                continue;
            }

            $arqu = 'foo '.$value['arquivotxt'].' bar';    
            
            if (strpos($arqu, 'SP') && substr($arqu, -10) == 'SP.txt bar') {
                $icmsarray = $this->icmsSP($value);
            }

            if (strpos($arqu, 'RJ') && substr($arqu, -10) == 'RJ.txt bar') {
                $icmsarray = $this->icmsRJ($value);   
            }

            if (strpos($arqu, 'RS') && substr($arqu, -10) == 'RS.txt bar') {
                $icmsarray = $this->icmsRS($value);
            }  

            if (strpos($arqu, 'AL') && substr($arqu, -10) == 'AL.txt bar') {
                $icmsarray = $this->icmsAL($value);
            }  

            if (strpos($arqu, 'DF') && substr($arqu, -10) == 'DF.txt bar') {
                $icmsarray = $this->icmsDF($value);
            }
            
            if (strpos($arqu, 'PA') && substr($arqu, -10) == 'PA.txt bar') {
                $icmsarray = $this->icmsPA($value);
            }

            if (strpos($arqu, 'GO') && substr($arqu, -10) == 'GO.txt bar') {
                $icmsarray = $this->icmsGO($value);
            }  
            
            if (strpos($arqu, 'ES') && substr($arqu, -10) == 'ES.txt bar') {
                $icmsarray = $this->icmsES($value);
            }
            
            if (strpos($arqu, 'PB') && substr($arqu, -10) == 'PB.txt bar') {
                $icmsarray = $this->icmsPB($value);
            }

            if (strpos($arqu, 'SE') && substr($arqu, -10) == 'SE.txt bar') {
                $icmsarray = $this->icmsSE($value);
            }
            
            if (strpos($arqu, 'BA') && substr($arqu, -10) == 'BA.txt bar') {
                $icmsarray = $this->icmsBA($value);
            }

            if (strpos($arqu, 'RN') && substr($arqu, -10) == 'RN.txt bar') {
                $icmsarray = $this->icmsRN($value);
            }

            if (strpos($arqu, 'PE') && substr($arqu, -10) == 'PE.txt bar') {
                $icmsarray = $this->icmsPE($value);
            }

            if (strpos($arqu, 'MA') && substr($arqu, -10) == 'MA.txt bar') {
               $icmsarray = $this->icmsMA($value);
            }

            if (strpos($arqu, 'MG') && substr($arqu, -10) == 'MG.txt bar') {
                $icmsarray = $this->icmsMG($value);
            }

            if (strpos($arqu, 'CE') && substr($arqu, -10) == 'CE.txt bar') {
                $icmsarray = $this->icmsCE($value);
            }

            if (strpos($arqu, 'PI') && substr($arqu, -10) == 'PI.txt bar') {
               $icmsarray = $this->icmsPI($value);
            }

            if (strpos($arqu, 'PR') && substr($arqu, -10) == 'PR.txt bar') {
               $icmsarray = $this->icmsPR($value);
            }

            if (strpos($arqu, 'MS') && substr($arqu, -10) == 'MS.txt bar') {
                $icmsarray = $this->icmsMS($value);
            }

            if (strpos($arqu, 'MT') && substr($arqu, -10) == 'MT.txt bar') {
               $icmsarray = $this->icmsMT($value);
            }

            if (!empty($icmsarray)) {
                foreach ($icmsarray as $key => $icms) {
                    if (empty($icms) || count($icms) < 6) {
                        $this->createCritica(1, 0, 8, $value['arquivo'], 'Não foi possível ler o arquivo', 'N');
                        continue;
                    }

                    $validateAtividade = DB::select("Select COUNT(1) as countAtividade FROM atividades where id = ".$AtividadeID);
                    if (empty($AtividadeID) || !$validateAtividade[0]->countAtividade) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Código de atividade não existe', 'N');
                        continue;
                    }

                    $validateCodigo = DB::select("Select COUNT(1) as countCodigo FROM atividades where id = ".$AtividadeID. " AND estemp_id = ".$estemp_id);
                    if (!$estemp_id || !$validateCodigo[0]->countCodigo) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Filial divergente com a filial da atividade', 'N');
                        continue;
                    }

                    $validateTributo = DB::select("Select count(1) as countTributo from regras where id = (select regra_id from atividades where id = ".$AtividadeID.") and tributo_id = 8 or tributo_id = 28");
                    if (!$validateTributo[0]->countTributo) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'O Tributo ICMS não confere com o tributo da atividade', 'N');
                        continue;
                    }

                    $validatePeriodoApuracao = DB::select("Select COUNT(1) as countPeriodoApuracao FROM atividades where id = ".$AtividadeID. " AND periodo_apuracao = '{$PeriodoApuracao}'");
                    if (empty($PeriodoApuracao) || !$validatePeriodoApuracao[0]->countPeriodoApuracao) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Período de apuração diverente do período da atividade', 'N');
                        continue;
                    }

                    $validateUF = DB::select("select count(1) as countUF FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = (select estemp_id FROM atividades where id = ".$AtividadeID.")) AND uf = '".$UF."'");

                    if (empty($UF) || !$validateUF[0]->countUF) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'UF divergente da UF da filial da atividade', 'N');
                        continue;
                    }

                    $alertCentroCusto = DB::select("select count(1) countCentroCusto FROM centrocustospagto where estemp_id = ".$estemp_id." AND centrocusto <> '' AND centrocusto is not null");
                    if (!$alertCentroCusto[0]->countCentroCusto) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Centro de custo não cadastrado', 'S');
                    }

                    $alertCodigoSap = DB::select("select count(1) as countCodigoSap FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = ".$estemp_id.") AND codigo_sap <> '' AND codigo_sap is not null");
                    if (!$alertCodigoSap[0]->countCodigoSap && $UF == 'SP') {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Código SAP do Municipio não cadastrado', 'S');
                    } 
                    
                    //if (!$this->validateEx($icms)) {
                    //    continue;
                    //}
                    //Pedido de remoção da validação pois em alguns casos é necessário importar a validação de regisros iguais 
                     

                    if (!empty($icms['COD_RECEITA'])) {  
                        $icms['COD_RECEITA'] = strtoupper($icms['COD_RECEITA']);
                    }

                    if (!empty($icms['UF'])) {  
                        $icms['UF'] = strtoupper($icms['UF']);
                    }

                    $icms['DATA'] = date('Y-m-d H:i:s');
                    if (!empty($_GET['getType'])) {
                        $input['USUARIO'] = Auth::user()->id;
                    }

                    Guiaicms::create($icms);
                    $destino = str_replace('/imported', '', $value['path']);
                    if (file_exists($destino)) {
                        copy($destino, $value['path']);
                        unlink($destino);
                    }
                }
            }
        }

        if (empty($_GET['getType'])) {  
            echo "Dados gravados com sucesso";exit;
        }

        $mensagem = 'Dados gravados com sucesso';
        return view('guiaicms.job_return')->withMensagem($mensagem);
        
    }

    public function createCritica($empresa_id=1, $estemp_id=0, $tributo_id=8, $arquivo, $critica, $importado)
    {
        $array['importado']     = $importado;
        $array['critica']       = $critica;
        $array['arquivo']       = $arquivo;
        $array['Tributo_id']    = $tributo_id;
        $array['Estemp_id']     = $estemp_id;
        $array['Empresa_id']    = $empresa_id;
        $array['Data_critica']  = date('Y-m-d h:i:s');
        CriticasLeitor::create($array); 
    }    

    public function createCriticaEntrega($empresa_id=1, $estemp_id=0, $tributo_id=8, $arquivo, $critica, $importado)
    {
        $array['importado']     = $importado;
        $array['critica']       = $critica;
        $array['arquivo']       = $arquivo;
        $array['Tributo_id']    = $tributo_id;
        $array['Estemp_id']     = $estemp_id;
        $array['Empresa_id']    = $empresa_id;
        $array['Data_critica']  = date('Y-m-d h:i:s');
        CriticasEntrega::create($array);
    }

    public function validateEx($icms)
    {
        if (empty($icms)) {
            return false;
        }   
        if (empty($icms['CNPJ'])) {
            $icms['CNPJ'] = 0;
        }
        if (empty($icms['REFERENCIA'])) {
            $icms['REFERENCIA'] = 0;
        }   
        $query = 'SELECT * FROM guiaicms WHERE CNPJ = "'.$icms['CNPJ'].'" AND REFERENCIA = "'.$icms['REFERENCIA'].'" AND TRIBUTO_ID = '.$icms['TRIBUTO_ID'].'';

        if (!empty($icms['VLR_TOTAL'])) {
            $query .= ' AND VLR_TOTAL = '.$icms['VLR_TOTAL'];
        }

        if (!empty($icms['IMPOSTO'])) {
            $query .= ' AND IMPOSTO = "'.$icms['IMPOSTO'].'"';
        }

        $validate = DB::select($query);
        if (!empty($validate)) {
            return false;
        }

        return true;
    }

    public function icmsMT($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'MT';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~05 - cnpj ou cpf([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", $i[0]));
        }

        preg_match('~06 - inscricao estadual([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~25 - codigo([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['REFERENCIA'] = trim($i[4]);
        }

        preg_match('~22 - data vencto.([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $valorData = $i[0];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[1])));
        }

        preg_match('~33 - valor a recolher por extenso
novecentos e quarenta e quatro reais e quinze centavos
modelo aprovada pela portaria nº 085/2002([^{]*)~i', $str, $match);
       if (!empty($match)) {
           $i = explode("\n", trim($match[1]));
           $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
           $icms['CODBARRAS'] = $codbarras;
       }

       if (empty($icms['CODBARRAS'])) {
            preg_match('~modelo aprovada pela portaria([^{]*)~i', $str, $match);
           if (!empty($match)) {
               $i = explode("\n", trim($match[1]));
               $codbarras = str_replace('-', '', str_replace(' ', '', $i[1]));
               $icms['CODBARRAS'] = $codbarras;
           }           
       }

       if (!is_numeric($icms['VLR_RECEITA'])) {

            preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['REFERENCIA'] = trim($i[0]);
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[6]);
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[6]);
                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[6]);
                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[7])));
            }


            preg_match('~modelo aprovada pela portaria([^{]*)~i', $str, $match);
            if (!empty($match)) {
               $i = explode("\n", trim($match[1]));
               $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
               $icms['CODBARRAS'] = $codbarras;
            }  

       }
        $v = $this->numero($icms['REFERENCIA']);
        if (empty($v)) {
            preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);        
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['REFERENCIA'] = trim($i[0]);
            } 
        }


        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsMS($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'MS';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~03-cpf/cnpj/ie/renavam([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~01-codigo do tributo 02-vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~04-referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~11 - codigo do municipio([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $valorData = trim($i[2]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~06-principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
        }

        preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
        }

        preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }

        preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
        }

        preg_match('~emissao pelo site: www.sefaz.ms.gov.br. nao use copias, emita um daems por pagamento.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['CODBARRAS'] = str_replace('-', '', str_replace(' ', '', $i[0]));
        }

        if (!isset($icms['VLR_TOTAL'])) {
            preg_match('~06-principal([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
            }

            preg_match('~07-multa([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

            preg_match('~08-juros([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

            preg_match('~09-correcao monetaria 10-total([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            }
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function icmsRS($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~razao social:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($i[0]);
        }

        preg_match('~produto:

cnpj/cpf/insc. est.:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = preg_replace("/[^0-9]/", "", str_replace('.', '', trim($i[0])));
        }

        preg_match('~uf:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if (strlen($i[0]) > 2) {
                $i[0] = trim($i[0]);
                $icms['UF'] =substr($i[0], 0,2);
            }
        }

        preg_match('~periodo de referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = $i[0];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~parcela([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[0])));
            $icms['ATUALIZACAO_MONETARIA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[5])));;
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[11])));;
            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[17])));;
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', str_replace('o', '', $i[23]))));;
        }
        
        preg_match('~informacoes complementares:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['OBSERVACAO'] = trim($i[0]);
        }

        preg_match('~documento valido para pagamento ate([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        $arr = explode('_', $value['arquivotxt']);
        if (!empty($arr)) {
            $atividadeID = $arr[0];
            if (!is_numeric($atividadeID)) {
                $atividadeID = 0;
            }
            $atividade = json_decode(json_encode(DB::select('SELECT * FROM atividades where id = '.$atividadeID.' limit 1')),true);
            if (!empty($atividade)) {
                $estabelecimento = Estabelecimento::where('id', '=', $atividade[0]['estemp_id'])->where('ativo', '=', 1)->first();
                if (!empty($estabelecimento)) {
                    $icms['CNPJ'] = $estabelecimento->cnpj;
                    $icms['ENDERECO'] = $estabelecimento->endereco.', '.$estabelecimento->num_endereco;
                    $municipio = Municipio::findOrFail($estabelecimento->cod_municipio);
                    if (!empty($municipio)) {
                        $icms['MUNICIPIO'] = $municipio->nome;
                    }
                }
            }
        }
                    
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }



    public function icmsRJ($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~\(01\) nome / razao social \(estabelecimento principal\)([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($i[0]);
        }

        preg_match('~\(10\) cnpj/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CNPJ'] =str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~\(04\) uf ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['UF'] = trim($i[0]);
        }

        preg_match('~periodo de referencia: data vencimento:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $l = explode(' ', $i[0]);
            $icms['REFERENCIA'] = trim($l[0]);
            $valorData = trim($l[1]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        if (empty($icms['IE'])) {
            preg_match('~apuracao \(debitos/creditos\) normal([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                $icms['IE'] = str_replace(',', '.', trim(str_replace('.', '', $i[1])));
            }   
        }

        if (empty($icms['IE'])) {
            preg_match('~natureza da receita: cnpj/cpf: inscricao estadual/rj: nome/razao social: endereco: municipio: uf: cep: telefone:([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $a = explode(' ', trim($match[1]));
                $icms['IE'] = trim($this->numero($a[4]));
            }   
        }

        preg_match('~\(06\) receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~\(13\) valor principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));
        }

        preg_match('~\(14\) juros de mora

\(15\) multa de mora([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['JUROS_MORA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));
            $a = explode(' ', $i[2]);
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $a[0]));
        }

        preg_match('~\(16\) multa penal/formal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['MULTA_PENAL_FORMAL'] = str_replace(',', '.', trim(str_replace('.', '', $a[0])));
            $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $i[1])));
        }
        
        preg_match('~\(08\) informacoes complementares([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['OBSERVACAO'] = trim($i[0]);
        }

        preg_match('~\(18\) autenticacao bancaria([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PA';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~1 - codigo da receita 2 - referencia 34 - documento origem
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = $i[0];
            if (empty($icms['IE'])) {
                $icms['IE'] = $this->numero($i[4]);
            }
        }
        
        preg_match('~5 - vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = trim(substr($i[0], 0, 10));
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            $referencia = date('m/Y', strtotime($data_vencimento));
            $k = explode('/', $referencia);
            $k[0] = $k[0]-1;
            if ($k[0] == 0) {
                $k[1] = $k[1] - 1;
            }
            if (strlen($k[0]) == 1) {
                $k[0] = '0'.$k[0];
            }
            $icms['REFERENCIA'] = $k[0].'/'.$k[1];
        }
        
        preg_match('~1 - codigo da receita 2 - referencia 34 - documento origem
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['CODBARRAS'] = trim($i[0]);
        }
        
        preg_match('~8 - taxa r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        
        preg_match('~14 - total r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        
        preg_match('~9 - principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', trim($a[0]))));
        }
        
        preg_match('~12 - multa([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', trim($a[0]))));
        }
        
        preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $codbarras = '';
            foreach ($i as $k => $x) {
                if (strlen($x) > 6) {
                    $codbarras .= $this->numero($x); 
                }
                if ($k == 4) {
                    break;
                }
            }
            
            $icms['CODBARRAS'] = trim($codbarras);
        }

        if (empty($this->numero($icms['VLR_RECEITA']))) {
           preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 - correcao: 11 - acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
           if (!empty($match)) {
               $i = explode(' ', trim($match[1]));
               $icms['REFERENCIA'] = $i[1];
               $a = explode("\n", $i[2]);
               $valorData = trim($a[1]);
               $data_vencimento = str_replace('/', '-', $valorData);
               $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
               $icms['VLR_RECEITA'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[7]))));
               $icms['MULTA_MORA_INFRA'] =  str_replace(',', '.', str_replace('.', '', $i[14]));
               $icms['VLR_TOTAL'] = trim(str_replace('nome:', '', str_replace(',', '.', str_replace('.', '', $i[16]))));
               $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', $i[5]));
               $p = explode('
', $i[4]);
                if(strlen($i[4]) < 5){
                    $p = explode('
', $i[3]);
                }
                
               $icms['IE'] =  $p[0];
               $icms['COD_RECEITA'] =  $p[1];
       }

       preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $codbarras = '';
            foreach ($i as $k => $x) {
                if (strlen($x) > 6) {
                    $codbarras .= $this->numero($x); 
                }
                if ($k == 4) {
                    break;
                }
            }
            
            $icms['CODBARRAS'] = trim($codbarras);
        }
       }
       
       if (strlen($icms['CODBARRAS']) <= 6) {
           preg_match('~\*\*\* autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                $codbarras = '';
                foreach ($i as $k => $x) {
                    if (strlen($x) > 6) {
                        $codbarras .= $this->numero($x); 
                    }
                    if ($k == 4) {
                        break;
                    }
                }
                
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }
       

       if (strlen($icms['MULTA_MORA_INFRA'])  <= 2) {

           preg_match('~receber ate :([^{]*)~i', $str, $match);
           if (!empty($match)) {
               $i = explode(' ', trim($match[1]));
               $valorData = trim(substr($i[0], 0, 10));
               $data_vencimento = str_replace('/', '-', $valorData);
               $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
           }

           preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 - correcao: 11 - acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
               if (!empty($match)) {
                  $i = explode('
', trim($match[1]));
                   $a = explode(' ', $i[2]);
                   $icms['VLR_RECEITA'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[4]))));
                   $icms['TAXA'] =  str_replace(',', '.', str_replace('.', '', $a[2]));
          }


           preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 - correcao: 11 - acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
               if(!empty($match)){
                   $i = explode('
', trim($match[1]));
                   $a = explode(' ', $i[3]);
                   $icms['VLR_TOTAL'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[9]))));
                   $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $a[5]));
                }
            }

        preg_match('~6 - documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode("\n", trim($i[0]));
            $icms['IE'] = $a[0];
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }
    
    public function icmsPB($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PB';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~03 - receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = $this->numero($i[2]);
        }

        preg_match('~05 - inscricao estadual/cgc/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode("\n", trim($i[0]));
            $icms['IE'] = $this->numero($a[0]);
        }

        preg_match('~06 - referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~07 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = trim($i[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~29 - matricula([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            
            $a = explode(' ', $i[0]);
            $b = explode(' ', $i[1]);
            
            $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
            $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
            $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $b[0])));
            $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[2])));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
            $icms['CODBARRAS'] = trim($codbarras);
        }
        if ($icms['MULTA_MORA_INFRA'] > $icms['VLR_RECEITA']) {
            preg_match('~29 - matricula([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
                $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
                $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[2])));
                $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[1])));
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[3]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        if (empty($icms['CODBARRAS'])) {
            preg_match('~29 - matricula([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[1])));
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[3]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        
        if ($icms['VLR_TOTAL'] == $icms['MULTA_MORA_INFRA']) {
            $icms['MULTA_MORA_INFRA'] = '0.00';            
        }        

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsES($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'ES';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~servico icms - comercio([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[4]);
            $icms['COD_RECEITA'] = $this->numero($a[1]);
        }

        if(empty($icms['COD_RECEITA'])){
            preg_match('~
receita ([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['COD_RECEITA'] = $this->numero($a[0]);
        }
        }

        preg_match('~data de referencia([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['REFERENCIA'] = $a[0];
        }

        preg_match('~vencimento([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $valorData = $a[0];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~valor da receita([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[1]);
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }

        preg_match('~credito total([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
        }

        preg_match('~documento unico de arrecadacao versao internet([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsGO($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'GO';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~inscricao estadual:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~documento de origem referencia 300-mensal -([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = substr(trim($i[0]), 0,10);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~validade do calculo: total a recolher:
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[1])));          
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($i[1])));
        }

        preg_match('~foo([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        preg_match('~
receita
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $k = explode(' ', $a[6]);
            $icms['COD_RECEITA'] = trim($k[0]);
        }

    
    if (empty($icms['COD_RECEITA'])) {
            preg_match('~
receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }
    }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsSE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'SE';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }


        preg_match('~inscricao estadual / cpf / cnpj

numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }
        
        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST'){

        preg_match('~
valor total

([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            $icms['CODBARRAS'] = trim(str_replace('observacao', '', trim(str_replace(' ', '', $i[0]))));
       }

       preg_match('~
validade([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = substr($i[0], 0,10);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            $referencia = date('m/Y', strtotime($data_vencimento));
            $k = explode('/', $referencia);
            $k[0] = $k[0]-1;
            if ($k[0] == 0) {
                $k[1] = $k[1] - 1;
            }
            if (strlen($k[0]) == 1) {
                $k[0] = '0'.$k[0];
            }
            $icms['REFERENCIA'] = $k[0].'/'.$k[1];
       }
        
        } else {

        preg_match('~validade

valor total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
           $valorData = $i[0];
           $data_vencimento = str_replace('/', '-', $valorData);
           $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
           $referencia = date('m/Y', strtotime($data_vencimento));
           $k = explode('/', $referencia);
           $k[0] = $k[0]-1;
           if ($k[0] == 0) {
               $k[1] = $k[1] - 1;
           }
           if (strlen($k[0]) == 1) {
               $k[0] = '0'.$k[0];
           }
           $icms['REFERENCIA'] = $k[0].'/'.$k[1];
        }
            
        preg_match('~validade

valor total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsBA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'BA';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;


        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~3-inscricao estadual/cpf ou cnpj([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~4-referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~1-codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~2-data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = $i[0];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~7-valor principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[1]);
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        preg_match('~9-acres. moratorio e/ou juros([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[1]);
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        preg_match('~10-multa por infracao([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[1]);
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        preg_match('~11-total a recolher([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[1]);
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }

        preg_match('~---------------------------------------------------------------------------------------------------------------------------------------------------([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsRN($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'RN';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~receita ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = trim($this->numero($i[0]));
        }
        
        preg_match('~vencimento
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = $i[0];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            $referencia = date('m/Y', strtotime($data_vencimento));
            $k = explode('/', $referencia);
            $k[0] = $k[0]-1;
            if ($k[0] == 0) {
                $k[1] = $k[1] - 1;
            }
            if (strlen($k[0]) == 1) {
                $k[0] = '0'.$k[0];
            }
            $icms['REFERENCIA'] = $k[0].'/'.$k[1];
        }
        
        preg_match('~
valor do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }


        preg_match('~
valor do documento([^{]*)~i', $str, $match);
       if (!empty($match)) {
           $i = explode('
', trim($match[1]));
           $codbarras = '';
           foreach ($i as $k => $x) {
              if (strlen($x) == 13) {
                  $codbarras .= $this->numero($x);
              }
              if (strlen($codbarras) != 36) {
                  if ($k == 14) {
                      break;
                  }
              }
              if ($k == 16) {
                  break;
              }
          }

           $icms['CODBARRAS'] = trim($codbarras);
       }
        
        if (isset($icms['COD_RECEITA']) && trim($icms['COD_RECEITA']) == 1245) {
            $icms['IMPOSTO'] = 'SEFAZ';
       } 

        preg_match('~03 - receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = $this->numero($i[2]);
        }

        preg_match('~06 - referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~07 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = trim($i[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~05 - inscricao estadual/cgc/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms['IE'] = $this->numero($i[0]);
        }

        preg_match('~29 - matricula([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            
            $a = explode(' ', $i[0]);
            $b = explode(' ', $i[1]);
            $c = explode(' ', $i[2]);
            
            $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
            $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
            $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $b[0])));
            $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[2])));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
            $icms['CODBARRAS'] = trim($codbarras);
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsMG($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms[0]['CNPJ'] = $estabelecimento->cnpj;
        $icms[0]['IE'] = $estabelecimento->insc_estadual;
        $icms[0]['UF'] = 'MG';

        $icms[1]['CNPJ'] = $estabelecimento->cnpj;
        $icms[1]['IE'] = $estabelecimento->insc_estadual;
        $icms[1]['UF'] = 'MG';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms[0]['TRIBUTO_ID'] = 8;
        $icms[1]['TRIBUTO_ID'] = 8;
     

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms[0]['IMPOSTO'] = 'GAREI';
            $icms[1]['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms[0]['IMPOSTO'] = 'SEFAZ';
            $icms[1]['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms[0]['IMPOSTO'] = 'SEFAZ';
            $icms[1]['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms[0]['IMPOSTO'] = 'SEFAB';
            $icms[1]['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms[0]['IMPOSTO'] = 'SEFAT';
            $icms[1]['IMPOSTO'] = 'SEFAT';
        }
        
        preg_match('~validade([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = substr($i[0], 0,12);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms[0]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            $icms[1]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            $referencia = date('m/Y', strtotime($data_vencimento));
            $k = explode('/', $referencia);
            $k[0] = $k[0]-1;
            if ($k[0] == 0) {
                $k[1] = $k[1] - 1;
            }
            if (strlen($k[0]) == 1) {
                $k[0] = '0'.$k[0];
            }
            $icms[0]['REFERENCIA'] = $k[0].'/'.$k[1];
            $icms[1]['REFERENCIA'] = $k[0].'/'.$k[1];
        }
        
        preg_match('~receita

periodo ref.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms[0]['COD_RECEITA'] = $this->numero($a[1]);

            if (!empty($i[2])) {
                $k = explode(' ', $i[2]);
                $icms[1]['COD_RECEITA'] = $this->numero($k[1]);
            }
        }
        
        preg_match('~valor([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
                $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));
            
            if (strlen(substr($i[0], 0,-5)) > 8) {
                $a = explode('
', substr($i[0], 0,-5));
                $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                $icms[1]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
            }
        }

        preg_match('~numero identificacao([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if (isset($i[0])) {
                $a = explode(" ", trim($i[0]));
                if (isset($a[1])) {
                    $icms[0]['IE'] = $this->numero($a[1]);
                }
            }
        }

        preg_match('~multa([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
                $icms[0]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));

            if (!strstr($i[0], "j"))  {
                $icms[0]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                $icms[1]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[1], 0,-5))));
            }
        }

        preg_match('~juros([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
                $icms[0]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));

            if (!strstr($i[0], "t"))  {
                $icms[0]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                $icms[1]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[1], 0,-5))));
            }
        }

        preg_match('~total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
                $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));
            
            if (strlen(substr($i[0], 0,-5)) > 8) {
                $a = explode('
', substr($i[0], 0,-5));
                $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                $icms[1]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
            }
        }

        preg_match('~total

r\$
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
                $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        preg_match('~linha digitavel:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms[0]['CODBARRAS'] = trim($this->numero($i[0]));
            $icms[1]['CODBARRAS'] = trim($this->numero($i[0]));
        }

        if (empty($icms[0]['IE'])) {
            preg_match('~numero identificacao([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
    ', trim($match[1]));
                $a = explode(' ', $i[0]);
                $icms[0]['IE'] = trim($this->numero($a[1]));
                $icms[1]['IE'] = trim($this->numero($a[1]));
            }
        }

        if (empty($icms[0]['COD_RECEITA'])) {
            preg_match('~receita

periodo ref.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(" ", trim($match[1]));
            $icms[0]['COD_RECEITA'] = trim($i[0]);
        }
        }

        if (isset($icms[0]['VLR_RECEITA'])) {
        $check = $this->letras($icms[0]['VLR_RECEITA']);
        if (!empty($check)) {
            preg_match('~total

r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($i[4])));
            $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($i[4])));
        }
        }   
        }

        if (isset($icms[0]['IE'])) {
            if (empty($icms[0]['IE'])) {
                preg_match('~numero([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    $a = explode(' ', $i[0]);
                    $icms[0]['IE'] = trim($this->numero($a[1]));
                }
            }
        }

        if (isset($icms[0]['VLR_RECEITA'])) {
            if (strlen($icms[0]['VLR_RECEITA'] > 11)) {
                preg_match('~valor([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(" ", trim($match[1]));
                    $a = explode("\n", trim($i[0]));
                    $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[0])));
                    $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[0])));
                }
            }
        }
        
        preg_match('~mes ano de referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms[0]['REFERENCIA'] = str_replace(' ', '',trim($i[0]));
        }

        preg_match('~numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($i[21])));
        }

        if (isset($icms[0]['REFERENCIA'])) {
            if (strlen($icms[0]['REFERENCIA']) != 7) {
                preg_match('~validade([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));
                    $valorData = substr($i[0], 0,12);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms[0]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                    $icms[1]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                    $referencia = date('m/Y', strtotime($data_vencimento));
                    $k = explode('/', $referencia);
                    $k[0] = $k[0]-1;
                    if ($k[0] == 0) {
                        $k[1] = $k[1] - 1;
                    }
                    if (strlen($k[0]) == 1) {
                        $k[0] = '0'.$k[0];
                    }
                    $icms[0]['REFERENCIA'] = $k[0].'/'.$k[1];
                }
            }
        }
    
        $vlr_total = 'a';

        preg_match('~numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            $vlr_total = str_replace(',', '.', str_replace('.', '',trim($i[20])));
        }

        if (empty($icms[0]['VLR_TOTAL']) || is_numeric($vlr_total)) {
            $icms[0]['VLR_TOTAL'] = $vlr_total;
        }

        if (empty($icms[0]['REFERENCIA'])) {
            $ano = substr($file_content[3], -4);
            $mes = substr($file_content[3], 0,2);
            $icms[0]['REFERENCIA'] = $mes.'/'.$ano;
        }
        
        if (substr($icms[0]['REFERENCIA'], 0,2) == '00') {
            preg_match('~periodo ref.([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if (!empty($i[0])) {
                    $a = explode(' ', $i[0]);
                    foreach ($a as $x => $data) {
                    }
                    $icms[0]['REFERENCIA'] = substr($data, -8);
                }
            }
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms[0];
        return $icmsarray;
    }

    private function letras($string)
    {
        $nova = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '', $string);
        return $nova;
    }

    public function icmsDF($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'DF';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }
        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }
        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }
        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }
        preg_match('~12.res. sef([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $i = explode(' ', $a[0]);
            $k = explode(' ', $a[1]);
            
            if (isset($i[1])) {
                $icms['COD_RECEITA'] = $i[1];
            }
            
            if(empty($icms['IE'])){
                $icms['IE'] = $i[0];
            }
            
            $icms['REFERENCIA'] = $k[0];
            
            if (isset($k[1])) {
                $valorData = trim($k[1]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }
        
        preg_match('~13.principal - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$a[0]));
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$a[4]));
            
            $codbarras = '';
            $cod_barras = explode(' ', $a[6]);
            foreach($cod_barras as $single){
                if($this->numero($single) > 8){
                    $codbarras .= $this->numero($single);
                }
            }
            $icms['CODBARRAS'] = $codbarras;
        }
        
        preg_match('~15.juros - r\$ 16.outros - r\$ 17.valor total - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $p = explode(' ', $a[0]);
            $icms['TAXA'] = str_replace(',', '.', str_replace('.', '',$p[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$p[2]));
        }
        if (count($icms['CODBARRAS']) <= 8) {
        if (empty($icms['IE']) || strlen($this->letras($icms['IE'])) > 4) {
            preg_match('~df ([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $a = explode(' ', trim($match[1]));
                $icms['IE'] = trim(substr($a[0], 0,8));
            }
        }
        preg_match('~17.valor total - r\$
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CODBARRAS'] = trim($this->numero($i[0]));
            $a = explode(' ', $i[2]);
            $k = explode(' ', $i[3]);
            $custos = explode(' ', $i[5]);
            $icms['COD_RECEITA'] = $a[1];
            $icms['REFERENCIA'] = $k[0];
            if (isset($k[1])) {
                $valorData = trim($k[1]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
            
            if(count($custos) == 2){    
                $custos_pp = explode(' ', $i[6]);
                if (isset($custos[1])) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
                }
                if (isset($custos_pp[1])) {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos_pp[1]));
                }
                if (isset($custos_pp[2])) {
                    $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos_pp[2]));
                }
                
                if (isset($custos_pp[3])) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos_pp[3]));
                }
            } else {
                if (isset($custos[1])) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
                }
                if (isset($custos[2])) {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos[2]));
                }
                if (isset($custos[3])) {
                    $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos[3]));
                }
                if (isset($custos[5])) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos[5]));
                }           
            }
        }
        }
    
    if (strlen($this->letras($icms['JUROS_MORA'])) > 5) {
        preg_match('~16.outros - r\$ 17.valor total - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $custos = explode(' ', $i[7]);
            if (isset($custos[0])) {
                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos[0]));
            }
            if (isset($custos[1])) {
                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
            }
            if (isset($custos[3])) {
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos[3]));
            }
        } else {
        preg_match('~17.valor total - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $custos = explode(' ', $i[7]);
            if (isset($custos[0])) {
                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos[0]));
            }
            if (isset($custos[1])) {
                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
            }
            if (isset($custos[3])) {
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos[3]));
            }
        }
        }
    }
        preg_match('~valor original: r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if (isset($i[0])) {
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));
            }
        }
        if (empty($this->numero($icms['COD_RECEITA']))) {
        preg_match('~12.res. sef ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', trim($i[0]));
            if (isset($a[1])) {
                $icms['COD_RECEITA'] = $a[1];
            }
        }   
        }
        if (empty($this->numero($icms['REFERENCIA']))) {
        preg_match('~12.res. sef ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', trim($i[1]));
            if (isset($a[0])) {
                $icms['REFERENCIA'] = $a[0];
            }
        }
        }  
        if (strlen($icms['CODBARRAS']) < 20) {
        preg_match('~aviso aos bancos : receber ate([^{]*)~i', $str, $match);
            if(!empty($match)){
                $i = explode(' ', $match[1]);
                if (is_array($i)) {
                    $codbarras = '';
                    foreach ($i as $k => $v) {
                        if (strlen($this->numero($v)) > 8) {
                            $codbarras .= trim($v);
                        }
                        if ($k == 5) {
                         break;
                        }
                    }
            $icms['CODBARRAS'] = substr($codbarras, 0, -11);
                }
            }
        }

        
        if (empty($this->numero($icms['COD_RECEITA']))) {
           preg_match('~01.cf/df 02.cod receita 03.cota ou refer. 04.vencimento 05.exercicio([^{]*)~i', $str, $match);
           if(!empty($match)){
               $i = explode('
', $match[1]);

               $a = explode(' ', $i[2]);
               $icms['COD_RECEITA'] = $a[1];
               $c = explode(' ', $i[3]);
               $icms['REFERENCIA'] = $c[0];

               }
       }

    fclose($handle);
    $icmsarray = array();
    $icmsarray[0] = $icms;
    return $icmsarray;
    }


    public function icmsCE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'CE';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
     
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~numeracao do codigo de barras([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $codbarras = '';
            foreach ($i as $key => $value) {
                $codbarras .= $this->numero($value);
                if ($key == 3) {
                    break;
                }
            }
            $icms['CODBARRAS'] = substr($codbarras, 0, -1);
        }

        preg_match('~1 - codigo/especificacao da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = $i[0];
        }

        preg_match('~5 - periodo referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['REFERENCIA'] = trim(substr($i[0], 0,-1));
        }

        preg_match('~3 - pagamento ate([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = trim(substr($i[0], 0,10));
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~6 - valor principal \*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }
        
        preg_match('~7 - multa

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        if(empty($icms['MULTA_MORA_INFRA'])){
        preg_match('~7 - multa \*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', trim($i[0]));
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        }

        preg_match('~8 - juros

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        } 

        if(empty($icms['JUROS_MORA'])){
        preg_match('~8 - juros \*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', trim($i[0]));
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        }

        preg_match('~10 - total a recolher

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }
        
        if(empty($icms['VLR_TOTAL'])){
        preg_match('~10 - total a recolher \*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }
        }
            
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPR($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PR';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~periodo de referencia
05([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~codigo da receita
01
data de vencimento
02([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['COD_RECEITA'] = trim($a[0]);
            $valorData = $a[1];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }
        
        preg_match('~valor da receita \(r\$\)
09([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));;
        }

        preg_match('~total a recolher \(r\$\)
13([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$i[0]));;
        }
        

        preg_match('~contribuinte pagar no banco do brasil, itau, bradesco, santander, sicredi, bancoob ou rendimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            foreach ($i as $k => $x) {
                if (strlen($this->numero($x)) == 48) {
                    $codbarras = $this->numero($x); 
                }
                if ($k == 7) {
                    break;
                }
            }
            
            $icms['CODBARRAS'] = trim($codbarras);
        }


        if (!isset($icms['CODBARRAS'])) {
            preg_match('~os valores e informacoes foram fornecidos pelo contribuinte pagar no banco do brasil, bancoob, bradesco, itau, rendimento, santander ou sicredi([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));

                foreach ($i as $k => $x) {
                    if (strlen($this->numero($x)) == 48) {
                        $codbarras = $this->numero($x); 
                    }
                    if ($k == 7) {
                        break;
                    }
                }
                
                $icms['CODBARRAS'] = trim($codbarras);
            }            
        }
        

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PE';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~06 - codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($this->numero($i[0]));
        }

        preg_match('~07 - periodo fiscal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~02 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = trim($i[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~05 - valor do tributo em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }        

        preg_match('~10 - valor dos juros em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~08 - valor da multa em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~16 - total a pagar em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~governo do estado de pernambuco secretaria da fazenda documento de arrecadacao estadual([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        preg_match('~09 - documento de identificacao do contribuinte([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[2]);
            $icms['COD_IDENTIFICACAO'] = trim($this->numero($a[1]));
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsMA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'MA';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~data vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $valorData = trim(substr($i[0], 0,10));
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }
        
        preg_match('~referencia/ parcela vencimento codigo da receita valor principal valor dos juros valor da multa

valor total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            
            $icms['REFERENCIA'] = trim($i[0]);
            if(strlen($i[0]) > 7){
                $icms['REFERENCIA'] = trim($i[2]);
            }

            $icms['COD_RECEITA'] = trim($i[4]);
            if(strlen($i[4]) > 4){
                $icms['COD_RECEITA'] = trim($i[6]);
            }
            $valores = explode(' ', $i[6]);
            if(count($valores) > 3){
                $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
                $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
                $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
                $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));                
            } else {
                $valores = explode(' ', $i[8]);
                $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
                $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
                $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
                $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));
            }
        }

        preg_match('~linha digitavel:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $codbarras = '';
            foreach ($i as $key => $value) {
                if (is_numeric($this->numero($value)) && (strlen($this->numero($value)) == 11 || strlen($this->numero($value)) == 1)) {
                    $codbarras .= $this->numero($value);
                }
                if ($key == 8) {
                    break;
                }
            }
            $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
            $icms['CODBARRAS'] = trim($codbarras);
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPI($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        //$icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'PI';
        
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~01 - inscricao estadual/renavam

02 - cnpj/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CNPJ'] = $i[2];
            $icms['IE'] = $i[0];
        } 

        preg_match('~valor principal 18 - atualizacao monetaria 19 - juros 20 - multa 21 - taxa 22 - total a recolher
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['REFERENCIA'] = $a[0];
            $valorData = trim($a[1]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));

            $k = explode(' ', $i[1]);
            $icms['COD_RECEITA'] = $k[0];
            $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[3]))));;

            $valores = explode(' ', $i[2]);
            $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
            $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[3]))));
            $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
        } 

        preg_match('~11 - linha digitavel([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = '';
            foreach ($i as $key => $value) {
                if (is_numeric($this->numero($value))) {
                    $codbarras .= $this->numero($value);
                }
                if ($key == 5) {
                    break;
                }
            }
            $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
            $icms['CODBARRAS'] = trim($codbarras);
        }

    if(!isset($icms['VLR_TOTAL']) || !isset($icms['VLR_RECEITA'])){
      preg_match('~17 - valor principal 18 - atualizacao monetaria([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));
          $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));
      }

      preg_match('~19 - juros 20 - multa 21 - taxa([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));
          $a = explode(' ', $i[0]);

          $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '',$a[0])));
          $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '',$a[1])));
      }

      preg_match('~22 - total a recolher([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));

          $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$i[0]));
      }

      preg_match('~12 - periodo de referencia([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));

          $icms['REFERENCIA'] = $i[0];

      }

      preg_match('~13 - data de vencimento([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));

          $valorData = trim($i[0]);
          $data_vencimento = str_replace('/', '-', $valorData);
          $icms['DATA_VENCTO'] = date ('Y-m-d', strtotime($data_vencimento));

      }

      preg_match('~14 - codigo da receita([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));

          $icms['COD_RECEITA'] = $i[0];

      }

      preg_match('~02 - cnpj/cpf([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));

          $icms['IE'] = $i[0];

      }

      preg_match('~11 - linha digitavel([^{]*)~i', $str, $match);
      if (!empty($match)) {
          $i = explode('
', trim($match[1]));
          $codbarras = '';
          foreach ($i as $key => $value) {
              if (is_numeric($this->numero($value))) {
                  $codbarras .= $this->numero($value);
              }
              if ($key == 5) {
                  break;
              }
          }
          $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
          $icms['CODBARRAS'] = trim($codbarras);
          }
       }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function numero($str) {
        return preg_replace("/[^0-9]/", "", $str);
    }


    public function icmsAL($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'AL';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }
        
        preg_match('~caceal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] =substr(str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($this->numero($i[0]))))), 0, -6);
            if (empty($icms['COD_RECEITA'])) {
                $icms['COD_RECEITA'] = trim($this->numero($i[0]));
            }
        }

        preg_match('~referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            if(isset($a[2])){
                $icms['REFERENCIA'] = trim($a[2]);          
            }
            if (!is_numeric($icms['REFERENCIA'])) {
                $icms['REFERENCIA'] = substr($i[0], 0,7);
            }
        }

        preg_match('~vencimento principal cm desconto juros multa total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);

            $valorData = trim($a[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            if(empty($icms['REFERENCIA'])){
                $referencia = date('m/Y', strtotime($data_vencimento));
                $k = explode('/', $referencia);
                $k[0] = $k[0]-1;
                if ($k[0] == 0) {
                    $k[1] = $k[1] - 1;
                }
                if (strlen($k[0]) == 1) {
                    $k[0] = '0'.$k[0];
                }
                $icms['REFERENCIA'] = $k[0].'/'.$k[1];          
            }
            
            if(empty($icms['COD_RECEITA'])){
        preg_match('~
data de emissao
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = $this->numero(trim($i[0]));
        }
            }
            
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[1])));
            if(isset($a[1]) && strlen($a[1]) == 1){
                // $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[2])));             
                $icms['VLR_RECEITA'] = $a[1].str_replace(',', '.', str_replace('.', '',trim($a[2])));             
            }

            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',trim($a[4])));
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',trim($a[5])));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[6])));
            if(isset($a[7]) && strlen($a[7]) == 1){
                // $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[8])));
                $icms['VLR_TOTAL'] = $a[7].str_replace(',', '.', str_replace('.', '',trim($a[8])));
            }
        }

        preg_match('~via - banco([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function icmsSP($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['UF'] = 'SP';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        //inscricao estadual
        preg_match('~inscricao estadual([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode("\n", trim($match[1]));
            $i = explode(' ', trim($a[0]));
            $icms['IE'] = trim(str_replace(".", "", $i[1]));
        }

        //razão social
        preg_match('~nome ou razao social
15([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($a[0]);
        }

        //municipio
        preg_match('~municipio([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['MUNICIPIO'] = trim($i[0]);
        }
        
        //cpf - cpnj
        preg_match('~cnpj ou cpf
05([^{]*)~i', $str, $match);
        if(!empty($match)){
            $i = explode("
", trim($match[1]));
            $icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", $i[0]));
        }
       
       //referencia verificar
        preg_match('~referencia \(mes/ano\)
07 ([^{]*)~i', $str, $match);
        if (!empty($match)) {
             $k = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($k[0]);
        }

        //cod_receita
        preg_match('~codigo da receita
03([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $k = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($k[0]);
        }

        if (empty($icms['IE'])) {
         //inscricao estadual
        preg_match('~inscricao estadual([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $k = explode('
', trim($match[1]));
            $icms['IE'] = $this->numero(trim($k[2]));
        }   
        }

        //observacao
        preg_match('~observacoes
21([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $l = explode('
', $match[1]);
            $icms['OBSERVACAO'] = '';
            foreach($l as $lk)
            {
                if($lk === '22 autenticacao mecanica') {
                    break;
                }
                $icms['OBSERVACAO'] .= ' '.trim($lk); 
            }
        }
        

        //vlr_receita
        preg_match('~valor da receita \(nominal ou corrigida\)
09
juros de mora
10
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("
", trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));;
        }

        //vlr_total
        preg_match('~valor total
14([^{]*)~i', $str, $match);
        if (!empty($match)) {
        $string = explode('
',trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $string[0])));
        }
        
        //ie e cnae
        preg_match('~placa do veiculo
20([^{]*)~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('
',$string);
        $icms['CNAE'] = $string[0];
        }
        
        //cidade e endereço e data vencimento
        preg_match('~endereco
16 ([^{]*)~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('
',$string);
        $icms['ENDERECO'] = $string[0];
        }
        
        preg_match('~data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('
',$string);
        $valorData = $string[0];
        $data_vencimento = str_replace('/', '-', $valorData);
        $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }
        
        preg_match('~juros de mora([^{]*)~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('
',$string);
        if(strlen($string[0]) != 2)
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $string[0]));
        }
        
        $v = $this->numero($icms['IE']);
        if (empty($v)) {
             //inscricao estadual
            preg_match('~inscricao estadual([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $k = explode("\n", trim($match[1]));
                $icms['IE'] = $this->numero(trim($k[2]));
            }   
        }

        if (!isset($icms['VLR_RECEITA']) || empty($icms['VLR_RECEITA'])) {
            preg_match('~valor da receita \(nominal ou corrigida\)([^{]*)~i', $str, $match);

            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[2])));;
            }
        }
        
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function search_criticas()
    {
        return view('guiaicms.search_criticas');
    }


    public function criticas(Request $request)
    {
        $mensagem = "Não existem críticas no período selecionado.";
        $input = $request->all();
        if (empty($input['inicio']) || empty($input['fim'])) {
            return redirect()->back()->with('status', 'É necessário informar as duas datas.');
        }

        $data_inicio = $input['inicio']. ' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';

        $sql = "Select DATE_FORMAT(A.Data_critica, '%d/%m/%Y') as Data_critica, B.codigo, C.nome, A.critica, A.arquivo, A.importado FROM criticasleitor A LEFT JOIN estabelecimentos B ON A.Estemp_id = B.id LEFT JOIN tributos C ON A.Tributo_id = C.id WHERE A.Data_critica BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.Empresa_id = ".$this->s_emp->id." ";
        
        $dados = json_decode(json_encode(DB::Select($sql)),true);

        if (!empty($dados)) {
            $mensagem = '';
        }

        return view('guiaicms.search_criticas')->withDados($dados)->with('mensagem', $mensagem);
    }
    

    public function search_criticas_entrega()
    {
        return view('guiaicms.search_criticas_entrega');
    }

    public function criticas_entrega(Request $request)
    {
       $input = $request->all();
       if (empty($input['inicio']) || empty($input['fim'])) {
           return redirect()->back()->with('status', 'É necessário informar as duas datas.');
       }

       $data_inicio = $input['inicio'].' 00:00:00';
       $data_fim = $input['fim'].' 23:59:59';

       $sql = "Select DATE_FORMAT(A.Data_critica, '%d/%m/%Y') as Data_critica, B.codigo, C.nome, A.critica, A.arquivo, A.importado FROM criticasentrega A LEFT JOIN estabelecimentos B ON A.Estemp_id = B.id INNER JOIN tributos C ON A.Tributo_id = C.id WHERE A.Data_critica BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.Empresa_id = ".$this->s_emp->id." ";

       $dados = json_decode(json_encode(DB::Select($sql)),true);

       return view('guiaicms.search_criticas_entrega')->withDados($dados);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function icms()
    {  
        $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->lists('codigo','id');
        $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->lists('UF','UF');
        $estabelecimentosselected = array();
        $ufselected = array();

        return view('guiaicms.icms')->withEstabelecimentos($estabelecimentos)->withUf($uf)->withestabelecimentosselected($estabelecimentosselected)->withufselected($estabelecimentosselected);
    }

    public function planilha(Request $request)
    {  
        $input = $request->all();
        $estabelecimentosselected = array();
        if (!empty($input['multiple_select_estabelecimentos'])) {
            $estabelecimentosselected = $input['multiple_select_estabelecimentos'];
        }

        $ufselected = array();
        if (!empty($input['multiple_select_uf'])) {
            $ufselected = $input['multiple_select_uf'];
        }
        

        $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->lists('codigo','id');
        $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->lists('UF','UF');

        if (empty($input['inicio']) || empty($input['fim'])) {
            return redirect()->back()->with('status', 'É necessário informar as datas de inicio e fim.');
        }

        $data_inicio = $input['inicio'].' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';
        
        $sql = "SELECT A.*, B.empresa_id, B.codigo, C.uf, D.centrocusto FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj inner join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.CODBARRAS <> ''"; 

        if (!empty($input['inicio_leitura']) && !empty($input['fim_leitura'])) {
            $inicio_leitura = $input['inicio_leitura'].' 00:00:00';
            $fim_leitura = $input['fim_leitura'].' 23:59:59';
            
            $sql .= " AND A.DATA BETWEEN '".$inicio_leitura."' AND '".$fim_leitura."'";
        }

        if (!empty($input['multiple_select_estabelecimentos'])) {
            $sql .= " AND A.CNPJ IN (Select cnpj FROM estabelecimentos where id IN (".implode(',', $input['multiple_select_estabelecimentos'])."))";
        }

        if (!empty($input['multiple_select_uf'])) {
            $sql .= " AND A.UF IN (".implode(',', array_map(function($value){
                return "'$value'";
            }, $input['multiple_select_uf'])).")";
        }

        $dados = json_decode(json_encode(DB::Select($sql)),true);

        $planilha = array();
        foreach ($dados as $key => $dado) {
            if ($dado['empresa_id'] == $this->s_emp->id) {
                $planilha[] = $dado;
            }
        }

        $sql_semcod = "SELECT A.*, B.empresa_id, B.codigo, C.uf, D.centrocusto, C.codigo_sap FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj left join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.CODBARRAS = ''"; 

        if (!empty($input['multiple_select_estabelecimentos'])) {
            $sql_semcod .= " AND A.CNPJ IN (Select cnpj FROM estabelecimentos where id IN (".implode(',', $input['multiple_select_estabelecimentos'])."))";
        }

        if (!empty($input['multiple_select_uf'])) {
            $sql_semcod .= " AND A.UF IN (".implode(',', array_map(function($value){
                return "'$value'";
            }, $input['multiple_select_uf'])).")";
        }

        if (!empty($input['inicio_leitura']) && !empty($input['fim_leitura'])) {
            $inicio_leitura = $input['inicio_leitura'].' 00:00:00';
            $fim_leitura = $input['fim_leitura'].' 23:59:59';
                
            $sql_semcod .= " AND A.DATA BETWEEN '".$inicio_leitura."' AND '".$fim_leitura."'";
        }
        
        $dados_semcod = json_decode(json_encode(DB::Select($sql_semcod)),true);

        $planilha_semcod = array();
        foreach ($dados_semcod as $key => $dado) {
            if ($dado['empresa_id'] == $this->s_emp->id) {
                $planilha_semcod[] = $dado;
            }
        }

        foreach ($planilha as $chave => $valorl) {
            if ($valorl['MULTA_MORA_INFRA'] == 0) {
                $planilha[$chave]['MULTA_MORA_INFRA'] = '';
            }

            if ($valorl['HONORARIOS_ADV'] == 0) {
                $planilha[$chave]['HONORARIOS_ADV'] = '';
            }

            if ($valorl['ACRESC_FINANC'] == 0) {
                $planilha[$chave]['ACRESC_FINANC'] = '';
            }

            if ($valorl['JUROS_MORA'] == 0) {
                $planilha[$chave]['JUROS_MORA'] = '';
            }

            if ($valorl['MULTA_PENAL_FORMAL'] == 0) {
                $planilha[$chave]['MULTA_PENAL_FORMAL'] = '';
            }
        }

        foreach ($planilha_semcod as $chave2 => $valorl2) {
            if ($valorl2['MULTA_MORA_INFRA'] == 0) {
                $planilha_semcod[$chave2]['MULTA_MORA_INFRA'] = '';
            }

            if ($valorl2['HONORARIOS_ADV'] == 0) {
                $planilha_semcod[$chave2]['HONORARIOS_ADV'] = '';
            }

            if ($valorl2['ACRESC_FINANC'] == 0) {
                $planilha_semcod[$chave2]['ACRESC_FINANC'] = '';
            }

            if ($valorl2['JUROS_MORA'] == 0) {
                $planilha_semcod[$chave2]['JUROS_MORA'] = '';
            }

            if ($valorl2['MULTA_PENAL_FORMAL'] == 0) {
                $planilha_semcod[$chave2]['MULTA_PENAL_FORMAL'] = '';
            }
        }

        $valorData = $data_fim;
        $data_vencimento_2 = str_replace('-', '/', $valorData);
        $data_fim = date('dmY', strtotime($data_vencimento_2));

        $valorData2 = $data_inicio;
        $data_vencimento = str_replace('-', '/', $valorData2);
        $data_inicio = date('dmY', strtotime($data_vencimento));   

        $mensagem = 'Período carregado com sucesso';
        if (empty($dados) && empty($dados_semcod)) {
            $mensagem = 'Não há dados nesse período';
        }

        if (!empty($planilha)) {
            foreach ($planilha as $key => $value) {
                $planilha[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
                $planilha[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
                $planilha[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
                $planilha[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
                $planilha[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
                $planilha[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
                $planilha[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
            }
        }

        if (!empty($planilha_semcod)) {
            foreach ($planilha_semcod as $key => $value) {
                $planilha_semcod[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
                $planilha_semcod[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
                $planilha_semcod[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
                $planilha_semcod[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
                $planilha_semcod[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
                $planilha_semcod[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
                $planilha_semcod[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
            }
        }
        return view('guiaicms.icms')->withUf($uf)->withEstabelecimentos($estabelecimentos)->with('planilha', $planilha)->with('planilha_semcod', $planilha_semcod)->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('mensagem', $mensagem)->withestabelecimentosselected($estabelecimentosselected)->withufselected($ufselected);
    }

    public function conferencia(Request $request)
    {  
        $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->lists('codigo','id');
        $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->lists('UF','UF');
        $estabelecimentosselected = array();
        $ufselected = array();

        $input = $request->all();
        if (!empty($input)) {

            $estabelecimentosselected = array();
            if (!empty($input['multiple_select_estabelecimentos'])) {
                $estabelecimentosselected = $input['multiple_select_estabelecimentos'];
            }

            $ufselected = array();
            if (!empty($input['multiple_select_uf'])) {
                $ufselected = $input['multiple_select_uf'];
            }
            

            $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->lists('codigo','id');
            $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->lists('UF','UF');

            if (empty($input['inicio']) || empty($input['fim'])) {
                return redirect()->back()->with('status', 'É necessário informar as duas datas.');
            }
            $data_inicio = $input['inicio'].' 00:00:00';
            $data_fim = $input['fim'].' 23:59:59';
            
            $sql = "SELECT A.*, B.codigo, B.empresa_id FROM guiaicms A INNER JOIN estabelecimentos B on replace(replace(replace(A.CNPJ,'-',''),'/',''), '.', '') = B.cnpj WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."'";
            
            if (!empty($input['multiple_select_estabelecimentos'])) {
                $sql .= " AND replace(replace(replace(A.CNPJ,'-',''),'/',''), '.', '') IN (Select cnpj FROM estabelecimentos where id IN (".implode(',', $input['multiple_select_estabelecimentos'])."))";
            }

            if (!empty($input['multiple_select_uf'])) {
                $sql .= " AND A.UF IN (".implode(',', array_map(function($value){
                    return "'$value'";
                }, $input['multiple_select_uf'])).")";
            }

            $dados = json_decode(json_encode(DB::Select($sql)),true);
            $planilha = array();
            foreach ($dados as $key => $dado) {
                if ($dado['empresa_id'] == $this->s_emp->id) {
                    $planilha[] = $dado;
                }
            }

            foreach ($planilha as $chave => $valorl) {
                if ($valorl['MULTA_MORA_INFRA'] == 0) {
                    $planilha[$chave]['MULTA_MORA_INFRA'] = '0.00';
                }

                if ($valorl['HONORARIOS_ADV'] == 0) {
                    $planilha[$chave]['HONORARIOS_ADV'] = '0.00';
                }

                if ($valorl['ACRESC_FINANC'] == 0) {
                    $planilha[$chave]['ACRESC_FINANC'] = '0.00';
                }

                if ($valorl['JUROS_MORA'] == 0) {
                    $planilha[$chave]['JUROS_MORA'] = '0.00';
                }

                if ($valorl['MULTA_PENAL_FORMAL'] == 0) {
                    $planilha[$chave]['MULTA_PENAL_FORMAL'] = '0.00';
                }
            }

            $valorData = $data_fim;
            $data_vencimento_2 = str_replace('-', '/', $valorData);
            $data_fim = date('dmY', strtotime($data_vencimento_2));

            $valorData2 = $data_inicio;
            $data_vencimento = str_replace('-', '/', $valorData2);
            $data_inicio = date('dmY', strtotime($data_vencimento));   
            $mensagem = 'Período carregado com sucesso';
            if (empty($dados)) {
                $mensagem = 'Não há dados nesse período';
            }

            if (!empty($planilha)) {
                foreach ($planilha as $key => $value) {
                    $dataven = $value['DATA_VENCTO'];
                    $data_vencimento2 = str_replace('-', '/', $dataven);
                    $dataven2 = date('d/m/Y', strtotime($data_vencimento2));
                    $planilha[$key]['DATA_VENCTO'] = $dataven2;
                    $planilha[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
                    $planilha[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
                    $planilha[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
                    $planilha[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
                    $planilha[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
                    $planilha[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
                    $planilha[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
                }
            }

            return view('guiaicms.conferencia')->withUf($uf)->withEstabelecimentos($estabelecimentos)->with('planilha', $planilha)->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('mensagem', $mensagem)->withestabelecimentosselected($estabelecimentosselected)->withufselected($ufselected);
        }

    return view('guiaicms.conferencia')->withEstabelecimentos($estabelecimentos)->withUf($uf)->withestabelecimentosselected($estabelecimentosselected)->withufselected($estabelecimentosselected);
    }

    private function maskMoeda($valor)
    {
        $string = '';
        if (!empty($valor)) {
            $string = number_format($valor,2,",",".");
        }

        return $string;
    }

    public function jobAtividades()
    {
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:') {
            $path = 'W:';
        }
        $path .= '/storagebravobpo/';
        $arquivos = scandir($path);

        $data = array();
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                $data[$k]['arquivos'][1][1] = scandir($path_name.'/entregar');
                $data[$k]['arquivos'][1][2]['path'] = $path_name.'entregar/';
            }
        }

        CriticasEntrega::NoDuplicity();
        foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $pastas) {
                foreach ($pastas as $key => $arquivos) {
                    if (is_array($arquivos[1])) {
                        foreach ($arquivos[1] as $A => $arquivo) {
                            if (strlen($arquivo) > 2) {
                                $arrayNameFile = explode("_", $arquivo);
                                if (empty($arrayNameFile[2])) {
                                    continue;
                                }
                                $files[] = $arquivos[2]['path'].$arquivo;
                            }
                        }
                    }
                }
            }
        }              

        if (!empty($files)) {
            $this->savefiles($files);
        } else {
            echo "Não foram encontrados arquivos para realizar o processo.";exit;
        }

        $cmd = 'C:\wamp\bin\php\php7.0.10\php.exe C:\wamp\www\agenda\public\Background\UploadMails.php';
        if (substr(php_uname(), 0, 7) == "Windows"){ 
            pclose(popen("start /B " . $cmd, "r"));  
        } else { 
                exec($cmd . " > /dev/null &");   
        } 
        $this->clearEmptyPaths($files);
        echo "Job foi rodado com sucesso.";exit;
    }
    private function clearEmptyPaths($paths)
    {
        $clear = array();
        if (!empty($paths)) {
            foreach ($paths as $k => $path) {
                if (is_dir($path) && !is_file($path)) {
                    $a = scandir($path);
                    if (count($a) == 2) {
                        $clear[] = $path;
                    }
                }
            }
        }

        if (!empty($clear)) {
            foreach ($clear as $key => $valuetoclear) {
                @rmdir($valuetoclear);
            }
        }
    }

    private function savefiles($files){
        $arr = array();
        foreach ($files as $K => $file) {
            $arquivo = explode('/', $file);
            foreach ($arquivo as $k => $fileexploded) {
            }

            $empresaraiz = explode('_', $arquivo[2]);
            $empresacnpjini = $empresaraiz[1];
            
            $empresaraizid = 0;
            $empresaRaizBusca = DB::select('select id from empresas where LEFT(cnpj, 8)= "'.$empresacnpjini.'"');
            if (!empty($empresaRaizBusca[0]->id)) {
                $empresaraizid = $empresaRaizBusca[0]->id;
            }

            $arrayExplode = explode("_", $fileexploded);

            $AtividadeID = 0;
            if (!empty($arrayExplode[0])) 
                $AtividadeID = $arrayExplode[0];

            $CodigoEstabelecimento = 0;
            if (!empty($arrayExplode[1])) 
                $CodigoEstabelecimento = $arrayExplode[1];

            $NomeTributo = '';
            if (!empty($arrayExplode[2])) 
                $NomeTributo = $arrayExplode[2];
            if ($empresaraizid == 7) {
                $NomeTributo = $this->letras($NomeTributo);
            }

            $PeriodoApuracao = '';
            if (!empty($arrayExplode[3])) 
                $PeriodoApuracao = $arrayExplode[3]; 

            $UF = '';
            if (!empty($arrayExplode[4])) 
                $UF = substr($arrayExplode[4], 0, 2); 
            $estemp_id = 0;
            $arrayEstempId = DB::select('select id FROM estabelecimentos where codigo = "'.$CodigoEstabelecimento.'" and ativo = 1 and empresa_id ='.$empresaraizid.'');
            if (!empty($arrayEstempId[0]->id)) {
                $estemp_id = $arrayEstempId[0]->id;
            }

            // if (!$this->validatePasta($AtividadeID, $CodigoEstabelecimento, $NomeTributo, $PeriodoApuracao, $UF)) {
            //     $this->createCriticaEntrega($empresaraizid, $estemp_id, 8, $fileexploded, 'Nome do arquivo invalido', 'N');
            //     continue;
            // }
            
            $NomeTributo = $this->LoadNomeTributo($NomeTributo);
            if (!$this->checkTributo($NomeTributo)) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, 8, $fileexploded, 'Tributo não existente', 'N');
                continue;
            }

            $IdTributo = $this->loadTributo($NomeTributo);
            $validateAtividade = DB::select("Select COUNT(1) as countAtividade FROM atividades where id = ".$AtividadeID); 
            if (empty($AtividadeID) || !$validateAtividade[0]->countAtividade) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Código de atividade não existe', 'N');
                continue;
            }
            
            if (!$this->checkTribAtividade($AtividadeID, $IdTributo)) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Tributo divergente do tributo da atividade', 'N');
                continue;
            }

            $validateCodigo = DB::select("Select COUNT(1) as countCodigo FROM atividades where id = ".$AtividadeID. " AND estemp_id = ".$estemp_id);
            if (!$estemp_id || !$validateCodigo[0]->countCodigo) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Filial divergente com a filial da atividade', 'N');
                continue;
            }

            if (strlen($PeriodoApuracao) == 10) {
                $PeriodoApuracao = substr($PeriodoApuracao, 0, -4);
            }
            $validatePeriodoApuracao = DB::select("Select COUNT(1) as countPeriodoApuracao FROM atividades where id = ".$AtividadeID. " AND periodo_apuracao = ".$PeriodoApuracao."");
            if (empty($PeriodoApuracao) || !$validatePeriodoApuracao[0]->countPeriodoApuracao) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Período de apuração diferente do período da atividade', 'N');
                continue;
            }

            if (count($arrayExplode) >= 4) {
                $validateUF = DB::select("select count(1) as countUF FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = ".$estemp_id.") AND uf = '".$UF."'");
                if (empty($UF) || !$validateUF[0]->countUF) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'UF divergente da UF da filial da atividade', 'N');
                    continue;
                }
            }
            
            if (!$this->checkSubPath($file)) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Erro existe subpasta, eliminar a subpasta para a entrega', 'N');
                continue;
            }

            $return = $this->validateGeral($file, $AtividadeID);
            if (!is_numeric($return)) {
                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Está faltando o arquivo com extensão '.$return, 'N');
                continue;
            }
            if ($IdTributo == 1) {
                if (!$this->validateGeral($file, $AtividadeID, false, false, true)) {
                    $existsTXT = $this->validateGeral($file, $AtividadeID, true);
                    if ($existsTXT) {
                        $checkTXTvalue_read = $this->checkTXTvalue($file, $AtividadeID);
                        if ($checkTXTvalue_read == 'error-read') {
                            $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Contém arquivos TXT ou PDF que não atende o lay-out de leitura.', 'N');
                            continue;
                        }

                        $checkTXTvalue = $this->checkTXTvalue($file, $AtividadeID);
                        if (!is_numeric($checkTXTvalue)) {
                            $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'CNPJ do TXT '.$checkTXTvalue.' não confere com CNPJ da filial da atividade.', 'N');
                            continue;
                        }

                        $checkTXTvalue_2 = $this->checkTXTvalue($file, $AtividadeID, true);
                        if (!is_numeric($checkTXTvalue_2)) {
                           $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'PERÍODO do TXT '.$checkTXTvalue_2.' não confere com Período da atividade.', 'N');
                           continue;
                        }

                        $existsPDF = $this->validateGeral($file, $AtividadeID, false, true);
                        if ($existsPDF) {

                            $checkPDFvalue_read = $this->checkPDFvalue($file, $AtividadeID);
                            if ($checkPDFvalue_read == 'error-read') {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Não foi possível ler o arquivo '.$fileexploded, 'N');
                                continue;
                            }

                            $checkPDFvalue = $this->checkPDFvalue($file, $AtividadeID);
                            if (!is_numeric($checkPDFvalue)) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Aprovação: Existem mais de um arquivo PDF, não é possível identificar qual dos arquivos é o recibo.', 'N');
                                continue;
                            }

                            $checkPDFvalue_2 = $this->checkPDFvalue($file, $AtividadeID, true);
                            if (!is_numeric($checkPDFvalue_2)) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'CNPJ do Recibo não confere com CNPJ da filial da atividade.', 'N');
                                continue;
                            }

                            $checkPDFvalue_3 = $this->checkPDFvalue($file, $AtividadeID, false, true);
                            if (!is_numeric($checkPDFvalue_3)) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Período do Recibo não confere com o período da atividade.', 'N');
                                continue;
                            }

                            $this->checkPDFvalue($file, $AtividadeID, false, false, true);
                        } 
                    }     
                }
            }
            
            $arr[$AtividadeID][$K]['filename'] = $fileexploded;
            $arr[$AtividadeID][$K]['path'] = $file;
            $arr[$AtividadeID][$K]['atividade'] = $AtividadeID;   
        }

        if (!empty($arr)) {
            foreach ($arr as $k => $singlearray) {
                $path = $k.'.zip';
                $this->createZipFile($singlearray, $path);
            }
        }
    }   

    private function checkSubPath($file)
    {
        if (!is_dir($file)) {
            return true;
        }
        
        $scandir = scandir($file);
        foreach ($scandir as $x => $filename) {
            if (strlen($filename) > 2) {
                if (!is_dir($file.'/'.$filename)) {
                    continue;
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    private function checkTXTvalue($file, $id, $periodo = false)
    {
        if (is_dir($file)) {
            $formated = array();
            $files = scandir($file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (strtolower($exp[1]) == 'txt') {
                        $formated[$counter]['path'] = $file.'/'.$k;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }
        }

        if (!is_dir($file)) {
            $formated = array();
            $files = $this->getFilesByAtividadeId($id, $file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (strtolower($exp[1]) == 'txt') {
                        $formated[$counter]['path'] = $file;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }
        }
        
        $atividade = Atividade::findOrFail($id);
        if (!empty($formated)) {
            foreach ($formated as $single_key => $single_formated) {
                $handle = fopen($single_formated['path'], "r");
                if (filesize($single_formated['path']) == 0) {
                    return 'error-read';    
                }

                $contents = fread($handle, filesize($single_formated['path']));
                if (empty($contents)) {
                    return 'error-read';    
                }

                $exploded_rows = explode("\n", utf8_encode($contents));
                if (count($exploded_rows) < 10) {
                    return 'error-read';
                }

                //debug1
                //echo "<Pre>";
                //print_r($exploded_rows);exit;

                if ($atividade->regra->tributo->id == 1) {

                    //Modelo 1 - TXT
                    $exploded_column = explode("|", $exploded_rows[0]);
                    if (count($exploded_column) > 1) {
                        if ($periodo) {
                            if (substr($exploded_column[5], -6) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($exploded_column[7] != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    } 

                    //Modelo 2 - TXT
                    if (isset($exploded_rows[12]) && substr($exploded_rows[12], 0,8) == 'CNPJ/CPF') {
                        $exp_1 = explode(' ', $exploded_rows[12]);
                        $cnpj_v = $this->numero($exp_1[1]); 
                        $exp_2 = explode(' ', $exploded_rows[18]);
                        $periodo_v = substr($exp_2[1], 3); 
                        if ($periodo) {
                            if ($this->numero($periodo_v) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($cnpj_v != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    }

                    //Modelo 3 - TXT
                    if (isset($exploded_rows[10]) && substr($exploded_rows[10], 0,8) == 'CNPJ/CPF') {
                        $exp_1 = explode(' ', $exploded_rows[10]);
                        $cnpj_v = $this->numero($exp_1[1]); 
                        $exp_2 = explode(' ', $exploded_rows[16]);
                        $periodo_v = substr($exp_2[1], 3);
                        if ($periodo) {
                            if ($this->numero($periodo_v) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($cnpj_v != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    }
                }   
                fclose($handle);
            }
        }

        return '1';
        
    }

    private function checkPDFvalue($file, $id, $cnpj = false, $periodo = false, $save = false)
    {
        if (is_dir($file)) {
            $formated = array();
            $files = scandir($file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file.'/'.$k;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }

            $atividade = Atividade::findOrFail($id);
            $pdf = $this->readRecibo($formated[0]['path'], $save, $id);
            if (!$pdf) {
                return 'error-read';
            }

            if ($atividade->regra->tributo->id == 1) {
                if ($periodo) {
                    if ($pdf['periodo_apuracao'] != $atividade->periodo_apuracao) {
                        return 'error';
                    }
                }

                if ($cnpj) {
                    if ($pdf['cnpj'] != $atividade->estemp->cnpj) {
                        return 'error';
                    }
                }

                if (count($formated) > 1) {
                    return 'error';
                }
            }
        }

        if (!is_dir($file)) {
            $formated = array();
            $files = $this->getFilesByAtividadeId($id, $file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }

            $atividade = Atividade::findOrFail($id);
            $pdf = $this->readRecibo($formated[0]['path'], $save, $id);
            if (!$pdf) {
                return 'error-read';
            }

            if ($atividade->regra->tributo->id == 1) {
                if ($periodo) {
                    if ($pdf['periodo_apuracao'] != $atividade->periodo_apuracao) {
                        return 'error';
                    }
                }

                if ($cnpj) {
                    if ($pdf['cnpj'] != $atividade->estemp->cnpj) {
                        return 'error';
                    }
                }

                if (count($formated) > 1) {
                    return 'error';
                }
            }
        }

        return '1';
    }

    private function readRecibo($path, $save = false, $idAtividade = false)
    {
        $funcao = 'pdftotext.exe ';
        
        $filetxt = str_replace('.pdf', '.txt', $path);
        
        $caminho1 = explode('/', $filetxt);
        $caminho1_result = '';
        foreach ($caminho1 as $key => $value) {
            $arquivonome = $value;
            $key++;
            if (isset($caminho1[$key])) {
                $caminho1_result .= $value.'/';
            }
        }
        $caminho1_result = $caminho1_result.$arquivonome;
        $A = shell_exec($funcao.$path.' '.$caminho1_result);

        $arr = array();
        $arr['arquivotxt'] = $arquivonome; 
        $arr['pathtxt'] = $caminho1_result;
        
        $handle = fopen($arr['pathtxt'], "r");
        $contents = fread($handle, filesize($arr['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $dados = array();
        $fill = array();

        $atividade = Atividade::FindOrFail($idAtividade);

        preg_match('~cnpj/cpf:([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(" ", trim($match[1]));
            $dados['cnpj'] = trim($this->numero($i[0]));
        }

        preg_match('~periodo:([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode(" ", trim($match[1]));
            $a = explode("\n", trim($i[0]));
            $dados['periodo_apuracao'] = substr($this->numero($a[0]), -6);
        }

        if (empty($dados)) {
            return false;
        }
        
        //Debug2 de recibo
        //echo "<PrE>";
        //print_r($dados);exit;

        preg_match('~periodo de apuracao valor total dos debitos por saidas e prestacoes com debito do imposto valor total dos creditos por entradas e aquisicoes com credito do imposto valor total do icms a recolher valor total do saldo credor a transportar para o periodo seguinte valor recolhidos ou a recolher, extra-apuracao
([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            //Modelo PDF 1 
            $a = explode(" ", trim($i[0]));
            if (count($a) > 11 && !empty($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[12]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[12]));
            } 

            //modelo PDF 2
            $p = explode(" ", $i[1]);
            if (count($a) == 7) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $p[4]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $p[4]));
            }

            //Modelo PDF 3
            if (count($a) > 11 && !isset($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));
            } 

            //Modelo PDF 4
            $q = explode(" ", $i[2]);
            if (count($a) == 5 && count($q) > 1) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $q[1]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $q[2]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $q[1]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $q[2]));
            }

            //Modelo PDF 5
            if (count($a) > 11 && !empty($a[11]) && !isset($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));
            }
            
            //Modelo PDF 6
            if (isset($fill['vlr_recibo_5']) && $fill['vlr_recibo_5'] == 'r$') {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $p[5]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $p[1]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $p[3]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $p[5]));
            }

            if (empty($fill)) {
                return false;
            }

            foreach ($fill as $x => $index) {
                $valid = $this->numero($index);
                if (!is_numeric($valid)) {
                    return false;    
                }
            }
            
            $fill['data_aprovacao'] = date('Y-m-d H:i:s');
            $fill['status'] = 3;
            $fill['usuario_aprovador'] = 112;
            //debug3
            //echo "<PrE>";
            //print_r($fill);exit;
        } else {
            return false;
        }

        if ($save) {

            $query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = " .$atividade->regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$atividade->estemp->id. " AND B.Regra_geral = 'N') limit 1";

            $retornodaquery = DB::select($query);

            $sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = " .$atividade->regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND B.Regra_geral = 'S') limit 1";
            
            $queryGeral = DB::select($sql);

            $idanalistas = $retornodaquery;
            if (empty($retornodaquery)) {
                $idanalistas = $queryGeral;   
            }
            $fill['usuario_aprovador'] = '';
            if (!empty($idanalistas)) {
                foreach ($idanalistas as $k => $analista) {
                    $fill['usuario_aprovador'] = $analista->id;
                }
            }

            $fill['usuario_aprovador'] = 112;
            $fill['data_aprovacao'] = date('Y-m-d H:i:s');
            
            $atividade->fill($fill); 
            $atividade->save(); 
        }

        fclose($handle);
        unlink($arr['pathtxt']);
        return $dados;
    }

    private function validateGeral($file, $id, $checkTXT = false, $checkPDF = false, $checkDOC = false)
    {
        $validations = array();
        $atividade = Atividade::findOrFail($id);
        $loadExtensoes = EntregaExtensao::Where('tributo_id', $atividade->regra->tributo->id)->get()->toarray();
       
        if (!empty($loadExtensoes)) {
            foreach ($loadExtensoes as $x => $k) {
                $validations[strtolower($k['extensao'])] = false;
            }    
        }
        if (is_dir($file)) {
            //inicia validação de pasta
            $file_extensions = array();                 
            $validation = scandir($file);
            if (!empty($validation)) {
                foreach ($validation as $kk => $value_value) {
                    if (strlen($value_value) > 2) {
                        $exp = explode('.',$value_value);
                        if (isset($exp[1])) {
                            $file_extensions[] = strtolower($exp[1]);
                        }
                    }        
                }    
            }

            if (!empty($file_extensions)) {
                foreach ($file_extensions as $x => $valid) {

                    if ($checkTXT) {
                        if ($valid == 'txt') {
                            return true;
                        }
                    }
                    if ($checkPDF) {
                        if ($valid == 'pdf') {
                            return true;
                        }
                    }
                    if ($checkDOC) {
                        if (substr($valid, 0,3) == 'doc') {
                            return true;
                        }
                    }

                    if (isset($validations[$valid]) && empty($validations[$valid])) {
                        $validations[$valid] = true;
                    }
                }
            }
        }

        if (!is_dir($file)) {
            //inicia validação de pasta geral
            $allfiles = $this->getFilesByAtividadeId($atividade->id, $file);
            $file_extensions = array();                 

            $validation = $allfiles;
            if (!empty($validation)) {
                foreach ($validation as $kk => $value_value) {
                    $exp = explode('.',$value_value);
                    if (isset($exp[1])) {
                        $file_extensions[] = strtolower($exp[1]);
                    }
                }    
            }

            if (!empty($file_extensions)) {
                foreach ($file_extensions as $x => $valid) {

                    if ($checkTXT) {
                        if ($valid == 'txt') {
                            return true;
                        }
                    }
                    if ($checkPDF) {
                        if ($valid == 'pdf') {
                            return true;
                        }
                    }
                    if ($checkDOC) {
                        if (substr($valid, 0,3) == 'doc') {
                            return true;
                        }
                    }

                    if (isset($validations[$valid]) && empty($validations[$valid])) {
                        $validations[$valid] = true;
                    }
                }
            }
        }

        if ($checkPDF || $checkTXT || $checkDOC) {
            return false;
        }

        $retorno = 1;
        if (!empty($validations)) {
            foreach ($validations as $x => $index_true) {
                if (!$index_true) {
                    $retorno = $x;
                }
            }
        }

        return $retorno;
    }

    private function getFilesByAtividadeId($id, $file)
    {
        $explode = explode('/', $file);
        $path = '';
        foreach ($explode as $k => $way) {
            $path.= $way.'/';
            if ($way == 'entregar') {
                break;    
            }
        }
        $path = substr($path, 0,-1);
        $files_formated = array();
        $allFiles = scandir($path);
        foreach ($allFiles as $single_index => $single_file) {
            $directory = $path.'/'.$single_file;
            if (strlen($single_file) > 2 && $directory != $file && !is_dir($directory)) {
                $detalhamento = explode('_', $single_file);
                if ($detalhamento[0] == $id) {
                    $files_formated[] = $directory;
                }
            }
        }
        $files_formated[] = $file;

        return $files_formated;
    }

    private function loadTributo($tributo_nome)
    {
        $tributo = Tributo::where('nome', $tributo_nome)->first();
        return $tributo->id;
    }

    private function LoadNomeTributo($nomeTributo)
    {
        $nomeTributo = $this->letras($nomeTributo);
        if ($nomeTributo == "SPEDFISCAL") {
           return "SPED FISCAL";
        }
        if ($nomeTributo == "EFD") {
           return "EFD CONTRIBUIÇÕES";
        }
        if ($nomeTributo == "ICMSST") {
          return "ICMS ST";
        }
        if ($nomeTributo == "GIAST") {
          return "GIA ST";
        }
        if ($nomeTributo == "DCTFWEB") {
          return "DCTF WEB";
        }
        if ($nomeTributo == "LIVROFISCAL") {
          return "LIVRO FISCAL";
        }
        if ($nomeTributo == "DESONERACAO") {
          return "DESONERAÇÃO FOLHA";
        }
        return $nomeTributo;
    }

    private function checkTribAtividade($id_atividade, $id_tributo)
    {
        $atividade = Atividade::where('id', $id_atividade)->first();
        if ($atividade->regra->tributo_id == $id_tributo) {
            return true;
        }
        return false;
    }

    public function checkTributo($tributo)
    {
        $permission = DB::table('tributos')
            ->select('tributos.id')
            ->where('tributos.nome', '=', $tributo)
            ->get();

        if (empty($permission)) {
            return false;
        }
    
    return true;
    }

    private function validatePasta($atividade_id, $codigo_estabelecimento, $nome_tributo, $periodo_apuracao, $uf)
    {
        if (!is_numeric($atividade_id)) {
            return false;
        }

        if (!strlen($uf == 2) && is_numeric($uf)) {
            return false;
        }

        if (!strlen($periodo_apuracao) == 6 && !is_numeric($periodo_apuracao)) {
            return false;
        }

        if (strlen($codigo_estabelecimento) > 5) {
            return false;
        }

        $nm_tributo = $this->numero($nome_tributo);
        if (!empty($nm_tributo)) {
            return false;
        }
        
        return true;
    }

    public function createZipFile($f = array(),$fileName){
        $zip = new \ZipArchive();

        touch($fileName);
        $arrayDelete = array();
        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
            foreach ($f as $in => $name) {
                if (!is_file($name['path'])) {
                    $name['path'] = $name['path'].'/';
                    $name['filename'] = $name['filename'].'/';
                    
                    if(is_dir($name['path'])){
                        $arrayExtra = scandir($name['path']);
                        foreach ($arrayExtra as $M => $singlefile) {
                            if (strlen($singlefile) > 2) {
                                $extra_files[$M]['path'] = $name['path'].$singlefile;
                                $extra_files[$M]['filename'] = $singlefile;
                            }
                        }
                        
                        if(isset($extra_files)){
                            foreach ($extra_files as $keyExtra => $extra_file) {
                                if ($zip->addFile($extra_file['path'] , $extra_file['filename'])) {
                                    $destinoArray = explode('/', $extra_file['path']);
                                    $destino = '';
                                    foreach ($destinoArray as $key => $value) {
                                        $destino .= $value.'/';
                                        if ($key == 2) {
                                            break;
                                        }
                                    }
                                    $destino .= 'uploaded/';
                                    $arrayDelete['pasta'][$keyExtra]['path'] = $extra_file['path']; 
                                    $arrayDelete['pasta'][$keyExtra]['filename'] = $extra_file['filename']; 
                                    $arrayDelete['pasta'][$keyExtra]['pastaname'] = $name['filename']; 
                                    $arrayDelete['pasta'][$keyExtra]['destino'] = $destino;
                                    $arrayDelete['pasta'][$keyExtra]['raiz'] = $name['path'];
                                    $arrayDelete['pasta'][$keyExtra]['pasta'] = 1;
                                }
                            }                                                   
                        } else {
                            $name['path'] =substr($this->limpaWay($name['path']), 0, -1);
                            if ($this->checkDiretorio($name['path'])) {
                                @rmdir($name['path']);
                            }
                        }
                    }
                }

                if (is_file($name['path'])) {
                    if ($zip->addFile($name['path'] , $name['filename'])) {
                        $destinoArray = explode('/', $name['path']);
                        $destino = '';
                        foreach ($destinoArray as $key => $value) {
                            $destino .= $value.'/';
                            if ($key == 2) {
                                break;
                            }
                        }
                        $destino .= 'uploaded/';
                        $arrayDelete['pasta'][$in]['path'] = $name['path']; 
                        $arrayDelete['pasta'][$in]['filename'] = $name['filename']; 
                        $arrayDelete['pasta'][$in]['destino'] = $destino;
                        $arrayDelete['pasta'][$in]['pastaname'] = $name['filename'];
                    }
                }

            }
        }

        $zip->close();

        if (!empty($arrayDelete)) {
            foreach ($arrayDelete as $chave => $single) {
                if (is_array($single) && $chave === 'pasta') {
                   foreach ($single as $p => $mostsingle) {
                        
                        $creationpath = $mostsingle['destino'].$mostsingle['pastaname'];
                        $verifypath = str_replace('uploaded', 'entregar', $creationpath);
                        
                        if (!is_dir($creationpath) && !is_file($verifypath)) {
                            mkdir($creationpath, 0777);
                        }
                        
                        $currentFile = $creationpath;
                        if (!is_file($verifypath)) {
                            $creationpath = $creationpath.'/';
                            $currentFile = $creationpath.'/'.$mostsingle['filename'];
                        }
                        copy($mostsingle['path'], $currentFile);
                        unlink($mostsingle['path']);
                    }

                    if (isset($mostsingle['raiz'])) {
                        if ($this->checkDiretorio($mostsingle['raiz'])) {
                            @rmdir($mostsingle['raiz']);
                        }
                    }
                }
                if (!is_array($single)) {
                    copy($single['path'], $single['destino']);
                    unlink($single['path']);
                }
                
                if(is_array($single) && is_numeric($chave)){
                    copy($single['path'], $single['destino']);
                    unlink($single['path']);
                }
            }
        }

        if (file_exists($fileName)) {
            $data = ['image' => $fileName, 'atividade_id' => $name['atividade'], '_token' => csrf_token()];
            $this->upload($data);
        }
    }
    
    private function checkDiretorio($diretorio)
    {
       $verify = array();
       if (!empty($diretorio) && is_dir($diretorio)) {
           $scandir = scandir($diretorio);
           foreach ($scandir as $index => $pasta) {
               if (strlen($pasta) > 2) {
                   $verify[] = $pasta;
               }
           }

           if (!empty($verify)) {
               return false;
           }
           return true;
       }
    }

    private function limpaWay($way)
    {
        $anotherway = '';
        if(!empty($way)){
            $exploded = explode('/', $way);
            foreach($exploded as $index => $single){
                if(!empty($single)){
                    $anotherway .= $single;
                    if(is_file($anotherway)){
                        break;
                    } else {
                        $anotherway .= '/';
                    }
                }
            }
        }
        return $anotherway;
    }
    
    public function upload($data) {
        $file = array('image' => $data['image']);
        $rules = array('image' => 'required|mimes:pdf,zip'); 
        $validator = Validator::make($file, $rules);
        
        $atividade_id = $data['atividade_id'];
        $atividade = Atividade::findOrFail($atividade_id);
        $estemp = $atividade->estemp;
        $regra = $atividade->regra;
        $tipo = $regra->tributo->tipo;
        $tipo_label = 'UNDEFINED';
        switch($tipo) {
            case 'F':
                $tipo_label = 'FEDERAIS'; break;
            case 'E':
                $tipo_label = 'ESTADUAIS'; break;
            case 'M':
                $tipo_label = 'MUNICIPAIS'; break;
        }
            
        $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8);
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$estemp->cnpj;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }
    
        $destinationPath .= '/'.$tipo_label;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$regra->tributo->nome;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }
        
        $destinationPath .= '/'.$atividade->periodo_apuracao;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .='/';
        copy($data['image'], $destinationPath.$data['image']);
        unlink($data['image']);

        
        $query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$estemp->id. " AND B.Regra_geral = 'N') limit 1";

        $retornodaquery = DB::select($query);

        $sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND B.Regra_geral = 'S') limit 1";
        
        $queryGeral = DB::select($sql);
        
        $idanalistas = $retornodaquery;
        if (empty($retornodaquery)) {
            $idanalistas = $queryGeral;   
        }

        $user_aprovador = '';
        if (!empty($idanalistas)) {
            foreach ($idanalistas as $k => $analista) {
                $user_aprovador = $analista->id;
            }
        }

        if (empty($user_aprovador)) {
            $user_aprovador = 112;
        }

        $atividade->arquivo_entrega = $data['image'];
        $atividade->usuario_entregador = $user_aprovador;    
        $atividade->data_entrega = date("Y-m-d H:i:s");
        $atividade->status = 2;

        if($atividade->regra->tributo->id == 1 ) {
            $atividade->usuario_aprovador = 112;
            $atividade->data_aprovacao = date('Y-m-d H:i:s');
            $atividade->status = 3;
        }

        $atividade->save();
    }    
}

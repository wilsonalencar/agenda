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
        $this->eService = $service;
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
        //
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

                            if ($arrayNameFile[2] != 'ICMS' && $arrayNameFile[2] != 'DIFAL') {
                                continue;
                            }

                            $files[] = $FILENAME['path'].$arquivo;
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

        $mensagem = 'Concluído com sucesso';
        return view('guiaicms.job_return')->withMensagem($mensagem);
    }

    public function saveICMS($array)
    {
        $icms = array();

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
                $NomeTributo = $arrayExplode[2];

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

            $arqu = 'foo '.$value['arquivotxt'].' bar';    
            
            if (strpos($arqu, 'SP')) {
                $icms = $this->icmsSP($value);
            }

            if (strpos($arqu, 'RJ')) {
                $icms = $this->icmsRJ($value);   
            }

            if (strpos($arqu, 'RS')) {
                $icms = $this->icmsRS($value);
            }  

            if (strpos($arqu, 'AL')) {
                $icms = $this->icmsAL($value);
            }  

            if (strpos($arqu, 'DF')) {
                $icms = $this->icmsDF($value);
            }
            
            if (strpos($arqu, 'PA')) {
                $icms = $this->icmsPA($value);
            }

            if (strpos($arqu, 'GO')) {
                $icms = $this->icmsGO($value);
            }  
            
            // if (strpos($arqu, 'ES')) {
            //     $icms = $this->icmsES($value);
            // }
            
            // if (strpos($arqu, 'PB')) {
            //     $icms = $this->icmsPB($value);
            // }

            // if (strpos($arqu, 'SE')) {
            //     $icms = $this->icmsSE($value);
            // }
            
            // if (strpos($arqu, 'BA')) {
            //     $icms = $this->icmsBA($value);
            // }

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

            $validateTributo = DB::select("Select count(1) as countTributo from regras where id = (select regra_id from atividades where id = ".$AtividadeID.") and tributo_id = 8");
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
            
            if (!$this->validateEx($icms)) {
                continue;
            }
             

            if (!empty($icms['COD_RECEITA'])) {  
                $icms['COD_RECEITA'] = strtoupper($icms['COD_RECEITA']);
            }

            if (!empty($icms['UF'])) {  
                $icms['UF'] = strtoupper($icms['UF']);
            }

            Guiaicms::create($icms);
            $destino = str_replace('/imported', '', $value['path']);
            copy($destino, $value['path']);
            unlink($destino);
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
        
        //criando registro na tabela
        CriticasLeitor::create($array);
        
        //buscar email através de empresa e tributo
        $query = "select id FROM users where id IN (select id_usuario_analista FROM atividadeanalista where Tributo_id = ".$tributo_id." and Emp_id = ".$empresa_id.")";
        $emailsAnalista = DB::select($query);

        $codigoEstabelecimento = '';
        if ($estemp_id > 0) {
            $codigoEstabelecimentoArray = DB::select('select codigo FROM estabelecimentos where id = '.$estemp_id.' LIMIT 1 ');
            
            if (!empty($codigoEstabelecimentoArray[0])) {
                $codigoEstabelecimento = $codigoEstabelecimentoArray[0]->codigo;
            }
        }

        $tributo_nome = '';
        if ($tributo_id > 0) {
            $nomeTributoArray = DB::select('select nome FROM tributos where id = '.$tributo_id.' LIMIT 1 ');
            
            if (!empty($nomeTributoArray[0])) {
                $tributo_nome = $nomeTributoArray[0]->nome;
            }
        }
        
        //enviando email
        $now = date('d/m/Y');
        $subject = "Críticas e Alertas Leitor PDF em ".$now;
        $text = 'Empresa => '.$empresa_id.' Estabelecimento.Codigo => '.$codigoEstabelecimento.' Tributo => '.$tributo_nome.' Arquivo => '.$arquivo.' Critica => '.$critica.' importado => '.$importado;

        $data = array('subject'=>$subject,'messageLines'=>$text);
        
        if (!empty($emailsAnalista)) {
            foreach($emailsAnalista as $row) {
                $user = User::findOrFail($row->id);
                $this->eService->sendMail($user, $data, 'emails.notification-leitor-criticas', false);
            }
        }     
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
        
        //criando registro na tabela
        CriticasEntrega::create($array);
        //buscar email através de empresa e tributo
        $query = "select id FROM users where id IN (select id_usuario_analista FROM atividadeanalista where Tributo_id = ".$tributo_id." and Emp_id = ".$empresa_id.")";
        $emailsAnalista = DB::select($query);

        $codigoEstabelecimento = '';
        if ($estemp_id > 0) {
            $codigoEstabelecimentoArray = DB::select('select codigo FROM estabelecimentos where id = '.$estemp_id.' LIMIT 1 ');
            
            if (!empty($codigoEstabelecimentoArray[0])) {
                $codigoEstabelecimento = $codigoEstabelecimentoArray[0]->codigo;
            }
        }

        $tributo_nome = '';
        if ($tributo_id > 0) {
            $nomeTributoArray = DB::select('select nome FROM tributos where id = '.$tributo_id.' LIMIT 1 ');
            
            if (!empty($nomeTributoArray[0])) {
                $tributo_nome = $nomeTributoArray[0]->nome;
            }
        }
        
        //enviando email
        $now = date('d/m/Y');
        $subject = "Críticas e Alertas Entrega de arquivos em ".$now;
        $text = 'Empresa => '.$empresa_id.' Estabelecimento.Codigo => '.$codigoEstabelecimento.' Tributo => '.$tributo_nome.' Arquivo => '.$arquivo.' Critica => '.$critica.' importado => '.$importado;

        $data = array('subject'=>$subject,'messageLines'=>$text);
        
        if (!empty($emailsAnalista)) {
            foreach($emailsAnalista as $row) {
                $user = User::findOrFail($row->id);
                $this->eService->sendMail($user, $data, 'emails.notification-leitor-criticas', false);
            }
        }     
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

        $validate = DB::select($query);
        if (!empty($validate)) {
            return false;
        }

        return true;
    }

    public function icmsRS($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        

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
                $estabelecimento = Estabelecimento::findOrFail($atividade[0]['estemp_id']);
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
        return $icms;
    }



    public function icmsRJ($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8;

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

        preg_match('~apuracao \(debitos/creditos\) normal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['IE'] = str_replace(',', '.', trim(str_replace('.', '', $i[1])));
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
        return $icms;
    }

    public function icmsPA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 -correcao: 11 -acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
        
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = $this->numero($i[0]);
            $icms['REFERENCIA'] = $this->numero($i[1]);
            $lk = explode('
', $i[2]);
            $icms['IE'] = $this->numero($lk[0]);
            $valorData = $lk[1];
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));

            $icms['TAXA'] = str_replace(',', '.', str_replace('.', '',$i[5]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim(str_replace('r$', '', trim($i[7])))));
            $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[16]))));
        }

        preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        return $icms;
    }

    public function icmsPB($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

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

        preg_match('~29 - matricula([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
            $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
            $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[2])));
            $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[1])));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[3]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        return $icms;
    }

    public function icmsES($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        preg_match('~servico icms - comercio([^{]*)~i', $str, $match);        
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[4]);
            $icms['COD_RECEITA'] = $this->numero($a[1]);
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
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
        }

        preg_match('~documento unico de arrecadacao versao internet([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        return $icms;
    }

    public function icmsGO($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        preg_match('~inscricao estadual:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~documento de origem referencia 300-mensal -([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['REFERENCIA'] = trim($this->numero($i[0]));
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
        
        fclose($handle);
        return $icms;
    }

    public function icmsSE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        
        $icms['TRIBUTO_ID'] = 8;


        preg_match('~inscricao estadual / cpf / cnpj

numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

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
        
        preg_match('~codigo numerico linha digitavel([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
        }

        preg_match('~validade

valor total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        preg_match('~observacao([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);
            $icms['OBSERVACAO'] = '';
            foreach ($a as $key => $value) {
                if ($value == 'instrucoes') {
                    break;
                }
                $icms['OBSERVACAO'] .= $value.' ';
            }
        }
        
        fclose($handle);
        return $icms;
    }

    public function icmsBA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;


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
        return $icms;
    }

    public function icmsDF($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;

        preg_match('~15.juros - r\$ 16.outros - r\$ 17.valor total - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $k = 0;
            foreach ($i as $key => $value) {
                if ($value == '15.juros - r$ 16.outros - r$ 17.valor total - r$') {
                    $k = 1;
                }
                if ($k) {
                    $data[] = $value;
                }
            }

        if (!empty($data)) {
               $a = explode(' ', $data[2]);
               $icms['COD_RECEITA'] = $a[1];

               $l = explode(' ', $data[3]);
               $icms['REFERENCIA'] = $l[0];

               $valorData = $l[1];
               $data_vencimento = str_replace('/', '-', $valorData);
               $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
               
               $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$data[7]));
               $p = explode(' ', $data[6]);
               $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$p[0]));
               $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$p[1]));
               $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$p[2]));
               $icms['TAXA'] = str_replace(',', '.', str_replace('.', '',$p[3]));
           }   
        }
        
        $codbarras = '';
        preg_match('~aviso aos bancos : receber ate ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            foreach ($i as $key => $value) {
                if (is_numeric($this->numero($value)) && strlen($this->numero($value)) > 8) {
                    $codbarras .= $this->numero($value);
                }
                if ($key == 5) {
                    break;
                }
            }
            $codbarras = str_replace('-', '', str_replace(' ', '', substr($codbarras, 0,-2)));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        fclose($handle);
        return $icms;
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
        $estabelecimento = Estabelecimento::findOrFail($atividade->estemp_id);
        $icms['CNPJ'] = $estabelecimento->cnpj;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        preg_match('~caceal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] =str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($this->numero($i[0])))));
        }

        preg_match('~referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $a = explode('
', $i[0]);
            $icms['REFERENCIA'] =str_replace('-', '', str_replace('.', '', trim($a[0])));
        }

        preg_match('~vencimento principal cm desconto juros multa total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $a = explode(' ', $i[0]);

            $valorData = trim($a[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            
            $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[1])));
            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',trim($a[4])));
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',trim($a[5])));
            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[6])));
        }

        preg_match('~via - banco([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }
        echo "<PRe>";
        print_r($icms);exit;
        fclose($handle);
        return $icms;
    }


    public function icmsSP($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8;
        
        //razão social
        preg_match('~nome ou razao social
15([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($a[0]);
        }

        //uf
        preg_match('~uf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['UF'] = trim($i[0]);
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
        
        
        preg_match('~inscricao estadual
04([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $string = explode('
',trim($match[1]));
            $icms['IE'] = trim(str_replace('.', '', $string[0]));
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
        
        fclose($handle);
        return $icms;
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

        $sql = "Select DATE_FORMAT(A.Data_critica, '%d/%m/%Y') as Data_critica, B.codigo, C.nome, A.critica, A.arquivo, A.importado FROM criticasentrega A INNER JOIN estabelecimentos B ON A.Estemp_id = B.id INNER JOIN tributos C ON A.Tributo_id = C.id WHERE    A.Data_critica BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.Empresa_id = ".$this->s_emp->id." ";
    
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
            return redirect()->back()->with('status', 'É necessário informar as duas datas.');
        }
        $data_inicio = $input['inicio'].' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';
        
        $sql = "SELECT A.*, B.empresa_id, B.codigo, C.uf, D.centrocusto FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj inner join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.CODBARRAS <> ''"; 


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

    private function maskMoeda($valor)
    {
        $string = '';
        if (!empty($valor)) {
            $string = number_format($valor,2,",",".");
        }

        return $string;
    }

    // inicio job de atividades
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
                            
                            if ($this->validateTributo($arrayNameFile[2])) {
                                continue;
                            }

                            $files[] = $FILENAME['path'].$arquivo;
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
    
        echo "Job foi rodado com sucesso.";exit;
    }

    private function validateTributo($tributo)
    {
        $permission = DB::table('tributoleitorpdf')
            ->join('tributos', 'tributoleitorpdf.Tributo_id', '=', 'tributos.id')
            ->select('tributoleitorpdf.id')
            ->where('tributoleitorpdf.leitorpdf', '=', 'S')
            ->where('tributos.nome', '=', $tributo)
            ->get();

        if (empty($permission)) {
            return true;
        }
    
    return false;
    }

    private function savefiles($files){
        $arr = array();
        foreach ($files as $K => $file) {
            $arquivo = explode('/', $file);
            foreach ($arquivo as $k => $fileexploded) {
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

            $validateAtividade = DB::select("Select COUNT(1) as countAtividade FROM atividades where id = ".$AtividadeID); 
            if (empty($AtividadeID) || !$validateAtividade[0]->countAtividade) {
                $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'Código de atividade não existe', 'N');
                continue;
            }

            $validateCodigo = DB::select("Select COUNT(1) as countCodigo FROM atividades where id = ".$AtividadeID. " AND estemp_id = ".$estemp_id);
            if (!$estemp_id || !$validateCodigo[0]->countCodigo) {
                $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'Filial divergente com a filial da atividade', 'N');
                continue;
            }

            if ($this->checkTributo($NomeTributo)) {
                $validateTributo = DB::select("Select count(1) as countTributo from regras where id = (select regra_id from atividades where id = ".$AtividadeID.") and tributo_id = 8");
                if (!$validateTributo[0]->countTributo) {
                    $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'O Tributo ICMS não confere com o tributo da atividade', 'N');
                    continue;
                }
            }

            $validatePeriodoApuracao = DB::select("Select COUNT(1) as countPeriodoApuracao FROM atividades where id = ".$AtividadeID. " AND periodo_apuracao = '{$PeriodoApuracao}'");
            if (empty($PeriodoApuracao) || !$validatePeriodoApuracao[0]->countPeriodoApuracao) {
                $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'Período de apuração diverente do período da atividade', 'N');
                continue;
            }

            $validateUF = DB::select("select count(1) as countUF FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = ".$estemp_id.") AND uf = '".$UF."'");
            if (empty($UF) || !$validateUF[0]->countUF) {
                $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'UF divergente da UF da filial da atividade', 'N');
                continue;
            }

            $alertCentroCusto = DB::select("select count(1) countCentroCusto FROM centrocustospagto where estemp_id = ".$estemp_id." AND centrocusto <> '' AND centrocusto is not null");
            if (!$alertCentroCusto[0]->countCentroCusto) {
                $this->createCriticaEntrega(1, $estemp_id, 8, $fileexploded, 'Centro de custo não cadastrado', 'S');
            }

            $arr[$AtividadeID][$K]['filename'] = $fileexploded;
            $arr[$AtividadeID][$K]['path'] = $file;
            $arr[$AtividadeID][$K]['atividade'] = $AtividadeID;
        }

        if (!empty($arr)) {
            foreach ($arr as $k => $singlearray) {
                if (isset($singlearray['atividade'])) {
                    unset($singlearray['atividade']);
                }

                $date = time();
                $path = $date.'.zip';
                $this->createZipFile($singlearray, $path);    
            }
        }
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

    public function createZipFile($f = array(),$fileName){
        $zip = new \ZipArchive();
        touch($fileName);

        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
        foreach ($f as $in => $name) {
                $zip->addFile($name['path'] , $name['filename']);
            }
        }
        $zip->close();

        if (file_exists($fileName)) {
            $data = ['image' => $fileName, 'atividade_id' => $name['atividade'], '_token' => csrf_token()];
            $this->upload($data);
        }
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
            
        $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8).'/'.$estemp->cnpj.'/'.$tipo_label.'/'.$regra->tributo->nome.'/'.$atividade->periodo_apuracao;
        copy($data['image'], $destinationPath);
        unlink($data['image']);

        $query = "select id FROM users where id IN (select id_usuario_analista FROM atividadeanalista where Tributo_id = ".$regra->tributo->id." and Emp_id = ".$atividade->emp_id.") limit 1";
        $idanalistas = DB::select($query);
        
        if (!empty($idanalistas)) {
            foreach ($idanalistas as $k => $analista) {
                $atividade->Usuario_aprovador = $analista->id;
                $atividade->usuario_entregador = $analista->id;
            }
        }
        $atividade->arquivo_entrega = $data['image'];
        $atividade->data_entrega = date("Y-m-d H:i:s");
        $atividade->data_aprovacao = date("Y-m-d H:i:s");
        $atividade->status = 3;
        $atividade->save();
    }    
}

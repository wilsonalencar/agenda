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
use App\Models\Atividade;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;


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
            $path = $a[0];
        }
        $path .= '/doc_apuracao/';

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
                
                // shell_exec($funcao.$file.' '.$caminho1_result);
                $destino = str_replace('results', 'imported', str_replace('txt', 'pdf', $caminho1_result));

                $arr[$file]['arquivo'] = str_replace('txt', 'pdf', $arquivonome); 
                $arr[$file]['path'] = substr($destino, 0, -9); 
                $arr[$file]['arquivotxt'] = $arquivonome; 
                $arr[$file]['pathtxt'] = substr($caminho1_result, 0, -8);
                echo "<Pre>";
                print_r($arr);exit;
                copy($file, $destino);
                //unlink($file);
            }
        }
        // if (!empty($files)) {
            // $this->saveICMS($arr);
        // }

    echo "Nenhum arquivo foi encontrado disponível para salvar";
    }

    public function saveICMS($array)
    {
        $icms = array();
        foreach ($array as $key => $value) {
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

            if ($this->validateEx($icms)) {
                Guiaicms::create($icms);
            } 
        }
    echo "Dados Gravados com sucesso";exit;
    }

    public function validateEx($icms)
    {
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




    public function icmsSP($value)
    {
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
        $string = explode('
',trim($match[1]));
        if (!empty($match)) {
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
            $icms['INSCRICAO_ESTADUAL'] = trim(str_replace('.', '', $string[0]));
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function icms()
    {   
        return view('guiaicms.icms');
    }

    public function planilha(Request $request)
    {   
        $input = $request->all();
        if (empty($input['inicio']) || empty($input['fim'])) {
            return redirect()->back()->with('status', 'É necessário informar as duas datas.');
        }
        $data_inicio = $input['inicio'].' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';
        
        $sql = "SELECT A.*, B.empresa_id, B.codigo, C.uf, D.centrocusto FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj inner join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.CODBARRAS <> ''"; 

        $dados = json_decode(json_encode(DB::Select($sql)),true);

        $planilha = array();
        foreach ($dados as $key => $dado) {
            if ($dado['empresa_id'] == $this->s_emp->id) {
                $planilha[] = $dado;
            }
        }

        $sql_semcod = "SELECT A.*, B.empresa_id, B.codigo, C.uf, D.centrocusto, C.codigo_sap FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj left join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.CODBARRAS = ''"; 
        
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

        return view('guiaicms.icms')->with('planilha', $planilha)->with('planilha_semcod', $planilha_semcod)->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('mensagem', $mensagem);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
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

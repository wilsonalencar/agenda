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
        $emp = explode(' ', $this->s_emp->razao_social);
        $emp_cnpj = substr($this->s_emp->cnpj, 0,8);
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';
        if ($a[0] == 'C:' || $a[0] == 'F:') {
            $path = $a[0];
        }
        $path .= '/doc_apuracao/'.$emp[0].'_'.$emp_cnpj.'/';
        $arquivos = scandir($path);
        foreach ($arquivos as $X => $FILENAME) {
            if (substr($FILENAME, -3) == 'pdf') {
                $files[] = $FILENAME;
            }
        }

        if (!empty($files)) {
            foreach ($files as $K => $file) {
                $filetxt = str_replace('pdf', 'txt', $file);
                $pdftext = shell_exec('/usr/bin/pdftotext '.$path.$file.' '.$path.'results/'.$filetxt);
                $origem = $path.$file;
                $destino = $path.'imported/'.$file;
                $pathdestinotxt = $path.'results/'.$filetxt;

                $arr[$file]['arquivo'] = $file; 
                $arr[$file]['path'] = $destino; 
                $arr[$file]['arquivotxt'] = $filetxt; 
                $arr[$file]['pathtxt'] = $pathdestinotxt; 
                
                copy($origem, $destino);
                unlink($origem);
            }
        }
        if (!empty($files)) {
            $this->saveICMS($arr);
        }

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
        $icms['TRIBUTO_ID'] = 8;
        preg_match('~CNPJ/CPF/Insc. Est.:([^{]*)Endereço:~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($i[0]);
        }
        //cpf - cpnj
        preg_match('~CEP:([^{]*)Data de Vencimento~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = str_replace('.', '', trim($i[2]));
        }

        preg_match('~UF:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['UF'] = trim($i[0]);
        }
        
        preg_match('~Parcela([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['REFERENCIA'] = trim($i[0]);
        }        

        preg_match('~Código da Receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($i[2]);
        }

        preg_match('~Data de Vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $valorData = trim($i[0]);
            $data_vencimento = str_replace('/', '-', $valorData);
            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~Valor Principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = trim(str_replace('R$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0])))));
        }

        //juros
        preg_match('~Juros([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['JUROS_MORA'] = trim(str_replace('R$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0])))));
        }

        //multa
        preg_match('~Multa([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['MULTA_MORA_INFRA'] = trim(str_replace('R$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0])))));
        }

        //Atualização monetária
        preg_match('~Atualização Monetária([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['ACRESC_FINANC'] = trim(str_replace('R$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0])))));
        }

        preg_match('~Documento Válido para pagamento até([^{]*)1ª via - Banco~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = trim(str_replace('R$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0])))));
        }

        preg_match('~Informações Complementares:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['OBSERVACAO'] = trim($i[0]);
        }

        //código de barras
        preg_match('~1ª via - Banco([^{]*)Guia Nacional de Recolhimento de Tributos Estaduais - GNRE~i', $str, $match);
        if (!empty($match)) {
            $codbarras = str_replace('-', '', str_replace(' ', '', $match[1]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        //carregando cnpj, endereço e município
        $arr = explode('_', $value['arquivotxt']);
        if (!empty($arr)) {
            $atividadeID = $arr[0];
            $atividadeID = 7752;
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
        $icms['TRIBUTO_ID'] = 8;

        preg_match('~Nome/razão social:([^{]*)Endereço:~i', $str, $match);
        if (!empty($match)) {
            $icms['CONTRIBUINTE'] = trim($match[1]);
        }

        //cpf - cpnj
        preg_match('~CNPJ/CPF:([^{]*)Inscrição estadual/RJ:~i', $str, $match);
        if (!empty($match)) {
            $icms['CNPJ'] =str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($match[1]))));
        }

        //uf
        preg_match('~UF:([^{]*)CEP:~i', $str, $match);
        if (!empty($match)) {
           $icms['UF'] = trim($match[1]);
        }

        //referencia
        preg_match('~Período de Referência:([^{]*)Data Vencimento:~i', $str, $match);
        if (!empty($match)) {
            $icms['REFERENCIA'] = trim($match[1]);
        }
        
        //cod_receita
        preg_match('~Tipo de Pagamento:([^{]*)Data Pagamento:~i', $str, $match);
        if (!empty($match)) {
            $icms['COD_RECEITA'] = trim($match[1]);
        }

        //cod_receita
        preg_match('~INFORMAÇÕES COMPLEMENTARES([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $vlr = explode('
', trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $vlr[0])));
        }

        //juros de mora
//         preg_match('~JUROS DE MORA([^{]*)~i', $str, $match);
//         if (!empty($match)) {
//             $var = explode('
// ', trim($match[1]));
//             $icms['JUROS_MORA'] = str_replace(',', '.', trim(str_replace('.', '', $var[0])));
//         }

        //juros de mora
        // preg_match('~Multa de Mora:([^{]*)Multa de Mora:~i', $str, $match);
        // if (!empty($match)) {
        //     $icms['MULTA_PENAL_FORMAL'] = str_replace(',', '.', trim(str_replace('.', '', $match[1])));
        // }

        //juros de mora
        preg_match('~ICMS Atualizado:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $var2 = explode('
', trim($match[1]));
            if (trim($var2[0]) == 'Juros:') {
                $var2[0] = '';
            }
            $icms['OBSERVACAO'] = trim($var2[0]);
        }

        //código de barras
        preg_match('~Via BANCO([^{]*)
Governo do Estado do Rio de Janeiro~i', $str, $match);
        if (!empty($match)) {
            $codbarras = str_replace('-', '', str_replace(' ', '', $match[1]));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        //juros de mora
        preg_match('~O Contribuinte é responsável pelas informações contidas neste DARJ e por atualizar seus dados cadastrais junto à SEFAZ-RJ.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $var = explode('
', trim($match[1]));
            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $var[0]));
        }

        //juros de mora
        preg_match('~TOTAL A PAGAR([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $var = explode('
', trim($match[1]));
            $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $var[0])));
        }
        //cidade e endereço e data vencimento
        preg_match('~Data Vencimento:([^{]*)Informações Complementares:~i', $str, $match);
        if (!empty($match)) {
        $valorData = trim($match[1]);
        $data_vencimento = str_replace('/', '-', $valorData);
        $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        fclose($handle);
        return $icms;
    }




    public function icmsSP($value)
    {
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';

        $icms['TRIBUTO_ID'] = 8;
        //razão social
        preg_match('~15 ([^{]*)
16
~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $icms['CONTRIBUINTE'] = trim($a[0]);
        }

        //uf
        preg_match('~UF([^{]*)TELEFONE~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            foreach ($i as $k => $x) {
                if (!is_numeric($x) && strlen($x) == 2) {
                    $match[1] = $x;
                }
            }
            $icms['UF'] = trim($match[1]);
            $icms['MUNICIPIO'] = trim($i[0]);
        }
        //cpf - cpnj
        preg_match('~
CNAE
([^{]*)Inscrição na Dívida Ativa ou Nº da Etiqueta ou ID~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", $a[0]));
        }

        //referencia
        preg_match('~
07
([^{]*)Nº. AIIM ou Nº. DI ou Nº. PARCELAMENTO~i', $str, $match);
        if (!empty($match)) {
            $icms['REFERENCIA'] = trim($match[1]);
        }
        
        //referencia
        preg_match('~
21

([^{]*)Inscrição Estadual~i', $str, $match);
        if (!empty($match)) {
            $k = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = trim($k[0]);
        }
        //observacao
        preg_match('~06
([^{]*)REFERÊNCIA~i', $str, $match);
        if (!empty($match)) {
            $icms['OBSERVACAO'] = trim($match[1]);
        }

        //vlr_receita
        preg_match('~09
([^{]*)JUROS DE MORA~i', $str, $match);
        if (!empty($match)) {
            $i = explode("
", trim($match[1]));
            $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));;
        }

        //vlr_total
        preg_match('~Autenticação Mecânica

Honorários Advocatícios

13
Valor Total

14
([^{]*)

 bar~i', $str, $match);
        if (!empty($match)) {
            $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $match[1])));
        }

        //ie e cnae
        preg_match('~CNPJ ou CPF

([^{]*)
06
~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('

',$string);
        $icms['CNAE'] = $string[0];
        $string[2] = preg_replace("/[^0-9]/", "", $string[2]);
        $icms['INSCR_DIVIDA'] = str_replace('.', '', $string[2]);

        }

        //cidade e endereço e data vencimento
        preg_match('~Inscrição Estadual([^{]*)

03
~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('

',$string);
        $icms['ENDERECO'] = $string[0];
        
        $valorData = $string[2];
        $data_vencimento = str_replace('/', '-', $valorData);
        $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
        }

        preg_match('~Juros De Mora([^{]*)Multa de Mora ou Multa por Infração~i', $str, $match);
        if (!empty($match)) {
        $string = trim($match[1]);
        $string = explode('

',$string);
        $icms['JUROS_MORA'] =preg_replace("/[^0-9.]/", "", str_replace(',', '.', str_replace('.', '', $string[1])));
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
    public function store(Request $request)
    {   
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

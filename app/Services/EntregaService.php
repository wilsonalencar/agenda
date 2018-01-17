<?php
/**
 * Created by PhpStorm.
 * User: Silver
 * Date: 10/03/2016
 * Time: 11:28
 */

namespace App\Services;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Municipio;
use App\Models\Regra;
use App\Models\Tributo;
use App\Models\User;
use App\Models\Log;
use DB;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\FeriadoEstadual;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
//use Illuminate\Mail\Mailer;
//use Swift_Mailer;

class EntregaService {

    protected $notification_system;

    function __construct()
    {
        $this->notification_system = true; //ENABLED = true / DISABLED = false
    }

    public function calculaProximasEntregasEstemp($cnpj,$offset=null){

        $estemp = null;
        $isEmpresa = (substr($cnpj,8,4)=='0001');
        if ($isEmpresa) {
            $estemp = Empresa::where('cnpj', $cnpj)->first();
        } else {
            $estemp = Estabelecimento::where('cnpj', $cnpj)->first();
        }
        $cod_municipio = $estemp->cod_municipio;
        $uf = $estemp->municipio->uf;

        $param = null;

        if ($isEmpresa) {
            $param = array('cnpj'=>$estemp->cnpj,'IE'=>$estemp->insc_estadual);
            $regras = DB::table('regras')
                ->join('tributos', 'tributos.id', '=', 'regras.tributo_id')
                ->select('regras.*', 'tributos.nome AS tnome')
                ->where('ref', $uf)
                ->orWhere('ref','MATRIZ')
                ->orWhere('ref', $cod_municipio)
                ->get();
        } else {
            $param = array('cnpj'=>$estemp->empresa->cnpj,'IE'=>$estemp->empresa->insc_estadual);
            $regras = DB::table('regras')
                ->join('tributos', 'tributos.id', '=', 'regras.tributo_id')
                ->select('regras.*', 'tributos.nome AS tnome')
                ->where('ref', $uf)
                ->orWhere('ref', $cod_municipio)
                ->get();
        }
        $prox_entregas = array();
        foreach($regras as $regra) {

            $entrega = array();
            $nome_tributo = $regra->tnome;
            if ($regra->nome_especifico) {
                $nome_tributo .= ' ('.$regra->nome_especifico.')';
            }
            $adiant_fds = $regra->afds;

            if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

                $data = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega,$param, null, $offset,$adiant_fds);
                $entrega = array('desc'=>$nome_tributo,'data'=>$data[0]['data']);

            } else {
                $data = $this->calculaProximaData($regra->regra_entrega, null, $offset,$adiant_fds);
                $entrega = array('desc'=>$nome_tributo,'data'=>$data);
            }

            $prox_entregas[] = $entrega;
        }
        usort($prox_entregas, function ($a, $b) {
            if ($a['data'] == $b['data']) {
                return 0;
            }
            return ($a['data'] < $b['data']) ? -1 : 1;
        });

        return $prox_entregas;
    }

    public function calculaProximaData($regra, $periodo=null, $offset=null, $adiant_fds=true)
    {
        /* Attenzione - Manca considerare i giorni festivi!!! */

        $tipo_periodo = substr($regra,0,2);
        $valor_periodo = substr($regra,2,2);
        $tipo_dia = substr($regra,4,1);
        $val_sign = substr($regra,5,1);
        $val_dia = substr($regra,6,2);

        //Carbon::setLocale(LC_TIME,'pt_BR');
        Carbon::setTestNow();  //reset
        if ($periodo!=null) {
            $month = 1;
            if (strlen($periodo)>4) {
                $month = intval(substr($periodo, 0, 2));
            }
            $year = intval(substr($periodo,-4,4));
            Carbon::setTestNow(Carbon::createFromDate($year, $month, 1, 'America/Sao_Paulo'));
        }

        if ($tipo_periodo == 'MS') {
            if ($tipo_dia == 'F') {
                for ($i = 1; $i <= $valor_periodo; $i++) {
                    if ($val_sign=='+') {
                        Carbon::setTestNow(Carbon::parse('first day of next month')->startOfDay()->addDays($val_dia)->subHours(6));
                    } else {
                        Carbon::setTestNow(Carbon::parse('last day of next month')->startOfDay()->subDays($val_dia)->subHours(6));
                    }
                    if (Carbon::now()->isWeekEnd()){
                        if ($adiant_fds) {
                            Carbon::setTestNow(Carbon::parse('last friday'));
                        } else {
                            Carbon::setTestNow(Carbon::parse('next monday'));
                        }
                    }
                }
            } else if ($tipo_dia == 'U') {
                for ($i = 1; $i <= $valor_periodo; $i++) {
                    if ($val_sign=='+') {
                        Carbon::setTestNow(Carbon::parse('first day of next month')->startOfDay()->addWeekDays($val_dia)->subHours(6));
                    } else {
                        Carbon::setTestNow(Carbon::parse('last day of next month')->startOfDay()->subWeekDays($val_dia)->subHours(6));
                    }

                }
            }
        } else if ($tipo_periodo == 'QS') {

            $addQ = 15*($valor_periodo-1); //var_dump($addQ); var_dump($tipo_dia); var_dump($val_sign);
            //Estamos na primeira quinzena
            if ($tipo_dia == 'F') {
                if ($val_sign == '+') {
                    Carbon::setTestNow(Carbon::parse("first day of next month")->addDays($addQ+$val_dia-1)->startOfDay()->addHours(18));
                } else {
                    Carbon::setTestNow(Carbon::parse('first day of next month')->addDays($addQ+15-$val_dia)->startOfDay()->addHours(18));
                }
                if (Carbon::now()->isWeekEnd()){
                    if ($adiant_fds) {
                        Carbon::setTestNow(Carbon::parse('last friday'));
                    } else {
                        Carbon::setTestNow(Carbon::parse('next monday'));
                    }
                }
            } else if ($tipo_dia == 'U') {
                if ($val_sign == '+') {
                    Carbon::setTestNow(Carbon::parse("first day of next month")->addDays($addQ)->addWeekDays($val_dia-1)->startOfDay()->addHours(18));
                } else {
                    Carbon::setTestNow(Carbon::parse('first day of next month')->addDays($addQ+15)->subWeekDays($val_dia)->startOfDay()->addHours(18));
                }

            }

        } else if ($tipo_periodo == 'AS') {   //Somente para AS1DF+DDMM

            $val_mes = substr($regra,8,2);

            Carbon::setTestNow(Carbon::parse('first day of January next year')->startOfDay()->addMonths($val_mes-1)->addDays($val_dia)->subHours(6));

            if (Carbon::now()->isWeekEnd()){
                if ($adiant_fds) {
                    Carbon::setTestNow(Carbon::parse('last friday'));
                } else {
                    Carbon::setTestNow(Carbon::parse('next monday'));
                }
            }

        }

        if ($offset!=null){
            Carbon::setTestNow(Carbon::now()->subWeekDays($offset));
        }

        return Carbon::now()->endOfDay();
    }

    public function calculaProximaDataRegrasEspeciais($regra, $param=null, $periodo=null, $offset=null, $adiant_fds=true) {

        $retval_array = array();

        switch ($regra) {
            case 'RE01':    //GIA SP - $param = último dígito do número de Inscrição Estadual
                if ($param) {
                    $retval = null;
                    switch(substr($param['IE'],-1,1)) {
                        case '0':
                        case '1':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 0/1');
                            break;
                        case '2':
                        case '3':
                        case '4':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 2/3/4');
                            break;
                        case '5':
                        case '6':
                        case '7':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 5/6/7');
                            break;
                        case '8':
                        case '9':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 8/9');
                            break;
                    }
                    $retval_array[] = $retval;

                } else {   //Regra geral
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 0/1');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 2/3/4');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 5/6/7');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 8/9');
                }
                break;
            case 'RE02':    //ICMS - Livro Eletronico - DF - $param = 8 dígito do cnpj
                if ($param) {
                    $retval = null;
                    switch(substr($param['cnpj'],7,1)) {
                        case '0':
                        case '1':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+24",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 0/1');
                            break;
                        case '2':
                        case '3':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+25",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 2/3');
                            break;
                        case '4':
                        case '5':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+26",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 4/5');
                            break;
                        case '6':
                        case '7':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+27",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 6/7');
                            break;
                        case '8':
                        case '9':
                            $retval = array('data' => $this->calculaProximaData("MS1DF+28",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 8/9');
                            break;
                    }
                    $retval_array[] = $retval;

                } else {    //Regra geral
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+24",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 0/1');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+25",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 2/3');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+26",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 4/5');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+27",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 6/7');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+28",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 8/9');
                }
                break;
            case 'RE03':    //DIPAM SP - $param = último dígito do número de Inscrição Estadual
                if ($param) {
                    $retval = null;
                    switch(substr($param['IE'],-1,1)) {
                        case '0':
                        case '1':
                            $retval = array('data'=>$this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 0/1');
                            break;
                        case '2':
                        case '3':
                        case '4':
                            $retval = array('data'=>$this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 2/3/4');
                            break;
                        case '5':
                        case '6':
                        case '7':
                            $retval = array('data'=>$this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 5/6/7');
                            break;
                        case '8':
                        case '9':
                            $retval = array('data'=>$this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 8/9');
                            break;
                    }
                    $retval_array[] = $retval;

                } else {   //Regra geral
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 0/1');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 2/3/4');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 5/6/7');
                    $retval_array[] = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 8/9');
                }
                break;
        }

        return $retval_array;
    }

    public function getFeriadosNacionais($ano=null)
    {
        $formatoDataDeComparacao    =  "d-m"; // Dia / Mês
        //$diaDeComparacao            = date("d-m",strtotime($data));
        //$ano = intval(date('Y',strtotime($data)));
        if ($ano==null) $ano = date('Y');

        $pascoa = easter_date($ano); // Limite de 1970 ou após 2037 da easter_date PHP consulta http://www.php.net/manual/pt_BR/function.easter-date.php
        $dia_pascoa = date('j', $pascoa);
        $mes_pascoa = date('n', $pascoa);
        $ano_pascoa = date('Y', $pascoa);

        $feriados = array(
            // Tatas Fixas dos feriados Nacionail Basileiras
            'Confraternização Universal - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 1, 1, $ano)), // Confraternização Universal - Lei nº 662, de 06/04/49
            'Tiradentes - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 4, 21, $ano)), // Tiradentes - Lei nº 662, de 06/04/49
            'Dia do Trabalhador - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 5, 1, $ano)), // Dia do Trabalhador - Lei nº 662, de 06/04/49
            'Dia da Independência - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 9, 7, $ano)), // Dia da Independência - Lei nº 662, de 06/04/49
            'N. S. Aparecida - Lei nº 6802'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 10, 12, $ano)), // N. S. Aparecida - Lei nº 6802, de 30/06/80
            'Todos os santos - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 2, $ano)), // Todos os santos - Lei nº 662, de 06/04/49
            'Proclamação da republica - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 15, $ano)), // Proclamação da republica - Lei nº 662, de 06/04/49
            'Natal - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 12, 25, $ano)), // Natal - Lei nº 662, de 06/04/49

            // These days have a date depending on easter
            '2ºfeira Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 48, $ano_pascoa)),//2ºferia Carnaval
            '3ºfeira Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa)),//3ºferia Carnaval
            '6ºfeira Santa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa)),//6ºfeira Santa
            'Páscoa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa, $ano_pascoa)),//Pascoa
            'Corpus Christ'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa)),//Corpus Christ
        );

        return $feriados;
    }

    public function getFeriadosEstaduais()
    {
        $retval = FeriadoEstadual::all();

        //$feriados_estaduais = explode(';',$retval->first()->datas);

        return $retval;

    }

    public function sendMail($user_rec,$data,$content_page='emails.test') {
        
        if ($this->notification_system) {
            //$user_rec->email = 'anderson.usseda@innovative.com.br';
            // note, to use $subject within your closure below you have to pass it along in the "use (...)" clause.
            Mail::send($content_page, ['data' => $data, 'user' => $user_rec], function ($message) use ($data, $user_rec) {
                // note: if you don't set this, it will use the defaults from config/mail.php
                $message->from('no-reply-please@innovative.com.br', 'BravoTaxCalendar');
                $message->to($user_rec->email, $user_rec->name)->subject($data['subject']); //$user_rec->email
            });
        }
    }

    public function writeLog($description,$type='ADM') {
        $log = new Log();
        $log->user_id = Auth::user()->id;
        $log->description = $description;
        $log->type = $type;
        $log->save();
    }

    public function generateSingleCnpjActivities($periodo_apuracao,$cnpj,$codigo,$tributo_id) {
        // Single 'estabelecimento' generation for newly registered
        $generate = true;
        //
        $estab = Estabelecimento::where('cnpj',$cnpj)->where('codigo',$codigo)->firstOrFail();
        $empresa = Empresa::where('id',$estab->empresa_id)->firstOrFail();

        //Verifica existencia atividades
        if ($tributo_id==0) {
            $exists = Atividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->count();
        } else {
            $exists = Atividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->whereHas('regra.tributo', function ($query) use ($tributo_id) {
                $query->where('id', $tributo_id);
            })->count();
        }

        if ($exists >0 || $estab->ativo == 0) {
            $generate = false;
        }

        if ($generate) {
            //TODAS AS REGRAS ATIVAS
            $matchThese = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->uf];
            $orThose = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->codigo];
            //FILTRO TRIBUTO
            if ($tributo_id>0) {
                $matchThese['tributo_id']= $tributo_id;
                $orThose   ['tributo_id']= $tributo_id;
            }
            //FILTRO BLOQUEIO DE REGRA
            $blacklist = array();  //Lista dos estab (id) que não estão ativos para esta regra
            foreach($estab->regras as $el) {
                $blacklist[] = $el->id;
            }

            if (sizeof($blacklist)>0) {
                $regras = Regra::whereNotIn('id',$blacklist)->where($matchThese)->orWhere($orThose)->get();
            } else {
                $regras = Regra::where($matchThese)->orWhere($orThose)->get();
            }

            //GERAÇÂO
            $count = 0;
            foreach ($regras as $regra) {

                $trib = DB::table('tributos')
                    ->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
                    ->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
                    ->select('empresa_tributo.adiantamento_entrega')
                    ->where('tributos.id',$regra->tributo->id)
                    ->where('empresas.cnpj',$empresa->cnpj)
                    ->get();

                //VERIFICA ADIANTAMENTO DE ENTREGA
                $offset = null;
                if (!empty($trib[0]->adiantamento_entrega)) {
                    $offset = $trib[0]->adiantamento_entrega;
                }
                
                //VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
                $adiant_fds = $regra->afds;

                $val = array();
                // Regras Especiais
                if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

                    $param = array('cnpj'=>$estab->cnpj,'IE'=>$estab->insc_estadual);
                    $retval_array = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega,$param,$periodo_apuracao,$offset,$adiant_fds);

                    foreach ($retval_array as $el) {
                        $data_limite = $el['data']->toDateTimeString();
                        $alerta = intval($regra->tributo->alerta);
                        $inicio_aviso = $el['data']->subDays($alerta)->toDateTimeString();
                        $desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
                        $val = array(
                            'descricao' => $desc_prefix . $el['desc'],
                            'recibo' => $regra->tributo->recibo,
                            'status' => 1,
                            'periodo_apuracao' => $periodo_apuracao,
                            'inicio_aviso' => $inicio_aviso,
                            'limite' => $data_limite,
                            'tipo_geracao' => 'A',
                            'regra_id' => $regra->id
                        );

                    }

                } else {  // Regra standard

                    $ref = $regra->ref;
                    if ($municipio = Municipio::find($regra->ref)) {
                        $ref = $municipio->nome . ' (' . $municipio->uf . ')';
                    }
                    $nome_especifico = $regra->nome_especifico;
                    if (!$nome_especifico) {
                        $nome_especifico = $regra->tributo->nome;
                    }
                    $desc = $nome_especifico . ' ' . $ref;
                    $desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

                    $data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
                    $data_limite = $data->toDateTimeString();
                    $alerta = intval($regra->tributo->alerta);
                    $inicio_aviso = $data->subDays($alerta)->toDateTimeString();

                    $val = array(
                        'descricao' => $desc_prefix . $desc,
                        'recibo' => $regra->tributo->recibo,
                        'status' => 1,
                        'periodo_apuracao' => $periodo_apuracao,
                        'inicio_aviso' => $inicio_aviso,
                        'limite' => $data_limite,
                        'tipo_geracao' => 'A',
                        'regra_id' => $regra->id
                    );

                }

                //CRIA ATIVIDADE
                $val['estemp_type'] = 'estab';
                $val['estemp_id'] = $estab->id;
                $val['emp_id'] = $estab->empresa_id;

                $nova_atividade = Atividade::create($val);
                $count++;

            }

        }

        return $generate;

    }

    public function generateMonthlyActivities($periodo_apuracao,$cnpj_empresa) {
        // Activate auto activity generation
        $generate = true;
        //
        $empresa = Empresa::where('cnpj',$cnpj_empresa)->firstOrFail();

        if (Cron::where('periodo_apuracao', $periodo_apuracao)->where('emp_id', $empresa->id)->count() >0) {
            $generate = false;
        }
        //TODAS AS REGRAS ATIVAS PARA A EMPRESA SOLICITADA
        $empresa_tributos = $empresa->tributos()->get();
        $array_tributos_ativos = array();
        foreach($empresa_tributos as $at) {
            $array_tributos_ativos[] = $at->id;
        }
        //
        $regras = Regra::where('freq_entrega','M')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();

        if ($generate) {
            $count = 0;
            foreach ($regras as $regra) {

                //VERIFICA CNPJ QUE FORAM BANIDOS PARA ESTA REGRA
                $blacklist = array();
                foreach($regra->estabelecimentos as $el) {
                    $blacklist[] = $el->id;
                }

                //VERIFICA CNPJ QUE UTILIZAM A REGRA
                $ativ_estemps = array();
                if ($regra->tributo->tipo == 'F') { //Federal

                    $empresas = DB::table('empresas')
                        ->select('id', 'cnpj')
                        ->where('cnpj',$empresa->cnpj)
                        ->get();

                    $ativ_estemps = $empresas;

                } else if ($regra->tributo->tipo == 'E') { //Estadual

                    $ref = $regra->ref;

                    $empresas = DB::table('empresas')
                        ->select('empresas.cnpj', 'empresas.id', 'empresas.id', 'municipios.uf', 'municipios.nome', 'empresas.insc_estadual')
                        ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                        ->where('municipios.uf', $ref)
                        ->where('cnpj',$empresa->cnpj)
                        ->get();

                    $estabs = DB::table('estabelecimentos')
                        ->select('estabelecimentos.cnpj', 'estabelecimentos.id', 'estabelecimentos.empresa_id', 'municipios.uf', 'municipios.nome','estabelecimentos.insc_estadual')
                        ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
                        ->where('municipios.uf', $ref)
                        ->where('estabelecimentos.ativo', 1)
                        ->where('empresa_id',$empresa->id)
                        ->get();

                    $ativ_estemps = array_merge($empresas, $estabs);

                } else { //Municipal
                    $ref = $regra->ref;
                    if (strlen($ref)==2) {  // O tributo é municipal, porem a regra é estadual

                        $empresas = DB::table('empresas')
                            ->select('empresas.cnpj', 'empresas.id', 'empresas.id')
                            ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                            ->where('municipios.uf', $ref)
                            ->where('cnpj',$empresa->cnpj)
                            ->get();

                        $estabs = DB::table('estabelecimentos AS est')
                            ->select('est.cnpj', 'est.id', 'est.empresa_id')
                            ->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
                            ->where('mun.uf', $ref)
                            ->where('est.ativo', 1)
                            ->where('empresa_id',$empresa->id)
                            ->get();

                    } else {    // O tributo é municipal, e a regra é municipal

                        $empresas = DB::table('empresas')
                            ->select('empresas.cnpj', 'empresas.id', 'empresas.id')
                            ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                            ->where('municipios.codigo', $ref)
                            ->where('cnpj',$empresa->cnpj)
                            ->get();

                        $estabs = DB::table('estabelecimentos AS est')
                            ->select('est.cnpj', 'est.id', 'est.empresa_id')
                            ->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
                            ->where('mun.codigo', $ref)
                            ->where('est.ativo', 1)
                            ->where('empresa_id',$empresa->id)
                            ->get();
                    }
                    $ativ_estemps = array_merge($empresas, $estabs);
                }

                $trib = DB::table('tributos')
                    ->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
                    ->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
                    ->select('empresa_tributo.adiantamento_entrega')
                    ->where('tributos.id',$regra->tributo->id)
                    ->where('empresas.cnpj',$empresa->cnpj)
                    ->get();

                //VERIFICA ADIANTAMENTO DE ENTREGA
                $offset = $trib[0]->adiantamento_entrega;

                //VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
                $adiant_fds = $regra->afds;

                $val = array();

                // REGRAS ESPECIAIS: RE01,RE02,RE03...
                if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

                    foreach($ativ_estemps as $ae) {
                        $param = array('cnpj' => $ae->cnpj, 'IE' => $ae->insc_estadual);
                        $retval_array = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega, $param, $periodo_apuracao, $offset, $adiant_fds);

                        $data_limite = $retval_array[0]['data']->toDateTimeString();
                        $alerta = intval($regra->tributo->alerta);
                        $inicio_aviso = $retval_array[0]['data']->subDays($alerta)->toDateTimeString();
                        $desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
                        $val = array(
                            'descricao' => $desc_prefix . $retval_array[0]['desc'],
                            'recibo' => $regra->tributo->recibo,
                            'status' => 1,
                            'periodo_apuracao' => $periodo_apuracao,
                            'inicio_aviso' => $inicio_aviso,
                            'limite' => $data_limite,
                            'tipo_geracao' => 'A',
                            'regra_id' => $regra->id
                        );

                        //FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)

                        $val['estemp_type'] = substr($ae->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
                        $val['estemp_id'] = $ae->id;
                        if ($val['estemp_type'] == 'estab') {
                            $val['emp_id'] = $ae->empresa_id;
                        } else {
                            $val['emp_id'] = $ae->id;
                        }

                        //Verifica blacklist dos estabelecimentos para esta regra
                        if (!in_array($ae->id,$blacklist)) {
                            Atividade::create($val);
                            $count++;
                        }

                    }

                }
                // REGRAS PADRÃO
                else {

                    $ref = $regra->ref;
                    if ($municipio = Municipio::find($regra->ref)) {
                        $ref = $municipio->nome . ' (' . $municipio->uf . ')';
                    }
                    $nome_especifico = $regra->nome_especifico;
                    if (!$nome_especifico) {
                        $nome_especifico = $regra->tributo->nome;
                    }
                    $desc = $nome_especifico . ' ' . $ref;
                    $desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

                    $data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
                    $data_limite = $data->toDateTimeString();
                    $alerta = intval($regra->tributo->alerta);
                    $inicio_aviso = $data->subDays($alerta)->toDateTimeString();

                    $val = array(
                        'descricao' => $desc_prefix . $desc,
                        'recibo' => $regra->tributo->recibo,
                        'status' => 1,
                        'periodo_apuracao' => $periodo_apuracao,
                        'inicio_aviso' => $inicio_aviso,
                        'limite' => $data_limite,
                        'tipo_geracao' => 'A',
                        'regra_id' => $regra->id
                    );

                    //FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)
                    if (sizeof($ativ_estemps) > 0) {
                        foreach ($ativ_estemps as $el) {

                            $val['estemp_type'] = substr($el->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
                            $val['estemp_id'] = $el->id;
                            if ($val['estemp_type'] == 'estab') {
                                $val['emp_id'] = $el->empresa_id;
                            } else {
                                $val['emp_id'] = $el->id;
                            }

                            //Verifica blacklist dos estabelecimentos para esta regra
                            if (!in_array($el->id,$blacklist)) {
                                Atividade::create($val);
                                $count++;
                            }
                        }
                    }

                }

            }

            DB::table('crons')->insert(
                ['periodo_apuracao' => $periodo_apuracao,'qtd'=>$count,'tipo_periodo'=>'M','emp_id'=>$empresa->id]
            );
        }

        return $generate;

    }

    public function generateYearlyActivities($periodo_apuracao,$cnpj_empresa) {
        // Activate auto activity generation
        $generate = true;
        //
        $empresa = Empresa::where('cnpj',$cnpj_empresa)->firstOrFail();

        if (Cron::where('periodo_apuracao', $periodo_apuracao)->where('emp_id', $empresa->id)->count() >0) {
            $generate = false;
        }
        //TODAS AS REGRAS ATIVAS PARA A EMPRESA SOLICITADA
        $empresa_tributos = $empresa->tributos()->get();
        $array_tributos_ativos = array();
        foreach($empresa_tributos as $at) {
            $array_tributos_ativos[] = $at->id;
        }
        //
        $regras = Regra::where('freq_entrega','A')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();

        if ($generate) {
            $count = 0;
            foreach ($regras as $regra) {

                $trib = DB::table('tributos')
                    ->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
                    ->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
                    ->select('empresa_tributo.adiantamento_entrega')
                    ->where('tributos.id',$regra->tributo->id)
                    ->where('empresas.cnpj',$empresa->cnpj)
                    ->get();

                //VERIFICA ADIANTAMENTO DE ENTREGA
                $offset = $trib[0]->adiantamento_entrega;

                //VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
                $adiant_fds = $regra->afds;
                $val = array();

                // Não tem regras especiais para a geração anual

                // Regra standard

                $ref = $regra->ref;
                if ($municipio = Municipio::find($regra->ref)) {
                    $ref = $municipio->nome . ' (' . $municipio->uf . ')';
                }
                $desc = $regra->tributo->nome . ' ' . $ref;
                $desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

                $data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
                $data_limite = $data->toDateTimeString();
                $alerta = intval($regra->tributo->alerta);
                $inicio_aviso = $data->subDays($alerta)->toDateTimeString();

                $val = array('descricao' => $desc_prefix . $desc,
                    'recibo' => $regra->tributo->recibo,
                    'status' => 1,
                    'periodo_apuracao' => $periodo_apuracao,
                    'inicio_aviso' => $inicio_aviso,
                    'limite' => $data_limite,
                    'tipo_geracao' => 'A',
                    'regra_id' => $regra->id
                );

                //print_r($val);
                $ativ_estemps = array();

                if ($regra->tributo->tipo == 'F') { //Federal

                    $empresas = DB::table('empresas')
                        ->select('id', 'cnpj')
                        ->where('cnpj',$empresa->cnpj)
                        ->get();

                    $ativ_estemps = $empresas;


                } else if ($regra->tributo->tipo == 'E') { //Estadual

                    $ref = $regra->ref;

                    $empresas = DB::table('empresas')
                        ->select('empresas.cnpj', 'empresas.id', 'municipios.uf', 'municipios.nome')
                        ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                        ->where('municipios.uf', $ref)
                        ->where('cnpj',$empresa->cnpj)
                        ->get();

                    $estabs = DB::table('estabelecimentos')
                        ->select('estabelecimentos.cnpj', 'estabelecimentos.id', 'municipios.uf', 'municipios.nome')
                        ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
                        ->where('municipios.uf', $ref)
                        ->where('estabelecimentos.ativo', 1)
                        ->where('empresa_id',$empresa->id)
                        ->get();

                    $ativ_estemps = array_merge($empresas, $estabs);

                } else { //Municipal
                    $ref = $regra->ref;
                    $empresas = DB::table('empresas')
                        ->select('empresas.cnpj', 'empresas.id')
                        ->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
                        ->where('municipios.codigo', $ref)
                        ->where('cnpj',$empresa->cnpj)
                        ->get();

                    $estabs = DB::table('estabelecimentos AS est')
                        ->select('est.cnpj', 'est.id')
                        ->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
                        ->where('mun.codigo', $ref)
                        ->where('est.ativo', 1)
                        ->where('empresa_id',$empresa->id)
                        ->get();

                    $ativ_estemps = array_merge($empresas, $estabs);
                }
                //FILTRO ESTAB ATIVOS
                if (sizeof($ativ_estemps) > 0) {
                    foreach ($ativ_estemps as $el) {

                        $val['estemp_type'] = substr($el->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
                        $val['estemp_id'] = $el->id;
                        if ($val['estemp_type'] == 'estab') {
                            $val['emp_id'] = $el->empresa_id;
                        } else {
                            $val['emp_id'] = $el->id;
                        }

                        $nova_atividade = Atividade::create($val);
                        $count++;

                        //Assignment usuario
                        //foreach($regra->tributo->users as $user) {
                            //$nova_atividade->users()->save($user);
                        //}
                    }
                }

            }

            DB::table('crons')->insert(
                ['periodo_apuracao' => $periodo_apuracao,'qtd'=>$count,'tipo_periodo'=>'A','emp_id'=>$empresa->id]
            );
        }

        return $generate;
    }

    public function generateNotifications($user) {
        // Activate auto notification generation
        $active = true;

        if (!$active) return true;

        $param_id = $user->id;
        $with_user = function ($query) use ( $param_id ) {
            $query->where('user_id',$param_id );
        };

        $atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
            ->join('regras', 'atividades.regra_id', '=', 'regras.id')
            //->whereHas('users',$with_user)
            ->where('inicio_aviso','<',date("Y-m-d H:i:s"))->where('status',1)
            ->groupBy('atividades.descricao','atividades.limite')
            ->orderBy('atividades.limite')->get();

        $subject = "BravoTaxCalendar - Avisos";
        $data = array('subject'=>$subject,'messageLines'=>array());

        foreach($atividades as $atividade) {
            $descricao = $atividade->descricao;
            if ($atividade->nome_especifico != '') {
                $descricao = 'Entrega '.$atividade->nome_especifico;
            }
            $date = date_create($atividade->limite);
            $data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
        }

        if (sizeof($atividades)>0) {
            $this->sendMail($user, $data, 'emails.notification');
        }

        return sizeof($atividades);

    }

    public function generateAdminNotifications($user) {
        // Activate auto notification generation
        $active = true;

        if (!$active) return true;

        $param_id = $user->id;
        $with_user = function ($query) use ( $param_id ) {
            $query->where('user_id',$param_id );
        };

        $atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
            ->join('regras', 'atividades.regra_id', '=', 'regras.id')
            //->whereHas('users',$with_user)
            ->where('status',2)
            ->groupBy('atividades.descricao','atividades.limite')
            ->orderBy('atividades.limite')->get();

        $subject = "BravoTaxCalendar - Aviso atividades em aprovação";
        $data = array('subject'=>$subject,'messageLines'=>array());

        foreach($atividades as $atividade) {
            $descricao = $atividade->descricao;
            if ($atividade->nome_especifico != '') {
                $descricao = 'Entrega '.$atividade->nome_especifico;
            }
            $date = date_create($atividade->limite);
            $data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
        }

        if (sizeof($atividades)>0) {
            $this->sendMail($user, $data, 'emails.notification-em-aprovacao');
        }

        return sizeof($atividades);

    }

    public function generateSupervisorNotifications($user) {
        // Activate auto notification generation
        $active = true;

        if (!$active) return true;

        $with_user = function ($query) {
            $query->where('user_id', Auth::user()->id);
        };
        $tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
        $granted_array = array();
        foreach($tributos_granted as $el) {
            $granted_array[] = $el->id;
        }

        $atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
            ->join('regras', 'atividades.regra_id', '=', 'regras.id')
            //->whereHas('users',$with_user)
            ->where('status',2)
            ->groupBy('atividades.descricao','atividades.limite')
            ->orderBy('atividades.limite')->get();

        $atividades->whereHas('regra.tributo', function ($query) use ($granted_array) {
            $query->whereIn('id', $granted_array);
        });

        $subject = "BravoTaxCalendar - Aviso atividades em aprovação";
        $data = array('subject'=>$subject,'messageLines'=>array());

        foreach($atividades as $atividade) {
            $descricao = $atividade->descricao;
            if ($atividade->nome_especifico != '') {
                $descricao = 'Entrega '.$atividade->nome_especifico;
            }
            $date = date_create($atividade->limite);
            $data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
        }

        if (sizeof($atividades)>0) {
            $this->sendMail($user, $data, 'emails.notification-em-aprovacao');
        }

        return sizeof($atividades);

    }

    public function getFeriadosCarbon($uf,$ano=null) {

        $retCarb = array();
        if ($ano==null) $ano = date('Y');

        $fer_nac = $this->getFeriadosNacionais($ano);
        foreach($fer_nac as $el) {
            $exploded = explode('-',$el);
            $retCarb[] = Carbon::create($ano, intval($exploded[1]), intval($exploded[0]), 0);
            //
        }

        $retval = FeriadoEstadual::select('*')->where('uf',$uf);
        $fer_est = explode(';',$retval->first()->datas);

        foreach($fer_est as $el) {
            $exploded = explode('-',$el);
            $retCarb[] = Carbon::create($ano, intval($exploded[1]), intval($exploded[0]), 0);
            //
        }

        return $retCarb;
    }
} 
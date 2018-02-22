<?php


namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\Municipio;
use App\Models\Tributo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;


class PagesController extends Controller
{
    protected $s_emp = null;

    public function __construct(Request $request = null)
    { 
        if (!Auth::guest() && !empty(session()->get('seid')))
            $this->s_emp = Empresa::findOrFail(session('seid'));
    }

    public function home (Request $request = null, $empresaID=false)
    {
        $iframe = false;
        $layoutgraficos = '';
        $nomeEmpresa = '';
        if (!empty($_GET['layout'])) {

            $iframe = true;
            $layoutgraficos = 'graficos';
            $this->s_emp->id = $_GET['emp_id'];
            $empresa = Empresa::findOrFail($_GET['emp_id']);
            $nomeEmpresa = $empresa->razao_social;
        }

        if ($empresaID > 0 ) {
            $this->s_emp->id = $empresaID;
        }

        Carbon::setTestNow();  //reset
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        if ($request->has('periodo_apuracao')) {
            $periodo_apuracao = Input::get("periodo_apuracao");

        } else {
            $periodo_apuracao = $last_month->format('mY');
        }

        // Verifica que o periodo exista
        $cron = Cron::where('periodo_apuracao',$periodo_apuracao)->first();
        if ($cron==null) {
            $info_periodo = substr($periodo_apuracao,0,2).'/'.substr($periodo_apuracao,-4,4);
            Session::flash('alert-warning', "O periodo $info_periodo não tem atividades cadastradas. Foi carregado o periodo padrão.");
            $periodo_apuracao = $last_month->format('mY');
        }


        if (!Auth::guest()) {
            
            $user = User::findOrFail(Auth::user()->id);

            if (crypt('teste123', $user->password) === $user->password && $user->reset_senha) {
                return view('pages.alterarsenha')->withUser($user);
            } 

            if (!empty($_GET['empresa_selecionada'])) {

                $key = $_GET['empresa_selecionada'];
                $s = DB::select("Select COUNT(1) as ct FROM empresa_user where user_id = ".Auth::user()->id." AND empresa_id = ". $key . "");
                if (!$s[0]->ct) {
                    echo "Você não tem acesso a empresa informada.<br/><br/><a href='home'>VOLTAR</a>";
                    exit;
                }

                $request->session()->put('seid', $key);
                return view('pages.home_ini');
            }
            
            if (!session()->get('seid') || isset($_GET['selecionar_empresa'])) {
                $user = User::findOrFail(Auth::user()->id);
                $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
                $empresasArray = array();
                foreach($empresas as $key => $empresa) {
                    $s = DB::select("Select COUNT(1) as ct FROM empresa_user where user_id = ".Auth::user()->id." AND empresa_id = ". $key . "");
                    if ((boolean)$s[0]->ct) {
                        $empresasArray[$key] = $empresa;
                    }
                }
                
                return view('pages.selecionarempresa')->withUser($user)->withEmpresas($empresasArray);
            }

            
        
            $tributos = Tributo::selectRaw("nome")->whereNotIn('id',[12,13,14,15])->lists('nome','nome');

            if (sizeof($user->roles)==0) {
                return ("<br/>Este usuário está sem autorização de acesso. Entrar em contato com o administrador de sistema.<br/><br/><a href='logout'>LOGOUT</a>");
            }

            $retval = $this->_loadNotifications(); //var_dump($retval);
            $graph = array();
            //$graph['status_1'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 1)->count();
            //$graph['status_2'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 2)->count();
            //$graph['status_3'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 3)->count();

            //MANAGER
            if ($user->hasRole('manager')) {
                return redirect('dashboard');
            }
            //ADMIN / OWNER
            else if ($user->hasRole('admin') || $user->hasRole('owner') || $user->hasRole('gbravo') || $user->hasRole('gcliente')) {
                $graph['status_1'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 1)->count();
                $graph['status_2'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 2)->count();
                $graph['status_3'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 3)->count();

                if ($empresaID > 0) {

                    if ($graph['status_1'] == 0) {
                        return false;
                    }

                    return true;
                }
                
                return view('pages.home'.$layoutgraficos)->withMessages($retval['ordinarias'])
                    ->withVencidas($retval['vencidas'])
                    ->withUrgentes($retval['urgentes'])
                    ->withAprovacao($retval['em_aprovacao'])
                    ->withGraph($graph)
                    ->withPeriodo($periodo_apuracao)
                    ->withCron($cron)
                    ->with('iframe', $iframe)
                    ->with('nome_empresa', $nomeEmpresa)
                    ->with('emp_id', $this->s_emp->id);
            }
            //ANALYST/SUPERVISOR/USER
            else {
                //$with_user = function ($query) {
                //    $query->where('user_id', Auth::user()->id);
                //};
                $graph['status_1'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 1)->count();
                $graph['status_2'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 2)->count();
                $graph['status_3'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 3)->count();
                //whereHas('users', $with_user)

                if ($empresaID > 0) {

                    if ($graph['status_1'] == 0) {
                        return false;
                    }

                    return true;
                }
                
                return view('pages.home'.$layoutgraficos)->withMessages($retval['ordinarias'])
                    ->withVencidas($retval['vencidas'])
                    ->withUrgentes($retval['urgentes'])
                    ->withAprovacao($retval['em_aprovacao'])
                    ->withGraph($graph)
                    ->withPeriodo($periodo_apuracao)
                    ->withCron($cron)
                    ->with('iframe', $iframe)
                    ->with('nome_empresa', $nomeEmpresa)
                    ->with('emp_id', $this->s_emp->id);            }

        } else {

            Session::forget('vcn');
            Session::forget('vcp');
            Session::forget('vco');
            Session::forget('seid');
            return Redirect::to('login');
        }

    }

    public function about(Request $request)
    {
        Carbon::setTestNow();  //reset time
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        $periodo_apuracao = $last_month->format('mY');

        if ($request->has('periodo_apuracao')) {
            $periodo_apuracao = Input::get("periodo_apuracao");
        }

        $usuarios = User::select('*')->get();

        $standing = DB::select("SELECT (X.name), SUM(X.fp) as entrega_fora_prazo, SUM(X.ep) as entrega_em_prazo, SUM(X.fp)+SUM(X.ep) as entregas_totais, ROUND(SUM(X.ep)/(SUM(X.fp)+SUM(X.ep))*100,2) as perc
                                FROM (
                                    select u.name as name, count(a.id) as fp, 0 as ep from atividades a, users u
                                    where a.usuario_entregador=u.id and a.tipo_geracao='A' and a.data_entrega>a.limite
                                    and a.periodo_apuracao = ".$periodo_apuracao."
                                    group by a.usuario_entregador
                                    union all
                                    select u.name as name, 0 as fp, count(a.id) as ep from atividades a, users u
                                    where a.usuario_entregador=u.id and a.tipo_geracao='A' and a.data_entrega<=a.limite
                                    and a.periodo_apuracao = ".$periodo_apuracao."
                                    group by a.usuario_entregador
                                ) AS X
                                GROUP BY X.name order by perc desc");
        $array = array();

        $div = count($standing) / 2;

        if (floor($div) != $div) {
            $div = $div + 0.5;
        }
        
        if (count($standing) > 0) {

            for($u=0;$u<count($standing);$u++) {

                if (empty($array[$u][0]) && !empty($standing[$u]) && $u<$div) {

                    $array[$u][0] = $standing[$u];
                    if (!empty($standing[$u+$div])) {
                        $array[$u][1] = $standing[$u+$div];    
                    }
                }    
            }
        }
       
        return view('pages.about')->with('users',$usuarios)->with('standing',$array)->with('periodo',$periodo_apuracao);
    }

    public function upload()
    {
        return view('pages.upload');
    }


    public function graficos(Request $request)
    {   
        $empresasSelecionadas = array();
        $empresasSelected     = array();
        $user = User::findOrFail(Auth::user()->id);
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $empresasArray = array();
        foreach($empresas as $key => $empresa) {

            $s = DB::select("Select COUNT(1) as ct FROM empresa_user where user_id = ".Auth::user()->id." AND empresa_id = ". $key . "");
            if ((boolean)$s[0]->ct && $this->home($request, $key)) {
                $empresasArray[$key] = $empresa;
            }
        }
        
        $input = $request->all();
        if (!empty($input['multiple_select_empresas'][0])) {

            foreach($input['multiple_select_empresas'] as $company)
            {
                $empresasSelecionadas[] = $company;
                $empresasSelected = $input['multiple_select_empresas'];  
            }

            //array_push($empresasSelecionadas, "img-1", "img-2");
        }
       
        return view('pages.graficos')
        ->with('empresas', $empresasArray)
        ->with('empresas_selecionadas', $empresasSelecionadas)
        ->with('empresas_selected', $empresasSelected);
    }

    public function desempenho_entregas(Request $request)
    {

        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $empresasArray = array();
        $i = 0;
        $u = 0;
        foreach($empresas as $key => $empresa) {

            $s = DB::select("Select COUNT(1) as ct FROM empresa_user where user_id = ".Auth::user()->id." AND empresa_id = ". $key . "");
            if ((boolean)$s[0]->ct && $this->home($request, $key)) {

                if (!empty($empresasArray[$i]) && count($empresasArray[$i]) > 3) {
                    $u = 0;
                    $i++;
                }

                $empresasArray[$i][$u]['key'] = $key;
            }

            $u++;
        }   
           
        return view('pages.desempenho_entregas')
        ->with('empresas_selecionadas', $empresasArray);
    }

    public function status_empresas(Request $request) {

        $iframe = false;
        $layoutgraficos = '';
        $nomeEmpresa = '';

        if ($request->has('switch_periodo')) {
            $switch = Input::get('switch_periodo');
        } else {
            $switch = 1;
        }

        Carbon::setTestNow();  //reset
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        $user = User::findOrFail(Auth::user()->id);
        $tributos = Tributo::selectRaw("nome")->whereIn('tipo',['E','M'])->whereNotIn('id',[12,13,14,15])->lists('nome','nome'); //ANUAL DELIVERY

        if ($request->has('periodo_apuracao')) {
            $periodo_apuracao = Input::get("periodo_apuracao");

        } else {
            $periodo_apuracao = $last_month->format('mY');
        }

        // Verifica o periodo
        $cron = Cron::where('periodo_apuracao',$periodo_apuracao)->first();
        if ($cron==null) {
            $info_periodo = substr($periodo_apuracao,0,2).'/'.substr($periodo_apuracao,-4,4);
            Session::flash('alert-warning', "O periodo $info_periodo não tem atividades cadastradas. Foi carregado o periodo padrão.");
            $periodo_apuracao = $last_month->format('mY');
        }

        if ($user->hasRole('supervisor')  || $user->hasRole('gcliente') || $user->hasRole('gbravo') || $user->hasRole('analyst') || $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('owner')) {

            $tipo_condition = "";
            $tipo_check = array(true,false,false);

            if ($request->has('tipo_tributos')) {
                $tipo = Input::get("tipo_tributos");
                switch ($tipo) {
                    case 'T':
                        break;
                    case 'E':
                        $tipo_condition = "and t.tipo = '$tipo'";
                        $tipo_check = array(false,false,true);
                        break;
                    case 'F':
                        $tipo_condition = "and t.tipo = '$tipo'";
                        $tipo_check = array(false,true,false);
                        break;
                    default:
                        break;
                }
            }

            $empresasSelecionadas = array();
            $empresasSelected     = array();
            $user = User::findOrFail(Auth::user()->id);
            $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
            
            foreach($empresas as $key => $empresa) {
                
                $this->s_emp->id = $key;

                if ($switch) {
                    $retval = DB::select( DB::raw("
                                        select t.nome,substr(limite,1,10) as lim,substr(data_aprovacao,1,10) as dt_aprovacao,limite,count(*),a.status
                                        from atividades a, regras r, tributos t
                                        where a.regra_id=r.id and r.tributo_id=t.id and a.emp_id=:empid and a.periodo_apuracao=:periodo_apuracao $tipo_condition
                                        group by t.nome,lim,dt_aprovacao,a.status
                                        order by t.nome,lim"), array(
                        'empid' => $this->s_emp->id,
                        'periodo_apuracao' => $periodo_apuracao
                    ));
                } else {
                    $limits = array();
                    //calculateNextMonthLimit
                    $data_limite = Carbon::createFromDate(substr($periodo_apuracao,-4,4), intval(substr($periodo_apuracao,0,2)), 1);
                    Carbon::setTestNow($data_limite);
                    Carbon::setTestNow(Carbon::parse('next month'));
                    $data_limite_inicio = Carbon::now()->startOfMonth();
                    $data_limite_fim = Carbon::now()->endOfMonth();
                    Carbon::setTestNow(); //reset
                    $limits['start'] = substr($data_limite_inicio,0,10);
                    $limits['end'] = substr($data_limite_fim,0,10);
                    //
                    $retval = DB::select( DB::raw("
                                        select t.nome,substr(limite,1,10) as lim,substr(data_aprovacao,1,10) as dt_aprovacao,limite,count(*),a.status
                                        from atividades a, regras r, tributos t
                                        where a.regra_id=r.id and r.tributo_id=t.id and a.emp_id=:empid $tipo_condition
                                        group by t.nome,lim,dt_aprovacao,a.status
                                        having lim >= :data_limite_inf and lim <= :data_limite_sup
                                        order by t.nome,lim"), array(
                        'empid' => $this->s_emp->id,
                        'data_limite_inf' => $limits['start'],
                        'data_limite_sup' => $limits['end'],
                    ));
                }
                
                
                // Elaboração das informações
                
                $NewArray = array();
                foreach ($retval as $val) {
                    $NewArray[] = (array) $val;
                }
                
                $graph = array();
                $count = array('s1'=>0,'s2'=>0,'s3'=>0,'v1'=>0,'v2'=>0, 'v3'=>0);
                foreach ($NewArray as $val) {
                    
                    // Vencidas
                    if ($val['lim']<$today && $val['status']<3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    } // Entregue fora do prazo
                    else if ($val['dt_aprovacao']>$val['lim'] && $val['status']==3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    }// Entregue no prazo
                    else if ($val['dt_aprovacao']<=$val['lim'] && $val['status']==3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                        $count['s' . $val['status']] += $val['count(*)'];

                    }// Não vencidas
                    else { // $val['lim']>=$today && $val['status']<3
                        isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                        $count['s' . $val['status']] += $val['count(*)'];
                    }
                }

                $array[$empresa]=$graph;
                $array[$empresa]['count']=$count;
            }            
            
            return view('pages.status_empresas'.$layoutgraficos)->withGraph($array)->withPeriodo($periodo_apuracao)->withSwitch($switch)->withTributos($tributos)->withCron($cron)->withTipo($tipo_check)->with('nome_empresa', $nomeEmpresa)->with('emp_id', $this->s_emp->id);
        }
    }

    public function returnEmpresasSearch(Request $request, $empresas) {
        $arrayEmpresas = explode(',', $empresas);
        $array = array();

        $cores[0] = 'yellow';
        $cores[1] = 'blue';
        $cores[2] = 'red';
        $cores[3] = 'black';

        $i = 0;
        foreach($arrayEmpresas as $row) {
            $array[$i] = $this->dashboard($request, $row, true);
            $array[$i]['cor'] = $cores[$i];
            $i++;
        }
        
        return view('pages.dashboardentregometro')
                        ->with('array', $array);
    }

    public function dashboard(Request $request, $empresaID=0, $returnArray=false) {

        $iframe = false;
        $layoutgraficos = '';
        $nomeEmpresa = '';
        $cor = '';

        if (!empty($_GET['empresas']) && !$returnArray) {
            return $this->returnEmpresasSearch($request, $_GET['empresas']);
        } 

        if (!empty($_GET['layout'])) {

            $iframe = true;
            $layoutgraficos = $_GET['layout'];
            $this->s_emp->id = $_GET['emp_id'];
            $empresa = Empresa::findOrFail($_GET['emp_id']);
            $nomeEmpresa = $empresa->razao_social;
            if ($layoutgraficos == 'entregometro') {
                $cor = $_GET['cor'];
            }
            
        }

        if ($returnArray) {
            $this->s_emp->id = $empresaID;
            $empresa = Empresa::findOrFail($empresaID);
            $nomeEmpresa = $empresa->razao_social;
        }

        if ($request->has('switch_periodo')) {
            $switch = Input::get('switch_periodo');
        } else {
            $switch = 1;
        }

        Carbon::setTestNow();  //reset
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        $user = User::findOrFail(Auth::user()->id);
        $tributos = Tributo::selectRaw("nome")->whereIn('tipo',['E','M'])->whereNotIn('id',[12,13,14,15])->lists('nome','nome'); //ANUAL DELIVERY

        if ($request->has('periodo_apuracao')) {
            $periodo_apuracao = Input::get("periodo_apuracao");

        } else {
            $periodo_apuracao = $last_month->format('mY');
        }

        // Verifica o periodo
        $cron = Cron::where('periodo_apuracao',$periodo_apuracao)->first();
        if ($cron==null) {
            $info_periodo = substr($periodo_apuracao,0,2).'/'.substr($periodo_apuracao,-4,4);
            Session::flash('alert-warning', "O periodo $info_periodo não tem atividades cadastradas. Foi carregado o periodo padrão.");
            $periodo_apuracao = $last_month->format('mY');
        }

        if ($user->hasRole('supervisor')  || $user->hasRole('gcliente') || $user->hasRole('gbravo') || $user->hasRole('analyst') || $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('owner')) {

            $tipo_condition = "";
            $tipo_check = array(true,false,false);

            if ($request->has('tipo_tributos')) {
                $tipo = Input::get("tipo_tributos");
                switch ($tipo) {
                    case 'T':
                        break;
                    case 'E':
                        $tipo_condition = "and t.tipo = '$tipo'";
                        $tipo_check = array(false,false,true);
                        break;
                    case 'F':
                        $tipo_condition = "and t.tipo = '$tipo'";
                        $tipo_check = array(false,true,false);
                        break;
                    default:
                        break;
                }
            }
            
            if ($switch) {
                $retval = DB::select( DB::raw("
                                    select t.nome,substr(limite,1,10) as lim,substr(data_aprovacao,1,10) as dt_aprovacao,limite,count(*),a.status
                                    from atividades a, regras r, tributos t
                                    where a.regra_id=r.id and r.tributo_id=t.id and a.emp_id=:empid and a.periodo_apuracao=:periodo_apuracao $tipo_condition
                                    group by t.nome,lim,dt_aprovacao,a.status
                                    order by t.nome,lim"), array(
                    'empid' => $this->s_emp->id,
                    'periodo_apuracao' => $periodo_apuracao
                ));
            } else {
                $limits = array();
                //calculateNextMonthLimit
                $data_limite = Carbon::createFromDate(substr($periodo_apuracao,-4,4), intval(substr($periodo_apuracao,0,2)), 1);
                Carbon::setTestNow($data_limite);
                Carbon::setTestNow(Carbon::parse('next month'));
                $data_limite_inicio = Carbon::now()->startOfMonth();
                $data_limite_fim = Carbon::now()->endOfMonth();
                Carbon::setTestNow(); //reset
                $limits['start'] = substr($data_limite_inicio,0,10);
                $limits['end'] = substr($data_limite_fim,0,10);
                //
                $retval = DB::select( DB::raw("
                                    select t.nome,substr(limite,1,10) as lim,substr(data_aprovacao,1,10) as dt_aprovacao,limite,count(*),a.status
                                    from atividades a, regras r, tributos t
                                    where a.regra_id=r.id and r.tributo_id=t.id and a.emp_id=:empid $tipo_condition
                                    group by t.nome,lim,dt_aprovacao,a.status
                                    having lim >= :data_limite_inf and lim <= :data_limite_sup
                                    order by t.nome,lim"), array(
                    'empid' => $this->s_emp->id,
                    'data_limite_inf' => $limits['start'],
                    'data_limite_sup' => $limits['end'],
                ));
            }

            // Elaboração das informações
            $array = array();
            foreach ($retval as $val) {
                $array[$val->nome][] = (array) $val;
            }

            foreach ($array as $key=>$el) {
                $graph = array();
                $count = array('s1'=>0,'s2'=>0,'s3'=>0,'v1'=>0,'v2'=>0, 'v3'=>0);
                foreach ($el as $val) {
                    // Vencidas
                    if ($val['lim']<$today && $val['status']<3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    } // Entregue fora do prazo
                    else if ($val['dt_aprovacao']>$val['lim'] && $val['status']==3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    }// Entregue no prazo
                    else if ($val['dt_aprovacao']<=$val['lim'] && $val['status']==3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                        $count['s' . $val['status']] += $val['count(*)'];

                    }// Não vencidas
                    else { // $val['lim']>=$today && $val['status']<3
                        isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                            $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                        $count['s' . $val['status']] += $val['count(*)'];
                    }
                }
                $array[$key]=$graph;
                $array[$key]['count']=$count;
            }

            $retvalDash = $this->_loadNotifications(); //var_dump($retval);

            $graphDash = array();
            $graphDash['status_1'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 1)->count();
            $graphDash['status_2'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 2)->count();
            $graphDash['status_3'] = Atividade::where('emp_id', $this->s_emp->id)->where('recibo', 1)->where('periodo_apuracao', $periodo_apuracao)->where('status', 3)->count();
            
            if ($returnArray) {
                $array['graph'] = $array;
                $array['periodo'] = $periodo_apuracao;
                $array['switch'] = $switch;
                $array['tributos'] = $tributos;
                $array['cron'] = $cron;
                $array['tipo'] = $tipo_check;
                $array['graphdash'] = $graphDash;
                $array['nome_empresa'] = $nomeEmpresa;
                $array['cor'] = $cor;
                $array['emp_id'] = $this->s_emp->id;

                return $array;
            }

            return view('pages.dashboard'.$layoutgraficos)
                        ->withGraph($array)
                        ->withPeriodo($periodo_apuracao)
                        ->withSwitch($switch)
                        ->withTributos($tributos)
                        ->withCron($cron)
                        ->withTipo($tipo_check)
                        ->withGraphdash($graphDash)
                        ->with('nome_empresa', $nomeEmpresa)
                        ->with('cor', $cor)
                        ->with('emp_id', $this->s_emp->id);
        }
    }

    public function dashboard_analista(Request $request)
    {
        Carbon::setTestNow();  //reset time
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        $ufs = Municipio::selectRaw("uf, uf")->orderby('uf','asc')->lists('uf','uf');
        $municipios = [''=>''];

        $periodo_apuracao = $last_month->format('mY');

        if ($request->has('periodo_apuracao')) {
            $periodo_apuracao = Input::get("periodo_apuracao");
        }

        $graph = array();

        if ($request->has('tributo')) {
            $tributo_id = Input::get("tributo");
            $uf = Input::get("uf");
            $only_uf = Input::get("only-uf");
            $codigo = Input::get("codigo");

            $graph['params'] = array('p_uf'=>$uf,'p_onlyuf'=>$only_uf,'p_codigo'=>$codigo,'p_tributo'=>$tributo_id);
            $graph['status_1'] = 0; $graph['status_2'] = 0; $graph['status_3'] = 0;


            $ativ_filtered = DB::table('atividades')
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->select(array('status',DB::raw('COUNT(atividades.id) as count')))
                ->where('periodo_apuracao',$periodo_apuracao)
                ->where('recibo', 1)
                ->where('regras.tributo_id',$tributo_id)
                ->where('emp_id',$this->s_emp->id)
                ->groupBy('status')
                ->get();

            foreach ($ativ_filtered as $at) {
                $graph['status_'.$at->status] = $at->count;
            }
        }
        else if ($request->has('uf') && $request->has('codigo')) {

            $uf = Input::get("uf");
            $only_uf = Input::get("only-uf");
            $codigo = Input::get("codigo");


            $graph['params'] = array('p_uf'=>$uf,'p_onlyuf'=>$only_uf,'p_codigo'=>$codigo,'p_tributo'=>null);
            $graph['status_1'] = 0; $graph['status_2'] = 0; $graph['status_3'] = 0;

            $ref = $codigo;
            if ($only_uf) {
                $ref = $uf;
            }

            $ativ_filtered = DB::table('atividades')
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->select(array('status',DB::raw('COUNT(atividades.id) as count')))
                ->where('ref',$ref)
                ->where('periodo_apuracao',$periodo_apuracao)
                ->where('recibo', 1)
                ->where('emp_id',$this->s_emp->id)
                ->groupBy('status')
                ->get();

            foreach ($ativ_filtered as $at) {
                    $graph['status_'.$at->status] = $at->count;
            }
            //var_dump($graph);

        } else {
            $graph['params'] = array('p_uf'=>null,'p_onlyuf'=>false,'p_codigo'=>null,'p_tributo'=>null);

            $graph['status_1'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 1)->count();
            $graph['status_2'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 2)->count();
            $graph['status_3'] = Atividade::where('emp_id', $this->s_emp->id)->where('periodo_apuracao', $periodo_apuracao)->where('status', 3)->count();
        }

        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');

        return view('pages.dashboard-analista')
            ->with('ufs',$ufs)
            ->with('municipios',$municipios)
            ->with('tributos',$tributos)
            ->with('periodo',$periodo_apuracao)
            ->with('graph',$graph);

    }

    public function imgGrafico1()
    {
        return view('pages.grafico1');
    }

    public function imgGrafico2()
    {
        return view('pages.grafico2');
    }

    public function dashboard_tributo(Request $request) {

        Carbon::setTestNow();  //reset time
        $today = Carbon::today()->toDateString();
        $last_month = new Carbon('last month');

        $user = User::findOrFail(Auth::user()->id);
        $tributos = Tributo::selectRaw("nome")->whereIn('tipo',['E','M'])->whereNotIn('id',[12,13,14,15])->lists('nome','nome'); //ANUAL DELIVERY

        // Verifica Request
        if ($request->has('periodo_apuracao') && $request->has('tributo')) {
            $periodo_apuracao = Input::get("periodo_apuracao");
            $tributo = Input::get("tributo");
        } else {
            return Redirect::to('home');
        }

        // Verifica o periodo
        $cron = Cron::where('periodo_apuracao',$periodo_apuracao)->first();
        if ($cron==null) {
            $info_periodo = substr($periodo_apuracao,0,2).'/'.substr($periodo_apuracao,-4,4);
            Session::flash('alert-warning', "O periodo $info_periodo não tem atividades cadastradas. Foi carregado o periodo padrão.");
            $periodo_apuracao = $last_month->format('mY');
        }
        //Verifica o role
        if ($user->hasRole('supervisor') || $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('owner')) {

            $retval = DB::select( DB::raw("
                                select  t.nome,substr(limite,1,10) as lim,
                                        substr(data_aprovacao,1,10) as dt_aprovacao,
                                        a.status,
                                        count(*)
                                from atividades a, regras r, tributos t
                                where a.regra_id=r.id and r.tributo_id=t.id and a.emp_id=:empid and a.periodo_apuracao=:periodo_apuracao
                                group by t.nome,lim,dt_aprovacao,a.status
                                order by t.nome,lim"), array(
                'empid' => $this->s_emp->id,
                'periodo_apuracao' => $periodo_apuracao,
            ));
            // Elaboração das informações no model TRIBUTO->DATA LIMITE->STATUS
            $array = array();
            foreach ($retval as $val) {
                $array[$val->nome][] = (array) $val;
            }

            foreach ($array as $key=>$el) {
                $graph = array();
                $count = array('s1'=>0,'s2'=>0,'s3'=>0,'v1'=>0,'v2'=>0, 'v3'=>0);
                foreach ($el as $val) {
                    // Vencidas
                    if ($val['lim']<$today && $val['status']<3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                        $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                        $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    } // Entregue fora do prazo
                    else if ($val['dt_aprovacao']>$val['lim'] && $val['status']==3) {
                        isset($graph[$this->_dateFormat($val['lim'])]['v' . $val['status']])?
                        $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] += $val['count(*)']:
                        $graph[$this->_dateFormat($val['lim'])]['v' . $val['status']] = $val['count(*)'];
                        $count['v' . $val['status']] += $val['count(*)'];
                    }// Entregue no prazo
                    else if ($val['dt_aprovacao']<=$val['lim'] && $val['status']==3) {
                       isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                       $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                       $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                       $count['s' . $val['status']] += $val['count(*)'];

                    }// Não vencidas
                    else { // $val['lim']>=$today && $val['status']<3
                        isset($graph[$this->_dateFormat($val['lim'])]['s' . $val['status']])?
                        $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] += $val['count(*)']:
                        $graph[$this->_dateFormat($val['lim'])]['s' . $val['status']] = $val['count(*)'];
                        $count['s' . $val['status']] += $val['count(*)'];
                    }
                }
                $array[$key]=$graph;
                //$array[$key]['count']=$count;
            }

            //var_dump($today);
            return view('pages.dashboard-tributo')->withGraph($array)->withPeriodo($periodo_apuracao)->withTributo($tributo)->withTributos($tributos)->withCron($cron);
        }
    }

    private function _loadNotifications() {

        if (Auth::guest()) {

            $retval = Array('ordinarias'=>null,'urgentes'=>null,'em_aprovacao'=>null,'vencidas');

        } elseif(Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner')) {

            Carbon::setTestNow();  //reset
            $today = Carbon::now();

            $atividades_em_aprovacao = Atividade::select('atividades.*','tributos.nome')
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('status','=',2)
                ->where('emp_id', $this->s_emp->id)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $atividades_vencidas = Atividade::select('atividades.*','tributos.nome')
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('limite', '<', $today)
                ->where('status','=',1)
                ->where('emp_id', $this->s_emp->id)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $retval = array('ordinarias'=>null,'urgentes'=>null,'em_aprovacao'=>$this->_notificationOutput($atividades_em_aprovacao),'vencidas'=>$this->_notificationOutput($atividades_vencidas));

        } else {
            $with_user = function ($query) {
                //$query->where('user_id', Auth::user()->id)->where('status',1);
                $query->where('user_id', Auth::user()->id);
            };

            Carbon::setTestNow();  //reset
            $today = Carbon::now();
            $nextWeek = Carbon::now()->addWeekDays(5);
            //$today = date("Y-m-d H:i:s");

            $atividades_em_aprovacao = Atividade::select('atividades.*','tributos.nome')//->whereHas('users', $with_user)
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('status','=',2)
                ->where('emp_id', $this->s_emp->id)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $atividades_ordinarias = Atividade::select('atividades.*','tributos.nome')//->whereHas('users', $with_user)
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('inicio_aviso', '<', $today)
                ->where('limite', '>', $nextWeek)
                ->where('emp_id', $this->s_emp->id)
                ->where('status',1)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $atividades_urgentes = Atividade::select('atividades.*','tributos.nome')//->whereHas('users', $with_user)
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('limite', '<', $nextWeek)
                ->where('limite', '>=', $today)
                ->where('emp_id', $this->s_emp->id)
                ->where('status',1)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $atividades_vencidas = Atividade::select('atividades.*','tributos.nome')//->whereHas('users', $with_user)
                ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                ->join('tributos', 'regras.tributo_id', '=', 'tributos.id')
                ->where('limite', '<', $today)
                ->where('emp_id', $this->s_emp->id)
                ->where('status',1)
                ->orderBy('limite')
                ->with('regra')->with('regra.tributo')->with('estemp')->get();

            $retval = array('ordinarias'=>$this->_notificationOutput($atividades_ordinarias),
                            'urgentes'=>$this->_notificationOutput($atividades_urgentes),
                            'em_aprovacao'=>$this->_notificationOutput($atividades_em_aprovacao),
                            'vencidas'=>$this->_notificationOutput($atividades_vencidas));

        }

        return $retval;
    }

    private function _dateFormat($datestring) {
        $newDate = date("d-m", strtotime($datestring));
        return $newDate;
    }

    private function _notificationOutput($input) {
        $output = array();
        foreach ($input as $el) {
            $output[$el->regra->tributo->nome][$this->_dateFormat($el->limite)][] = $el;
        }
        //var_dump($output);
        return $output;
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Comentario;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\Tributo;
use App\Models\Regra;
use App\Models\User;
use App\Models\Empresa;

use App\Services\EntregaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\CronogramaAtividade;
use App\Models\Atividade;
use App\Models\CronogramaMensal;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;


class CronogramaatividadesController extends Controller
{
    protected $eService;

    function __construct(EntregaService $service)
    {
        $this->middleware('auth');
        $this->eService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cronogramaatividades.index')->with('filter_cnpj',Input::get("vcn"))->with('filter_codigo',Input::get("vco"));
    }

    public function anyData(Request $request)
    {
        $input = $request->all();
        $periodo_busca = '';
        $empresa_busca = '';
        $ie_busca = '';
        $municipio_cod = '';
        $analista_busca = '';
        $estabelecimento_busca = '';
        $data_inicio = '';
        $data_termino = '';
        
        $permite_empresa = false;
        $permite_analista = false;
        $permite_municipio = false;
        $permite_filial = false;


        if (isset($input['periodo_apuracao']) && !empty($input['periodo_apuracao'])) {
            $periodo_busca = str_replace('/', '', $input['periodo_apuracao']);
        }
        if (isset($input['Emp_id']) && !empty($input['Emp_id'])) {
            $empresa_busca = $input['Emp_id'];
        }
        if (!empty($input['municipio_cod'])) {
            $municipio_cod = $input['municipio_cod'];
        }
        if (!empty($input['ie_busca'])) {
            $ie_busca = $input['ie_busca'];
        }
        if (!empty($input['Analista_id'])) {
            $analista_busca = $input['Analista_id'];
        }
        if (!empty($input['Estabelecimento_id'])) {
            $estabelecimento_busca = $input['Estabelecimento_id'];
        }
        if (!empty($input['permite_empresa'])) {
            $permite_empresa = $input['permite_empresa'];
        }
        if (!empty($input['permite_analista'])) {
            $permite_analista = $input['permite_analista'];
        }
        if (!empty($input['permite_municipio'])) {
            $permite_municipio = $input['permite_municipio'];
        }
        if (!empty($input['permite_filial'])) {
            $permite_filial = $input['permite_filial'];
        }
        if (!empty($input['data_inicio'])) {
            $data_inicio = $input['data_inicio'];
        }
        if (!empty($input['data_termino'])) {
            $data_termino = $input['data_termino'];
        }

        $query = 'SELECT A.id, DATE_FORMAT(A.inicio_aviso, "%d/%m/%Y") as inicio_aviso , DATE_FORMAT(A.data_atividade, "%d/%m/%Y") as data_atividade, B.codigo, A.descricao, C.uf, E.Tipo, F.name, C.nome, B.cnpj, B.insc_estadual, A.Id_usuario_analista from cronogramaatividades A inner join estabelecimentos B on A.estemp_id = B.id inner join municipios C on B.cod_municipio = C.codigo left join regras D on A.regra_id = D.id inner join tributos E on D.tributo_id = E.id left join users F on A.Id_usuario_analista = F.id where 1 ';
        
        if (!empty($empresa_busca) && $permite_empresa) {
            $query .= 'AND A.emp_id = '.$empresa_busca.'';
        }
        if (!empty($periodo_busca)) {
            $query .= ' AND A.periodo_apuracao = '.$periodo_busca.'';
        }
        if (!empty($ie_busca)) {
            $query .= ' AND B.insc_estadual = '.$ie_busca.'';
        }
        if (!empty($estabelecimento_busca) && $permite_filial) {
            $query .= ' AND A.estemp_id = '.$estabelecimento_busca.'';
        }
        if (!empty($municipio_cod) && $permite_municipio) {
            $query .= ' AND C.codigo = '.$municipio_cod.'';
        }
        if (!empty($analista_busca) && $permite_analista) {
            $query .= ' AND A.Id_usuario_analista = '.$analista_busca.'';
        }
        if (!empty($data_inicio)) {
            $query .= ' AND DATE_FORMAT(A.inicio_aviso, "%Y-%m-%d") = "'.$data_inicio.'"';
        }
        if (!empty($data_termino)) {
            $query .= ' AND DATE_FORMAT(A.limite, "%Y-%m-%d") = "'.$data_termino.'"';
        }

        $atividades = DB::select($query);
        $atividades = json_decode(json_encode($atividades),true);
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $municipios = Municipio::selectRaw("nome, codigo")->lists('nome','codigo');
        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->lists('razao_social','id');
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $analistas = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');

        return view('cronogramaatividades.index')->with('tabela', $atividades)->with('empresas', $empresas)->with('analistas', $analistas)->with('estabelecimentos', $estabelecimentos)->with('municipios', $municipios);
    }

    public function alterarAnalistas(Request $request)
    {
        $input = $request->all();   

        if (!empty($input['alterar']) && !empty($input['Analista_id'])) {
            DB::table('cronogramaatividades')
                ->wherein('id', $input['alterar'])
                ->update(['Id_usuario_analista' => $input['Analista_id']]);
        }

        return redirect()->back()->with('status', 'Analistas Alterados com sucesso');
    }


    public function AlterAnalistas(Request $request)
    {
        $input = $request->all();
        if (empty($input['Id_usuario_analista'])) {
            return redirect()->back()->with('status', 'É necessário selecionar um Analista');
        }

        CronogramaAtividade::where('cronograma_mensal', $input['id_cronogramamensal'])->update(['Id_usuario_analista' => $input['Id_usuario_analista']]);
        $msg = 'Analistas alterados com sucesso';
        
        return redirect()->back()->with('status', $msg);
        
    } 

    public function planejamento(Request $request)
    {
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $input = $request->all();
        $dados = DB::table('cronogramamensal')->leftjoin('cronogramaatividades', 'cronogramamensal.id', '=', 'cronogramaatividades.cronograma_mensal')->selectRaw('cronogramamensal.*, GROUP_CONCAT(cronogramaatividades.Id_usuario_analista SEPARATOR ", ") AS analistas');
        if (!empty($input)) {
            $periodo = str_replace('/', '', $input['periodo_apuracao']);
            $dados = $dados->where('cronogramamensal.periodo_apuracao', '=', $periodo);
        }
        // $dados = $dados->orderby('Empresa_id', '=', str_replace('/', '', $input['periodo_apuracao']));
        $dados = $dados->groupBy('cronogramamensal.id')->get();
        $array = array();
        if (!empty($dados)) {
            foreach ($dados as $key => $value) {
                $Tributo = Tributo::find($value->Tributo_id);
                $data_carga = DB::Select('SELECT A.Data_prev_carga FROM previsaocarga A WHERE A.periodo_apuracao = "'.$value->periodo_apuracao.'" AND A.Tributo_id = '.$value->Tributo_id);

                $empresa = Empresa::findOrFail($value->Empresa_id);

                $value->cnpj = $empresa->cnpj;
                $value->Tributo_nome = $Tributo->nome;
                $value->Tempo_estab = $value->Tempo_estab/60;
                $value->Tempo_total = $value->Tempo_total/60;
                $value->Tempo_geracao = $value->Tempo_geracao/60;
                
                $value->Qtd_analistas = round($value->Qtd_analistas);
                $value->Inicio = date('d/m/Y', strtotime("+1 days",strtotime($data_carga[0]->Data_prev_carga))); 
                $value->carga = date('d/m/Y', strtotime($data_carga[0]->Data_prev_carga)); 
                    
                $inicio = $this->formatData($value->Inicio);
                $value->Termino = date('d/m/Y', strtotime("+".$value->Qtd_dias." days",strtotime($inicio)));

                $data_vencimento = str_replace('-', '/', $value->DATA_SLA);
                $value->DATA_SLA = date('d/m/Y', strtotime($data_vencimento));
                
                $str = '';
                if (!empty($value->analistas)) {
                    $analistasArray = array();
                    if (strlen($value->analistas > 4)) {
                        $analistas_explode = explode(',', $value->analistas);
                        foreach ($analistas_explode as $singlekey => $single) {
                            $usuario = User::Find($single);
                            if (!empty($usuario)) {
                                $analistasArray[trim($single)] = $usuario->name;
                            }else {
                                $analistasArray[trim($single)] = '';
                            }
                        }

                    $str = implode("<br>", $analistasArray);
                    }
                }
                $value->names = $str;
                $dados[$key] = $value;
            }
        }

        return view('cronogramaatividades.planejamento')->with('dados', $dados)->with('usuarios', $usuarios);
    }

    private function formatData($date)
    {
        $valorData = trim($date);
        $data = str_replace('/', '-', $valorData);
        $formated = date('Y-m-d', strtotime($data));
        return $formated;
    }

    public function loadPlanejamento()
    {
        return view('cronogramaatividades.Loadplanejamento');
    }
    
    public function alterar(Request $request)
    {
        $input = $request->all();
        if (array_key_exists('inicio_aviso', $input) || array_key_exists('limite', $input)) {
            if (strtotime($input['inicio_aviso']) > strtotime($input['limite'])) {
                return redirect()->back()->with('status', 'Favor informar as datas corretamente');
            }
        }

        if ($input['id_atividade'] != 0) {
            $obj = CronogramaAtividade::findOrFail($input['id_atividade']);
            if (array_key_exists('inicio_aviso', $input) && !empty($input['inicio_aviso'])) {
                $obj->inicio_aviso = $input['inicio_aviso'];
            }

            if (array_key_exists('limite', $input) && !empty($input['limite'])) {
                $obj->limite = $input['limite'];
            }

            if (array_key_exists('Id_usuario_analista', $input) && !empty($input['Id_usuario_analista'])) {
                $obj->Id_usuario_analista = $input['Id_usuario_analista'];
            }

            if (array_key_exists('data_atividade', $input) && !empty($input['data_atividade'])) {
                $obj->data_atividade = $input['data_atividade'];
            }

        $obj->save();

        }

        if ($input['id_atividade'] == 0) {
            if (empty($input['periodo_apuracao']) || empty($input['Emp_id'])) {
                return redirect()->back()->with('status', 'É necessário informar a empresa e o período para busca dos registros a serem atualizados.');
            }
            $input['periodo_apuracao'] = str_replace('/', '', $input['periodo_apuracao']);

            $current = DB::Select('select id from cronogramaatividades where periodo_apuracao = '.$input['periodo_apuracao'].' and emp_id = '.$input['Emp_id'].'');
            
            foreach ($current as $key => $val) {
                $obj = CronogramaAtividade::findOrFail($val->id);

                if (array_key_exists('inicio_aviso', $input) && !empty($input['inicio_aviso'])) {
                    $obj->inicio_aviso = $input['inicio_aviso'];
                }

                if (array_key_exists('limite', $input) && !empty($input['limite'])) {
                    $obj->limite = $input['limite'];
                }

                if (array_key_exists('Id_usuario_analista', $input) && !empty($input['Id_usuario_analista'])) {
                    $obj->Id_usuario_analista = $input['Id_usuario_analista'];
                }

                if (array_key_exists('data_atividade', $input) && !empty($input['data_atividade'])) {
                    $obj->data_atividade = $input['data_atividade'];
                }

            $obj->save();
            }
        }

        return redirect()->back()->with('status', 'Registro Atualizado com sucesso');
    }
    public function excluir(Request $request)
    {
        $input = $request->all();

        if (array_key_exists('periodo_apuracao', $input)) {

            if (empty($input['periodo_apuracao'])) {
                return redirect()->back()->with('status', 'Favor informar o período desejado para exclusão');
            }

            $input['periodo_apuracao'] = str_replace('/', '', $input['periodo_apuracao']);
            
            $current = DB::Select('select id from cronogramaatividades where periodo_apuracao = '.$input['periodo_apuracao'].' and emp_id = '.$input['Emp_id'].'');
            
            if (!empty($current)) {
                foreach ($current as $strls => $vlr) {
                    $obj = CronogramaAtividade::findOrFail($vlr->id);
                    $obj->delete();
                }
            }
        }
        if (array_key_exists('idAtividade', $input)) {
            $id = $input['idAtividade'];
            $obj = CronogramaAtividade::findOrFail($id);
            $obj->delete();
        }

    return redirect()->back()->with('status', 'Registros excluídos com sucesso');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $usuarios = User::selectRaw("concat(name, ' - ( ', email, ' )') as nome_e_mail, id")->lists('nome_e_mail', 'id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $regras = [''=>''];
        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->lists('razao_social','id'); //Unidades Federais

        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id'); //Unidades Federais
        $municipios = [''=>''];

        return view('cronogramaatividades.create')->with('usuarios', $usuarios)
                                        ->with('empresas',$empresas)
                                        ->with('regras',$regras)
                                        ->with('estabelecimentos',$estabelecimentos)
                                        ->with('tributos',$tributos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeEstabelecimento(Request $request)
    {
        $input = $request->all();
        if (empty($input['periodo_apuracao_estab']) || empty($input['multiple_select_estabelecimentos_frm'])) {
            return redirect()->back()->with('status', 'Favor informar quais estabelecimentos e qual o período');
        }
        
        foreach ($input['select_tributo_estab'] as $key => $trib) {
            $tributo = explode(',', $trib);
        }

        foreach ($tributo as $key => $idTrib) {
            foreach ($input['multiple_select_estabelecimentos_frm'] as $x => $value) {
                $var[$key]['tributo'] = $idTrib; 
                $var[$key]['estabelecimento'] = $value; 
            }
        }

        $input['periodo_apuracao_estab'] = str_replace('/', '', $input['periodo_apuracao_estab']);
        $periodo_fim = $input['periodo_apuracao_estab'];
        $periodo_ini = $input['periodo_apuracao_estab'];

        foreach ($var as $k => $x) {
            $arr = explode(',', $x['estabelecimento']);

            foreach ($arr as $chave => $id) {
                $this->cronogramageracaoEstab($x['tributo'], $id, $periodo_ini, $periodo_fim);
            }
            
        }
    return redirect()->back()->with('status', 'Atividades geradas com sucesso');
    }

    public function cronogramageracaoEstab($id_tributo,$id_estab,$periodo_ini,$periodo_fin) 
    {
        set_time_limit(0);
        $estabelecimento = Estabelecimento::findOrFail($id_estab);
        if ($periodo_ini==$periodo_fin) {
            Artisan::call('generatecronograma:single', [
                'cnpj' => $estabelecimento->cnpj, 'codigo' => $estabelecimento->codigo, 'tributo_id' => $id_tributo, 'periodo_ini' => $periodo_ini
            ]);
        } 
        $exitCode = Artisan::output();
    }

    public function storeEmpresa(Request $request)
    {
        set_time_limit(0);
        $input = $request->all();
        if (empty($input['periodo_apuracao_emps']) || empty($input['multiple_select_empresas_frm'])) {
            return redirect()->back()->with('status', 'Favor informar quais empresas e qual o período');
        }
        foreach ($input['multiple_select_empresas_frm'] as $x => $value) {
        }
        $input['periodo_apuracao_emps'] = str_replace('/', '', $input['periodo_apuracao_emps']);

        $var = explode(',', $value);
        foreach ($var as $chave => $id) {
            $periodo = $input['periodo_apuracao_emps'];
            $this->cronogramageracaoEmps($periodo, $id);
        }
        return redirect()->back()->with('status', 'Atividade gerada com sucesso');
    }

    public function cronogramageracaoEmps($periodo,$id_emp) {
        $empresa = Empresa::findOrFail($id_emp);
        
        $warning = false; // WARNING para periodo anterior não gerado
        if (strlen($periodo) == 4) {
            $knownDate = Carbon::create($periodo,1,1,0,0);
        } else {
            $knownDate = Carbon::create((int)substr($periodo,-4,4),(int)substr($periodo,0,2),1,0,0);
        }

        if (!$warning){
            Artisan::call('generatecronograma:all', [
                'periodo' => $periodo, 'empresa' => $empresa->cnpj
            ]);

            $exitCode = Artisan::output();
        }
    }


    public function store(Request $request)
    {
        $input = $request->all();

        CronogramaAtividade::create($input);

        return redirect()->route('cronogramaatividades.index')->with('status', 'Atividade adicionada com sucesso!');
    }

    public function show($id)
    {
        //
    }


    public function Gerarsemanal()
    {
        return view('cronogramaatividades.generateCalendarSemanal');
    }

    public function GerarchecklistCron()
    {
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');

        return view('cronogramaatividades.generateChecklist')->with('empresas',$empresas);
    }

    public function GerarConsulta()
    {
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        
        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->lists('razao_social','id');
        
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $status = array('1' => 'Não efetuada', '2' => 'Em aprovação', '3' => 'Entregue');
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $analistas = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');

        return view('cronogramaatividades.generateConsulta')->with('empresas',$empresas)->with('estabelecimentos',$estabelecimentos)->with('analistas',$analistas)->with('tributos',$tributos)->with('status', $status);
    }

    public function ConsultaCronograma(Request $request)
    {
        $input = $request->all();

        if (!isset($input['data_inicio']) || empty($input['data_inicio'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (!isset($input['data_fim']) || empty($input['data_fim'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (!isset($input['empresas_selected']) || empty($input['empresas_selected'])) {
            return redirect()->back()->with('status', 'Favor informar a(s) empresa(s)');
        }

        $input['data_inicio'] = implode("/", array_reverse(explode("-", $input['data_inicio']))); 
        $input['data_fim'] = implode("/", array_reverse(explode("-", $input['data_fim']))); 

        $dateStart      = $input['data_inicio'];
        $dateStart      = implode('-', array_reverse(explode('/', substr($dateStart, 0, 10)))).substr($dateStart, 10);
        $dateStart      = new \DateTime($dateStart);

        $dateEnd        = $input['data_fim'];
        $dateEnd        = implode('-', array_reverse(explode('/', substr($dateEnd, 0, 10)))).substr($dateEnd, 10);
        $dateEnd        = new \DateTime($dateEnd);
        $dateRange = array();
        while($dateStart <= $dateEnd){
            $dateRange[] = $dateStart->format('Y-m-d');
            $dateStart = $dateStart->modify('+1day');
        }

        $datas = $dateRange;

        //datas
        $string['Datas'] = '';
        foreach ($datas as $k => $v) {
            $string['Datas'] .= "'".$v."',";
        }
        $string['Datas'] = substr($string['Datas'], 0, -1);

        //empresas
        $string['emps'] = '';
        foreach ($input['empresas_selected'] as $k => $v) {
            $string['emps'] .= $v.",";
        }
        $string['emps'] = substr($string['emps'], 0, -1);
        
        //analistas
        $string['analista_selected'] = '';
        if (!empty($input['analista_selected'])) {
            foreach ($input['analista_selected'] as $k => $v) {
                $string['analista_selected'] .= $v.",";
            }
            $string['analista_selected'] = substr($string['analista_selected'], 0, -1);
        }

        $string['estabelecimentos'] = '';
        if (!empty($input['estabelecimento_selected'])) {
            foreach ($input['estabelecimento_selected'] as $k => $v) {
                $string['estabelecimentos'] .= $v.",";
            }
        $string['estabelecimentos'] = substr($string['estabelecimentos'], 0, -1);
        }

        $string['tributos'] = '';
        if (!empty($input['tributos_selected'])) {
            foreach ($input['tributos_selected'] as $k => $v) {
                $string['tributos'] .= $v.",";
            }
        $string['tributos'] = substr($string['tributos'], 0, -1);
        }
        
        $string['status'] = '';
        if (!empty($input['status_selected'])) {
            foreach ($input['status_selected'] as $k => $v) {
                $string['status'] .= $v.",";
            }
        $string['status'] = substr($string['status'], 0, -1);
        }
        $user = User::findOrFail(Auth::user()->id);
        $query = "SELECT 
                    DATE_FORMAT(A.data_atividade, '%d/%m/%Y %H:%i:%s') as limite,
                    B.codigo as codigo,
                    B.cnpj as CNPJ,
                    D.nome as Tributo,
                    A.descricao as Atividade,
                    E.name as UsuarioAnalista,
                    A.status
                FROM 
                    cronogramaatividades A 
                INNER JOIN 
                    estabelecimentos B on A.estemp_id = B.id
                INNER JOIN 
                    regras C on A.regra_id = C.id
                INNER JOIN 
                    tributos D on C.tributo_id = D.id
                LEFT JOIN 
                    users E on A.Id_usuario_analista = E.id ";
        
        $query .= " WHERE DATE_FORMAT(A.data_atividade, '%Y-%m-%d') in (".$string['Datas'].") ";
        $query .= " AND A.emp_id in (".$string['emps'].") ";

        if (!empty($string['analista_selected']) && !$user->hasRole('analyst')) {
            $query .= " AND A.Id_usuario_analista in (".$string['analista_selected'].") ";
        }

        if (!empty($string['estabelecimentos'])) {
            $query .= " AND A.estemp_id in (".$string['estabelecimentos'].") ";
        }

        if (!empty($string['tributos'])) {
            $query .= " AND D.id in (".$string['tributos'].") ";
        }

        if (!empty($string['status'])) {
            $query .= " AND A.status in (".$string['status'].") ";
        }

        if ($user->hasRole('analyst')){
            $query .= ' AND A.Id_usuario_analista = '.$user->id;
        }

        $dados = DB::select($query);

    return view('cronogramaatividades.ConsultaCronograma')->with('dados',$dados);
    }

    public function ChecklistCron(Request $request)
    {
        $input = $request->all();
        
        if (empty($input['empresas_selected'])) {
            return redirect()->back()->with('status', 'Favor informar ao menos uma empresa');
        }
        
        if (empty($input['periodo_apuracao'])) {
            return redirect()->back()->with('status', 'Informar o período desejado para busca');
        }

    $empresas = '';

    $periodo_busca = str_replace("/", "", $input['periodo_apuracao']);
    foreach($input['empresas_selected'] as $key => $id) {
        $empresas .= $id.',';
    }
    
    $empresas = substr($empresas,0,-1);
    
    $queryCron = "SELECT 
                    A.descricao,
                    DATE_FORMAT(A.limite, '%d/%m/%Y') AS limite,
                    B.razao_social,
                    C.codigo,
                    C.cnpj,
                    D.status
                FROM
                    cronogramaatividades A
                        INNER JOIN
                    empresas B ON A.emp_id = B.id
                        INNER JOIN
                    estabelecimentos C ON A.estemp_id = C.id
                        INNER JOIN
                    atividades D ON A.emp_id = D.emp_id
                        AND A.estemp_id = D.estemp_id
                        AND A.periodo_apuracao = D.periodo_apuracao
                        AND A.regra_id = D.regra_id
                WHERE
                    D.status in (1,2)";

    //Período adicionado
    $queryCron .= " AND A.periodo_apuracao = ".$periodo_busca."";
    
    //Empresas adicionadas
    $queryCron .= " AND A.emp_id in (".$empresas.")";

    //ordenação
    $queryCron .= " order by A.limite, A.emp_id, C.codigo";

    $array = DB::Select($queryCron);
    $array = json_decode(json_encode($array),true);
    $checklist = array();
    
    foreach ($array as $chave => $value) {
        $checklist[$value['razao_social']][] = $value;
    }
    foreach ($checklist as $key => $value) {
        foreach ($value as $chave => $dados) {
            $dados['periodo_apuracao'] = $input['periodo_apuracao'];
        }
    $checklist[$key][$chave] = $dados;
    }

    return view('cronogramaatividades.checklist')->with('checklist',$checklist);
    }

    public function semanal(Request $request)
    {
        $input = $request->all();

        if (!isset($input['data_inicio']) || empty($input['data_inicio'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (!isset($input['data_fim']) || empty($input['data_fim'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (strtotime($input['data_inicio']) > strtotime($input['data_fim'])) {
            return redirect()->back()->with('status', 'A data de Início não pode ser Maior que a data Final');   
        }

        $day1 =substr($input['data_fim'], -2);
        $day2 = substr($input['data_inicio'], -2); 
        $diff = $day1 - $day2;
        if ( $diff > 7) {
            return redirect()->back()->with('status', 'Desculpe essa função não permite a busca de mais de uma semana');
        }
        
        $dataSelected   = $input['data_inicio'];
        $input['data_inicio'] = implode("/", array_reverse(explode("-", $input['data_inicio']))); 
        $input['data_fim'] = implode("/", array_reverse(explode("-", $input['data_fim']))); 

        $dateStart      = $input['data_inicio'];
        $dateStart      = implode('-', array_reverse(explode('/', substr($dateStart, 0, 10)))).substr($dateStart, 10);
        $dateStart      = new \DateTime($dateStart);

        $dateEnd        = $input['data_fim'];
        $dateEnd        = implode('-', array_reverse(explode('/', substr($dateEnd, 0, 10)))).substr($dateEnd, 10);
        $dateEnd        = new \DateTime($dateEnd);
     
        $dateRange = array();
        while($dateStart <= $dateEnd){
            $dateRange[] = $dateStart->format('Y-m-d');
            $dateStart = $dateStart->modify('+1day');
        }
     
        $datas = $dateRange;

        $user_id = Auth::user()->id;
        $events = [];
        
        $vall = count($datas);
        $datasB = '"';
        foreach ($datas as $key => $dataSing) {
            $datasB .= $dataSing.'","';
        }
        $datasB = substr($datasB, 0,-2);
        
        $user = User::findOrFail(Auth::user()->id);
            $atividades_estab = DB::table('cronogramaatividades')
                ->join('estabelecimentos', 'estabelecimentos.id', '=', 'cronogramaatividades.estemp_id')
                ->select('cronogramaatividades.id', 'cronogramaatividades.descricao', 'estabelecimentos.codigo','cronogramaatividades.limite','cronogramaatividades.data_atividade', 'cronogramaatividades.status')
                ->whereRaw('DATE_FORMAT(cronogramaatividades.data_atividade, "%Y-%m-%d") in ('.$datasB.')')
                ->where('cronogramaatividades.estemp_type','estab');
            
            if ($user->hasRole('analyst')){
                $atividades_estab = $atividades_estab->where('cronogramaatividades.Id_usuario_analista', $user->id);
            }

            $atividades_estab = $atividades_estab->get();
        $b = 0;
        foreach($atividades_estab as $atividade) {
            $cor = 'green';
            if (!empty($atividade->data_atividade) && (strtotime($atividade->limite) < strtotime($atividade->data_atividade)) || $b) {
                $cor = 'red';
                $b = 1;
            }

            $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                'Atividades', 
                true, 
                substr($atividade->data_atividade, 0,10), 
                substr($atividade->data_atividade, 0,10), 
                $atividade->id, 
                ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
            );
        }

        $atividades_emp = DB::table('cronogramaatividades')
            ->join('empresas', 'empresas.id', '=', 'cronogramaatividades.estemp_id')
            ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'empresas.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
            ->whereRaw('DATE_FORMAT(cronogramaatividades.data_atividade, "%Y-%m-%d") in ('.$datasB.')')
            ->where('cronogramaatividades.estemp_type','emp');
     
            if ($user->hasRole('analyst')){
                $atividades_emp = $atividades_emp->where('cronogramaatividades.Id_usuario_analista', $user->id);
            }
     
            $atividades_emp = $atividades_emp->get();

        $a = 0;
        foreach($atividades_emp as $atividade) {

            $cor = 'green';
            if (!empty($atividade->data_atividade) && (strtotime($atividade->limite) < strtotime($atividade->data_atividade)) || $a) {
                $cor = 'red';
                $a = 1;
            }

            $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                'Atividades',
                true, 
                substr($atividade->data_atividade, 0,10),
                substr($atividade->data_atividade, 0,10),
                $atividade->id,
                ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
            );
        }

        $day = 0;
        $dayofweek = date('w', strtotime($dataSelected));

        //Geração do calendario
        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
                'lang' => 'pt',
                'firstDay' => $dayofweek,
                'aspectRatio' => 30,
                'allDayText' => 'Atividades',
                'eventLimit' => 300,
                'defaultDate' => $dataSelected, 
                'header' => [ 'left' => '', 'center'=>'title', 'right' => ''] , 
                'defaultView' => 'agendaWeek'
            ])
        ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            'viewRender' => 'function() { }'
        ]);

        return view('cronogramaatividades.calendar', compact('calendar'));
    }

    public function Gerarmensal()
    {
        // $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        // return view('cronogramaatividades.generateCalendar')->with('empresas',$empresas);

        return view('cronogramaatividades.generateCalendar');
    }

    public function mensal(Request $request)
    {
        $input = $request->all();
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            return redirect()->back()->with('status', 'Favor informar o período corretamente');
        }

        $user_id = Auth::user()->id;
        $events = [];
        $empresas = array();

        $periodo_apuracao = str_replace('/', '', $input['periodo_apuracao']);
        $feriados = $this->eService->getFeriadosNacionais();
        $feriados_estaduais = $this->eService->getFeriadosEstaduais();
        $user = User::findOrFail(Auth::user()->id);
            $atividades_estab = DB::table('cronogramaatividades')
                ->join('estabelecimentos', 'estabelecimentos.id', '=', 'cronogramaatividades.estemp_id')
                ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'estabelecimentos.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
                // ->whereIN('cronogramaatividades.emp_id', $empresas)
                ->where('cronogramaatividades.periodo_apuracao', $periodo_apuracao)
                ->where('cronogramaatividades.estemp_type','estab');

                if ($user->hasRole('analyst')){
                   $atividades_estab = $atividades_estab->where('cronogramaatividades.Id_usuario_analista', $user->id);
                }
                $atividades_estab = $atividades_estab->get();
        $b = 0;
        foreach($atividades_estab as $atividade) {
            $cor = 'green';
            if (!empty($atividade->data_atividade) && (strtotime($atividade->limite) < strtotime($atividade->data_atividade)) || $b) {
                $cor = 'red';
                $b = 1;
            }

            $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                'Atividades',
                true, 
                substr($atividade->data_atividade, 0,10), 
                substr($atividade->data_atividade, 0,10), 
                $atividade->id, 
                ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
            );
        }
        
        //MATRIZ
        $atividades_emp = DB::table('cronogramaatividades')
            ->join('empresas', 'empresas.id', '=', 'cronogramaatividades.estemp_id')
            ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'empresas.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
            ->where('cronogramaatividades.periodo_apuracao', $periodo_apuracao)
            ->where('cronogramaatividades.estemp_type','emp');

            if ($user->hasRole('analyst')){
                $atividades_emp = $atividades_emp->where('cronogramaatividades.Id_usuario_analista', $user->id);
            }

            $atividades_emp = $atividades_emp->get();

        $a = 0;
        foreach($atividades_emp as $atividade) {

            $cor = 'green';
            if (!empty($atividade->data_atividade) && (strtotime($atividade->limite) < strtotime($atividade->data_atividade)) || $a) {
                $cor = 'red';
                $a = 1;
            }

            $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                'Atividades',
                true, 
                substr($atividade->data_atividade, 0,10),
                substr($atividade->data_atividade, 0,10),
                $atividade->id,
                ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
            );
        }  
        //Geração do calendario
        $mes = substr($periodo_apuracao, 0, -4)+1;
        $ano = substr($periodo_apuracao, 2);
        if (strlen($mes) == 1) {
            $mes = '0'.$mes;
        }
        $dataAcima = $ano.'-'.$mes.'';
        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
                'lang' => 'pt',
                'firstDay' => 1,
                'defaultDate' => $dataAcima,
                'aspectRatio' => 2.5,
                'header' => ['left' => 'prev.next', 'center'=>'title'] //, 'right' => 'month,agendaWeek'
            ])
        ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            'viewRender' => 'function() { }'
        ]);

        return view('cronogramaatividades.calendar', compact('calendar'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $atividade = Atividade::findOrFail($id);

        if (sizeof($atividade->retificacoes)>0 || $atividade->status>1) {
            return redirect()->route('cronogramaatividades.index')->with('error', 'Atividade já entregue, impossível cancelar!');
        } else {
            $atividade->delete();
        }


        return redirect()->route('cronogramaatividades.index')->with('status', 'Atividade cancelada com sucesso!');
    }

    public function retificar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        foreach($atividade->retificacoes as $el) {
            if ($el->status<3) {
                Session::flash('message', 'Atividade de retificação já em aberto!');
                return redirect()->route('arquivos.show',$atividade->id);
            }
        }
        $retificacao = new CronogramaAtividade;

        $retificacao->descricao = str_replace('Entrega','Retificacao',$atividade->descricao);
        $retificacao->recibo = $atividade->recibo;
        $retificacao->status = 1;
        $retificacao->regra_id = $atividade->regra_id;
        $retificacao->emp_id = $atividade->emp_id;
        $retificacao->estemp_id = $atividade->estemp_id;
        $retificacao->estemp_type = $atividade->estemp_type;
        $retificacao->periodo_apuracao = $atividade->periodo_apuracao;
        $retificacao->inicio_aviso = $atividade->inicio_aviso;
        $retificacao->limite = $atividade->limite;
        $retificacao->tipo_geracao = 'R';
        $retificacao->arquivo_entrega = '-';
        $retificacao->retificacao_id = $atividade->id;

        $retificacao->save();
        $lastInsertedId= $retificacao->id;

        /* NOTIFICAÇÃO */
        $user = User::findOrFail(Auth::user()->id);
        $entregador = User::findOrFail($atividade->usuario_entregador);
        $subject = "BravoTaxCalendar - Pedido retificação atividade";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = ' Foi efetuado um pedido de retificação para a "'.$atividade->descricao.' - COD.'.$atividade->estemp->codigo.'".';
        $data['messageLines'][] = 'Coordenador: '.$user->name;
        
        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from atividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }
        
        $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];
        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        return redirect()->route('entregas.index')->with('status', 'Atividade ('.$lastInsertedId.') de retificação gerada com sucesso.');

    }

    public function aprovar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        $atividade->status = 3;
        $atividade->usuario_aprovador = Auth::user()->id;
        $atividade->data_aprovacao = date("Y-m-d H:i:s");
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --APROVADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Aprovada, atividade concluída.';
        $data['messageLines'][] = 'Coordenador: '.$user->name;

        //$this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        return redirect()->route('entregas.index')->with('status', 'Atividade aprovada com sucesso!');
    }

    public function reprovar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --REPROVADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Reprovada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';

        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from atividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }
        $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        // Delete the file
        $tipo = $atividade->regra->tributo->tipo;
        $tipo_label = 'UNDEFINED';
        switch($tipo) {
            case 'F':
                $tipo_label = 'FEDERAIS'; break;
            case 'E':
                $tipo_label = 'ESTADUAIS'; break;
            case 'M':
                $tipo_label = 'MUNICIPAIS'; break;
        }
        $destinationPath = substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/'.$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        File::delete(public_path('uploads/'.$destinationPath));
        $exception = '';
        if (File::exists($destinationPath)) {
            $exception = 'O arquivo não foi deletado, contatar o administrador.';
        }
        return redirect()->route('entregas.index')->with('status', 'Atividade reprovada com sucesso! '.$exception);
    }

    public function cancelar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        if (sizeof($atividade->retificacoes)>0) {
            return redirect()->route('cronogramaatividades.index')->with('status', 'Não foi possivel cancelar, porque existem retificações! ');
        }

        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --CANCELADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Cancelada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';

        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from cronogramaatividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }
        $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        // Delete the file
        $tipo = $atividade->regra->tributo->tipo;
        $tipo_label = 'UNDEFINED';
        switch($tipo) {
            case 'F':
                $tipo_label = 'FEDERAIS'; break;
            case 'E':
                $tipo_label = 'ESTADUAIS'; break;
            case 'M':
                $tipo_label = 'MUNICIPAIS'; break;
        }
        $destinationPath = substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/'.$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        File::delete(public_path('uploads/'.$destinationPath));
        $exception = '';
        if (File::exists($destinationPath)) {
            $exception = 'Não foi possivel cancelar o arquivo, por favor contatar o administrador de sistema.';
        }
        return redirect()->route('cronogramaatividades.index')->with('status', 'Entrega atividade cancelada com sucesso! '.$exception);
    }
}

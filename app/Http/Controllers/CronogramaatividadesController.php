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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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
        $atividades = Atividade::select('*')
                        ->with('regra')->with('regra.tributo')->with('estemp')
                        ->orderBy('status','asc')->orderBy('limite','asc');

        if($filter_cnpj = $request->get('cnpj')){

            if (substr($filter_cnpj, -6, 4) == '0001') {
                $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
                $type = 'emp';
            } else {
                $estemp = Estabelecimento::select('id')->where('cnpj', $filter_cnpj)->get();
                $type = 'estab';
            }

            if (sizeof($estemp) > 0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type', $type);
            } else {
                $atividades = new Collection();
            }

        }

        if($filter_codigo = $request->get('codigo')){

            if ($filter_codigo == '1001') {
                $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
                $type = 'emp';
            } else {
                $estemp = Estabelecimento::select('id')->where('codigo','like','%'.$filter_codigo)->get();
                $type = 'estab';
            }

            if (sizeof($estemp)>0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
            } else {
                $atividades = new Collection();
            }

        }

        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }

        return Datatables::of($atividades)->make(true);
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
        $estabelecimentos = Estabelecimento::selectRaw("razao_social, id")->lists('razao_social','id'); //Unidades Federais
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
        foreach ($input['multiple_select_estabelecimentos_frm'] as $x => $value) {
        }
        $tributo = $input['select_tributo_estab'];
        $input['periodo_apuracao_estab'] = str_replace('/', '', $input['periodo_apuracao_estab']);
        $periodo_fim = $input['periodo_apuracao_estab'];
        $periodo_ini = $input['periodo_apuracao_estab'];

        $var = explode(',', $value);
        foreach ($var as $chave => $id) {
            return redirect()->route('estabelecimentos.cronogramageracao', [$tributo, $id, $periodo_ini, $periodo_fim] );
        }
    }

    public function storeEmpresa(Request $request)
    {
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
            return redirect()->route('empresas.cronogramageracao', [$periodo, $id] );
        }
    }

    public function store(Request $request)
    {

        $input = $request->all();

        CronogramaAtividade::create($input);

        return redirect()->route('cronogramaatividades.index')->with('status', 'Atividade adicionada com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
        $aprovacao = false;
        $aprovacao_referer = $_SERVER['HTTP_REFERER'];
        $pos = strpos( $aprovacao_referer, 'aprovacao' );
        if ($pos) {
            $aprovacao = true;
        }
        
        $atividade = CronogramaAtividade::findOrFail($id);
        $destinationPath = '#';

        if ($atividade->tipo_geracao == 'R') {
            $atividade = CronogramaAtividade::findOrFail($atividade->retificacao_id);
        }

        if ($atividade->status > 1) {
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
            $destinationPath = url('uploads') .'/'. substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj . '/' . $tipo_label . '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        }
        return view('atividades.show')->withAtividade($atividade)->withDownload($destinationPath)->with('aprovacao', $aprovacao);

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
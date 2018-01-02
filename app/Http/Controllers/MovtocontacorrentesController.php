<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Movtocontacorrente;
use App\Services\EntregaService;
use App\Models\Statusprocadm;
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

class MovtocontacorrentesController extends Controller
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
        return view('movtocontacorrentes.index');
    }

    public function import(Request $request = null)
    {
        return view('movtocontacorrentes.import');
    }

    public function action_valid_import(Request $request)
    {
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
        
        echo json_encode(array('success'=>true, 'dataApuracaoDiferente'=>false));exit;
                
    }

    public function action_import(Request $request)
    {
        $input = $request->all();
        
        if (empty($input['file_csv'])) {

            Session::flash('alert', 'Informar arquivo CSV para realizar importação');
            return redirect()->route('movtocontacorrentes.import');
        }

        $path = Input::file('file_csv')->getRealPath();
        $f = fopen($path, 'r');
        
        if (!$f) {

            Session::flash('alert', 'Arquivo inválido para operação');
            return redirect()->route('movtocontacorrentes.import');
        }

        $movtoID = DB::table('movtocontacorrentes')->orderBy('id', 'desc')->limit(1)->get();
        if (!empty($movtoID[0])) {
            $id = $movtoID[0]->id;
        } else {
            $id = 0;
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
                return redirect()->back()->with('movtocontacorrentes.import');
            }

            /*
            //valida se já existe periodo cadastrado
            $movto = Movtocontacorrente::where('periodo_apuracao', '=', $registro[0])->first();
            if (!empty($movto->id)) {

                DB::rollBack();
                Session::flash('alert', 'Já existem dados com mês '.$registro[0]);
                return redirect()->back()->with('movtocontacorrentes.import');
            }*/

            //valida periodo de apuracao
            $value = explode('/', $registro[0]);
            if ((empty($value[0]) || empty($value[1])) || (!is_numeric($value[0]) || !is_numeric($value[1])) ) {

                DB::rollBack();
                Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
                return redirect()->back()->with('movtocontacorrentes.import');
            }

            if (!checkdate($value[0], '01', $value[1])) {

                DB::rollBack();
                Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
                return redirect()->back()->with('movtocontacorrentes.import');
            }   

            $status = str_replace(" ", "", $registro[6]);
            if ($status == 'EMANDAMENTO') {
                $status_id = 2;
            }

            else if ($status == 'BAIXADO') {
                $status_id = 1;
            }
            
            //populando array para insert
            $array['periodo_apuracao']      = $registro[0];
            $array['estabelecimento_id']    = $estabelecimento->id;
            $array['vlr_guia']              = $registro[2];
            $array['vlr_gia']               = $registro[3];
            $array['vlr_sped']              = $registro[4];
            $array['vlr_dipam']             = $registro[5];
            $array['status_id']             = $status_id;
            $array['observacao']            = $registro[7];
            $array['dipam']                 = 'S';

            if ($registro[5] == 'S/M') {
                $array['vlr_dipam'] = 0;
                $array['dipam']     = 'N';
            }

            Movtocontacorrente::create($array);
            $i++;
        }

        DB::commit();
        return redirect()->back()->with('status', 'Importação realizada com sucesso!');
    }

    public function anyData(Request $request)
    {
	    $movtocontacorrentes = Movtocontacorrente::join('estabelecimentos', 'movtocontacorrentes.estabelecimento_id', '=', 'estabelecimentos.id')->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')->leftjoin('statusprocadms', 'movtocontacorrentes.status_id', '=', 'statusprocadms.id')->select(
                'movtocontacorrentes.*',
                'movtocontacorrentes.id as IdMovtoContaCorrente',
                'estabelecimentos.*',
                'municipios.*', 
                DB::raw('(IFNULL(statusprocadms.descricao, "")) as descricaoStatus'),
                DB::raw('(CASE WHEN vlr_guia = vlr_gia AND vlr_gia = vlr_sped AND (dipam = "N" OR (vlr_dipam = vlr_guia AND vlr_gia = vlr_dipam AND vlr_sped = vlr_dipam)) THEN 1 ELSE 0 END) as diferenca'),
                DB::raw('(CASE WHEN dipam = "S" THEN vlr_dipam ELSE "S/M" END) as dipam'),
                DB::raw('substring(observacao, 1, 5) as observacaoSubstr')
            )
            ->with('estabelecimentos')->with('estabelecimentos.municipio')->with('statusprocadm');

        if ($filter_cnpj = $request->get('cnpj')){
            $cnpj = preg_replace("/[^0-9]/","",$filter_cnpj);
            $estabelecimento = Estabelecimento::select('id')->where('cnpj', $cnpj)->get();
            if (sizeof($estabelecimento) > 0) {
                $movtocontacorrentes = $movtocontacorrentes->where('estabelecimento_id', $estabelecimento[0]->id);
            }else {
                $movtocontacorrentes = new Collection();
            }
        }

        if ($filter_area = $request->get('area')){
            
            $estabelecimento = Estabelecimento::select('id')->where('codigo', $filter_area)->get();
            if (sizeof($estabelecimento) > 0) {
                $movtocontacorrentes = $movtocontacorrentes->where('estabelecimento_id', $estabelecimento[0]->id);
            }else {
                $movtocontacorrentes = new Collection();
            }
        }

        if ($filter_periodo = $request->get('periodo')){
            $movtocontacorrentes = $movtocontacorrentes->where('periodo_apuracao', $filter_periodo);
        }

        $array = array();
        $estabelecimentos = Estabelecimento::select('id')->where('empresa_id', $this->s_emp->id)->get();
        foreach($estabelecimentos as $row) {
            $array[] = $row->id;
        }

        $movtocontacorrentes = $movtocontacorrentes->whereIn('estabelecimento_id', $array);
        
        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }
        
        return Datatables::of($movtocontacorrentes)->make(true);
    }

    public function search(Request $request = null)
    {
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
                                    SUM(if(vlr_guia = vlr_gia AND vlr_sped = vlr_gia AND (dipam = "N" OR (vlr_dipam = vlr_guia AND vlr_gia = vlr_dipam AND vlr_sped = vlr_dipam)), 1, 0)) as s_diferenca,
                                    SUM(if(vlr_guia <> vlr_gia OR vlr_sped <> vlr_gia OR (dipam = "S" AND (vlr_dipam <> vlr_guia OR vlr_dipam <> vlr_sped OR vlr_dipam <> vlr_gia)), 1, 0)) as diferenca,
                                    COUNT(*) as total
                                      FROM movtocontacorrentes a
                                      inner join estabelecimentos b on a.estabelecimento_id = b.id
                                      inner join municipios c on b.cod_municipio = c.codigo 
                                      WHERE '.$where.'
                                      group by c.uf');
       
        return view('movtocontacorrentes.search')
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
        
        $status = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
        $data = $request->session()->all();
        $periodo_apuracao = '';
        if (!empty($data['periodo_apuracao'])) {
            $periodo_apuracao = $data['periodo_apuracao'];
            Session::forget('periodo_apuracao');
        }

       return view('movtocontacorrentes.create')->with('periodo_apuracao', $periodo_apuracao)->with('status', $status);
    }


    public function edit($id)
    {   
        $status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
        $movtocontacorrentes = Movtocontacorrente::findOrFail($id);
        $movtocontacorrentes->vlr_gia  = number_format($movtocontacorrentes->vlr_gia, 2, ',', '.');
        $movtocontacorrentes->vlr_guia = number_format($movtocontacorrentes->vlr_guia, 2, ',', '.');
        $movtocontacorrentes->vlr_sped = number_format($movtocontacorrentes->vlr_sped, 2, ',', '.');
        if ($movtocontacorrentes->dipam == 'S') {
            $movtocontacorrentes->vlr_dipam = number_format($movtocontacorrentes->vlr_dipam, 2, ',', '.');
        }
        
        return view('movtocontacorrentes.edit')->withMovtocontacorrentes($movtocontacorrentes)->with('status', $status);
    }


    public function update(Request $request, $id)
    {   
        $movtocontacorrentes = Movtocontacorrente::findOrFail($id);
        $input = $request->all();

        $this->validate($request, [
            'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
            'estabelecimento_id' => 'required',
            'vlr_guia' => 'required',
            'vlr_gia' => 'required',
            'vlr_sped' => 'required',
            'status_id' => 'required',
            'observacao' => 'required'
        ],
        $messages = [
            'periodo_apuracao.required' => 'Informar um periodo de apuração',
            'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
            'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
            'vlr_guia.required' => 'Informar Valor Guia.',
            'vlr_gia.required' => 'Informar Valor Gia.',
            'vlr_sped.required' => 'Informar Valor Sped.',
            'status_id.required' => 'Informar Status.',
            'observacao.required' => 'Informar Observação.'

        ]);

        if (!empty($input['dipam']) && !$input['vlr_dipam']) {
            Session::flash('alert', 'Informar valor Dipam');
            return redirect()->route('movtocontacorrentes.edit', $id);
        }   

        $input['vlr_guia']          =  $this->formatar_valor($input['vlr_guia']);
        $input['vlr_gia']           =  $this->formatar_valor($input['vlr_gia']);
        $input['vlr_sped']          =  $this->formatar_valor($input['vlr_sped']);
        $input['usuario_update']    = Auth::user()->email;

        if (!empty($input['dipam']) && $input['dipam'] == 'S') {
            $input['vlr_dipam'] =  $this->formatar_valor($input['vlr_dipam']);
        } else {
            $input['dipam'] = 'N';
        }
        

        $movtocontacorrentes->fill($input)->save();
        return redirect()->back()->with('status', 'Conta Corrente atualizada com sucesso!');
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
            'vlr_guia' => 'required',
            'vlr_gia' => 'required',
            'vlr_sped' => 'required',
            'status_id' => 'required',
            'observacao' => 'required'
        ],
        $messages = [
            'periodo_apuracao.required' => 'Informar um periodo de apuração',
            'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
            'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
            'vlr_guia.required' => 'Informar Valor Guia.',
            'vlr_gia.required' => 'Informar Valor Gia.',
            'vlr_sped.required' => 'Informar Valor Sped.',
            'status_id.required' => 'Informar Status.',
            'observacao.required' => 'Informar Observação.'
        ]);

        if (!empty($input['dipam']) && !$input['vlr_dipam']) {
            Session::flash('alert', 'Informar valor Dipam');
            return redirect()->route('movtocontacorrentes.create');
        } 

        $input['vlr_guia'] =  $this->formatar_valor($input['vlr_guia']);
        $input['vlr_gia']  =  $this->formatar_valor($input['vlr_gia']);
        $input['vlr_sped'] =  $this->formatar_valor($input['vlr_sped']);
        $input['usuario_update']    = Auth::user()->email;

        if (!empty($input['dipam']) && $input['dipam'] == 'S') {
            $input['vlr_dipam'] =  $this->formatar_valor($input['vlr_dipam']);
        }

        Movtocontacorrente::create($input);
        
        $request->session()->put('periodo_apuracao', $input['periodo_apuracao']);
        return redirect()->back()->with('status', 'Registro adicionada com sucesso!');
    }

    public function delete($id)
    {   
        if (!$id) {
            return redirect()->route('movtocontacorrentes.search')->with('error', 'Informar movto para excluir');
        }

        Movtocontacorrente::destroy($id);
        return redirect()->route('movtocontacorrentes.search')->with('status', 'Registro excluido com sucesso!');
    }

    private function validate_dipam($dipam, $valor_dipam)
    {
        if ($dipam == 'S' && !$valor_dipam) {
            return false;
        }

        return true;
    }

    private function formatar_valor($valor)
    {
        if (!$valor) {
            return false;
        }

        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return $valor;
    }
}

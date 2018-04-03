<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use Auth;
use DB;
use App\Models\Empresa;
use App\Services\EntregaService;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;


class CargasController extends Controller
{
    protected $eService;
    public $s_emp = null;

    public function __construct(EntregaService $service)
    {
        if (!session()->get('seid')) {
            echo "Nenhuma empresa Selecionada.<br/><br/><a href='home'>VOLTAR</a>";
            exit;
        }

        $this->eService = $service;

        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid')); 
        }
    }

    public function index(Request $request)
    {
        if ($request->has('switch_val')) {
            $switch = Input::get('switch_val');
        } else {
            $switch = 0;
        }
        //var_dump($request);
        return view('cargas.msaf-load')->withSwitch($switch);
    }

    public function anyData(Request $request)
    {
        $seid = $this->s_emp->id;
        $estabelecimentos = Estabelecimento::select('*')->where('empresa_id', $seid)->with('municipio');

        if($filter = $request->get('ativo')){
            $estabelecimentos->where('carga_msaf_entrada',1)->where('carga_msaf_saida',1);
        }

        if($filter = $request->get('inativo')){
            $estabelecimentos->where('carga_msaf_entrada',0)->where('carga_msaf_saida',0);
        }

        return Datatables::of($estabelecimentos)->make(true);
    }

    public function grafico()
    {
        $seid = $this->s_emp->id;

        $first = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "E" as TIPO'))
            ->where('empresa_id', $seid)
            ->where('carga_msaf_entrada',1);
        $second = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "S" as TIPO'))
            ->where('empresa_id', $seid)
            ->where('carga_msaf_saida',1);
        $third = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "C" as TIPO'))
            ->where('empresa_id', $seid)
            ->where('carga_msaf_entrada',1)
            ->where('carga_msaf_saida',1);

        $grafico = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "T" as TIPO'))
            ->union($first)->union($second)->union($third)
            ->get();

        $retval = array();
        foreach($grafico as $el) {
            $retval[$el->TIPO] = $el->TOT;
        }

        return view('cargas.grafico')->with('graph_data',$retval);
    }

    public function resetData()
    {
        $estabelecimentos = Estabelecimento::all();
        foreach ($estabelecimentos as $el) {
            $el->carga_msaf_entrada = 0;
            $el->carga_msaf_saida = 0;
            $el->save();
        };

        return redirect()->back()->with('status', 'Todos os status de carga foram alterados!');
    }

    public function changeStateEntrada($status,$id) {

        $estabelecimento = Estabelecimento::findOrFail($id);
        if ($status=='1') {

            $estabelecimento->carga_msaf_entrada = 1;

        } else if ($status=='0') {

            $estabelecimento->carga_msaf_entrada = 0;

        }
        $estabelecimento->save();

        return redirect()->back()->with('status', 'O status de carga foi alterado para o estabelecimento '.$estabelecimento->codigo.'!');
    }

    public function changeStateSaida($status,$id) {

        $estabelecimento = Estabelecimento::findOrFail($id);
        if ($status=='1') {

            $estabelecimento->carga_msaf_saida = 1;

        } else if ($status=='0') {

            $estabelecimento->carga_msaf_saida = 0;

        }
        $estabelecimento->save();

        return redirect()->back()->with('status', 'O status de carga foi alterado para o estabelecimento '.$estabelecimento->codigo.'!');
    }

}

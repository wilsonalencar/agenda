<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;

class ArquivosController extends Controller
{
    public $s_emp = null;
    public function __construct()
    {
        if (!session()->get('seid')) {
            echo "Nenhuma empresa Selecionada.<br/><br/><a href='home'>VOLTAR</a>";
            exit;
        }
        
        $this->middleware('auth');

        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid')); 
        }
       
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('arquivos.index')->with('filter_cnpj',Input::get("vcn"))->with('filter_codigo',Input::get("vco"));
    }

    public function anyData(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $seid = $this->s_emp->id;

        $atividades = Atividade::select('*')->where('emp_id', $seid)->with('regra')->with('regra.tributo')->with('estemp')
            ->where('status', 3)->where('tipo_geracao','A')
            ->orderBy('data_entrega','desc');

        if ($user->hasRole('analyst') || $user->hasRole('supervisor'))
        {
            $with_user = function ($query) {
                $query->where('user_id', Auth::user()->id);
            };
            $tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
            $granted_array = array();
            foreach($tributos_granted as $el) {
                $granted_array[] = $el->id;
            }

            $atividades = $atividades->whereHas('regra.tributo', function ($query) use ($granted_array) {
                $query->whereIn('id', $granted_array);
            });

        }

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
/*
        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }
*/
        return Datatables::of($atividades)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $atividade = Atividade::findOrFail($id);
        $destinationPath = '#';
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
        return view('arquivos.show')->withAtividade($atividade)->withDownload($destinationPath);
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
        //
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

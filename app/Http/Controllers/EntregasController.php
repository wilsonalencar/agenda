<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Atividade;
use App\Http\Requests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;

class EntregasController extends Controller
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
        return view('entregas.index')->with('filter_cnpj',Input::get("vcn"))->with('filter_codigo',Input::get("vco"))->with('filter_status',Input::get("vst"));
    }

    public function anyData(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $seid = $this->s_emp->id;
        $atividades = Atividade::select('*')->where('emp_id',$seid)->with('regra')->with('regra.tributo')->with('entregador')->with('entregador.roles')->with('estemp')->orderBy('data_entrega','desc');


        if ($user->hasRole('owner') || $user->hasRole('admin') )
        {
            $atividades = $atividades->whereIn('status', [1, 2]);
            // ->whereHas('entregador.roles', function ($query) {
            //     $query->where('name', 'supervisor');
            // })
            
        } else if ($user->hasRole('supervisor')) {

            $with_user = function ($query) {
                $query->where('user_id', Auth::user()->id);
            };
            $tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
            $granted_array = array();
            foreach($tributos_granted as $el) {
                $granted_array[] = $el->id;
            }

            $atividades = $atividades->where('status','<=', 2)->whereHas('regra.tributo', function ($query) use ($granted_array) {
                $query->whereIn('id', $granted_array);
            });

        } else {

            $atividades = $atividades->where('status','<',3);  //whereHas('users', $with_user)

        }

        if($filter_cnpj = $request->get('cnpj')){

            // if (substr($filter_cnpj, -6, 4) == '0001') {
            //     $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
            //     $type = 'emp';
            // } else {
                $estemp = Estabelecimento::select('id')->where('cnpj', $filter_cnpj)->get();
                $type = 'estab';
            // }

            if (sizeof($estemp) > 0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
            } else {
                $atividades = new Collection();
            }

        }

        if($filter_codigo = $request->get('codigo')){

            // if ($filter_codigo == '1001') {
            //     $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
            //     $type = 'emp';
            // } else {
                $estemp = Estabelecimento::select('id')->where('codigo','=',$filter_codigo)->get();
                $type = 'estab';
            // }

            if (sizeof($estemp)>0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
            } else {
                $atividades = new Collection();
            }

        }

        if($filter_status = $request->get('status_filter')){

            if ($filter_status == 'E') {
                $atividades = $atividades->where('status', 1);
            }
            else if ($filter_status == 'A') {
                $atividades = $atividades->where('status', 2);
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
        return view('entregas.show')->withAtividade($atividade)->withDownload($destinationPath);
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
        $atividade = Atividade::findOrFail($id);
        $input = $request->all();
        $atividade->obs = $input['obs'];

        $atividade->save();

        return redirect()->back()->with('status', 'Comentario atualizado com sucesso!');
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


use App\Http\Requests;

class AtividadeanalistaController extends Controller
{
    protected $s_emp = null;

    public function __construct(Request $request = null)
    { 
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
        $user = User::findOrFail(Auth::user()->id);

    	$query = 'SELECT 
                        A.id,
                        C.name,
                        B.razao_social,
                        G.nome,
                        GROUP_CONCAT((SELECT 
                                    E.codigo
                                FROM
                                    estabelecimentos E
                                WHERE
                                    E.id = D.Id_estabelecimento)
                            SEPARATOR " - ") as estabelecimento
                    FROM
                        atividadeanalista A
                            INNER JOIN
                        empresas B ON A.Emp_id = B.id
                            INNER JOIN
                        users C ON A.Id_usuario_analista = C.id
                            LEFT JOIN
                        atividadeanalistafilial D ON (D.Id_atividadeanalista = A.id)
                            INNER JOIN 
                        tributos G ON A.Tributo_id = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY C.name , B.razao_social , A.id, G.nome';

        $table = DB::select($query);

        $table = json_decode(json_encode($table),true);
        return view('atividadeanalista.index')->with('table', $table);
    }

    public function anyData(Request $request)
    {
    	//
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    	//carregando dados da tela
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');


        return view('atividadeanalista.adicionar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $situation = 'status';
        $message = 'Registro inserido com sucesso';
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');

        $input = $request->all();

        if (!$this->validation($input)) {
            return redirect()->back()->with('alert', 'Já existe esta atividade para este analista.');
        }

        $create = AtividadeAnalista::create($input);
        $dados = AtividadeAnalista::findOrFail($create->id);
        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.id', 'atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $create->id)
                ->get();

        $cnpjs = json_decode(json_encode($cnpjs),true);

        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
    }

    public function validation($array)
    {
        $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->get();
       
        $find = json_decode(json_encode($find),true);

        if (count($find) > 0) {
            return false;
        }

    return true;
    }

    public function validationEdit($array)
    {
        $id = explode(',', $array['id']);
        $find = DB::table('atividadeanalista')->select('*')->where('Id_usuario_analista', $array['Id_usuario_analista'])->where('Tributo_id', $array['Tributo_id'])->where('Emp_id', $array['Emp_id'])->whereNotIn('id', $id)->get();
       
        $find = json_decode(json_encode($find),true);

        if (count($find) > 0) {
            return false;
        }

    return true;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    	//
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $situation = 'status';
        $message = 'Registro atualizado com sucesso';
        //carregando dados da tela 
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $input = $request->all();

        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.Id_estabelecimento','atividadeanalistafilial.id' ,'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $input['id'])
                ->get();
        
        $cnpjs = json_decode(json_encode($cnpjs),true);

        if (!$this->validationEdit($input)) {
            $situation = 'error';
            $message = 'Já existe atividade para o analista selecionado';
            $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['id'])),true);
            return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
        }
        $Atividade = AtividadeAnalista::findOrFail($input['id']);
        $Atividade->fill($input)->save();
        $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['id'])),true);
    
        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRLT(Request $request)
    {
        $situation = 'status';
        $message = 'Registro carregado com sucesso';
        foreach ($request->all() as $key => $value) {
            $privateid = $key; 
        }
        //carregando dados da tela 
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->lists('name','id');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.Id_estabelecimento','atividadeanalistafilial.id' ,'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $privateid)
                ->get();
        
        $cnpjs = json_decode(json_encode($cnpjs),true);

        $Atividade = AtividadeAnalista::findOrFail($privateid);
        $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($privateid)),true);
    
        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs)->with('returning', true);
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

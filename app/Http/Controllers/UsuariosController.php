<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;
use App\Services\EntregaService;

class UsuariosController extends Controller
{
    protected $eService;

    public function __construct(EntregaService $service)
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
        return view('usuarios.index');
    }

    public function anyData(Request $request)
    {
        $usuarios = User::with('roles')->select('*');

        return Datatables::of($usuarios)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //return view('auth.register');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ],
        $messages = [

        ]);

        $input = $request->all();

        User::create($input);

        return redirect()->back()->with('status', 'Usuário adicionado com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $usuario = User::findOrFail($id);


        return view('usuarios.show')->withUser($usuario);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');

        /*
        $regras = [''=>''];
        $empresas = Empresa::selectRaw("cnpj, cnpj")->lists('cnpj','cnpj');
        $estabs = Estabelecimento::selectRaw("cnpj, cnpj")->lists('cnpj','cnpj');
        $estemp = $empresas->merge($estabs);
        */
        return view('usuarios.edit')->withUser($usuario)->withTributos($tributos);
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
        $usuario = User::findOrFail($id);

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email'
        ]);

        $input = $request->all();

        $usuario->fill($input)->save();

        $tributos = Input::get('multiple_select_tributos');
        if ($tributos) {
            $usuario->tributos()->sync($tributos);
        } else {
            $usuario->tributos()->detach();
        }

        return redirect()->back()->with('status', 'User atualizado com sucesso!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $roles_count = sizeof($usuario->roles);

        if ($roles_count >0) {
            return redirect()->route('usuarios.index')->with('status', 'Usuário ainda ativo! Impossivel cancelar.');
        } else {
            $usuario->delete();
            return redirect()->route('usuarios.index')->with('status', 'Usuário cancelado com sucesso!');
        }
    }

    public function elevateRole($id) {

        $user = User::findOrFail($id);

        $isOwner = $user->hasRole('owner');
        $isAdmin = $user->hasRole('admin');
        $isManager = $user->hasRole('manager');
        $isSupervisor = $user->hasRole('supervisor');
        $isAnalyst = $user->hasRole('analyst');
        $isMsaf = $user->hasRole('msaf');
        $isUser = $user->hasRole('user');

        $owner_role = Role::where('name', '=', 'owner')->first();
        $admin_role = Role::where('name', '=', 'admin')->first();
        $manager_role = Role::where('name', '=', 'manager')->first();
        $supervisor_role = Role::where('name', '=', 'supervisor')->first();
        $analyst_role = Role::where('name', '=', 'analyst')->first();
        $msaf_role = Role::where('name', '=', 'msaf')->first();
        $user_role = Role::where('name', '=', 'user')->first();

        if ($isOwner) {
            return redirect()->back()->with('status', 'O usuário já possue o máximo nivel alcançável!');
        } else if ($isAdmin) {
            $user->attachRole($owner_role);
            $user->detachRole($admin_role);
        } else if ($isManager) {
            $user->attachRole($admin_role);
            $user->detachRole($manager_role);
        }else if ($isSupervisor) {
            $user->attachRole($manager_role);
            $user->detachRole($supervisor_role);
        }else if ($isAnalyst) {
            $user->attachRole($supervisor_role);
            $user->detachRole($analyst_role);
        } else if ($isMsaf) {
            $user->attachRole($analyst_role);
            $user->detachRole($msaf_role);
        } else if ($isUser) {
            $user->attachRole($msaf_role);
            $user->detachRole($user_role);
        } else {
            $user->attachRole($user_role);
        }

        return redirect()->back()->with('status', 'O nivel de acesso do usuário foi incrementado!');

    }

    public function decreaseRole($id) {
        $user = User::findOrFail($id);

        $isOwner = $user->hasRole('owner');
        $isAdmin = $user->hasRole('admin');
        $isManager = $user->hasRole('manager');
        $isSupervisor = $user->hasRole('supervisor');
        $isAnalyst = $user->hasRole('analyst');
        $isMsaf = $user->hasRole('msaf');
        $isUser = $user->hasRole('user');

        $owner_role = Role::where('name', '=', 'owner')->first();
        $admin_role = Role::where('name', '=', 'admin')->first();
        $manager_role = Role::where('name', '=', 'manager')->first();
        $supervisor_role = Role::where('name', '=', 'supervisor')->first();
        $analyst_role = Role::where('name', '=', 'analyst')->first();
        $msaf_role = Role::where('name', '=', 'msaf')->first();
        $user_role = Role::where('name', '=', 'user')->first();

        if ($isOwner) {
            $user->attachRole($admin_role);
            $user->detachRole($owner_role);
        } else if ($isAdmin) {
            $user->attachRole($manager_role);
            $user->detachRole($admin_role);
        } else if ($isManager) {
            $user->attachRole($supervisor_role);
            $user->detachRole($manager_role);
        } else if ($isSupervisor) {
            $user->attachRole($analyst_role);
            $user->detachRole($supervisor_role);
        } else if ($isAnalyst) {
            $user->attachRole($msaf_role);
            $user->detachRole($analyst_role);
        } else if ($isMsaf) {
            $user->attachRole($user_role);
            $user->detachRole($msaf_role);
        } else if ($isUser) {
            $user->detachRole($user_role);
        } else {
            return redirect()->back()->with('status', 'O usuário não possue credenciais de acesso!');
        }

        return redirect()->back()->with('status', 'O nivel de acesso do usuário diminuiu!');

    }

    public function sendEmailReminder($id)
    {
        $user = User::findOrFail($id);

        $this->eService->sendMail($user,array('subject'=>"Welcome to BravoTaxCalendar!"));

        return redirect()->back()->with('status', 'Envio efetuado, verificar o recebimento!');

    }

    public function sendEmailExport(Request $request)
    {
        $user = User::findOrFail($request->input('user_id'));

        $data = array('subject'=>'BravoTaxCalendar - Export Data ','messageLines'=>array());
        $data['messageLines'][] = $request->input('html_head');
        $data['messageLines'][] = $request->input('html_body');

        try {

            $this->eService->sendMail($user, $data, 'emails.export');
            echo "Envio efetuado corretamente.";

        } catch (Exception $ex) {

            echo 'Erro ao enviar as informações : '.$ex;
        }

    }

}

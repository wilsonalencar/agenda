<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use Auth;
use DB;
use App\Models\Empresa;
use App\Models\User;
use App\Models\DocumentacaoCliente;
use App\Services\EntregaService;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;


class DocumentacaoClienteController extends Controller
{
    public $answerPath;
    protected $s_emp = null;
    public $msg;

    public function __construct(Request $request = null)
    { 
        if (!Auth::guest() && !empty(session()->get('seid')))
            $this->s_emp = Empresa::findOrFail(session('seid'));
    }

    public function create(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        $input = $request->all();

        if (!empty($input)) {
            
            if (!$this->validation($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }

            $documento['emp_id'] = $this->s_emp->id;
            $documento['descricao'] = $input['descricao'];
            $documento['data_criacao'] = date('Y-m-d H:i:s');
            $documento['id_user_autor'] = Auth::user()->id;
            $documento['versao'] = $input['versao'];
            $documento['observacao'] = $input['observacao'];
            $documento['arquivo'] = $input['arquivo'];

            $this->upload($input['arquivo']);

            DocumentacaoCliente::create($documento);
            return redirect()->back()->with('status', 'Documento adicionado com sucesso.');
        }

        return view('documentacaocliente.create');
    }

    public function validation($input)
    {
        if (empty($input['descricao'])) {
            $this->msg = 'É necessário adicionar uma descrição.';
            return false;
        }
        if (empty($input['arquivo'])) {
            $this->msg = 'É necessário adicionar um documento.';
            return false;
        }

        return true;
    }

    public function index()
    {
        $table = DocumentacaoCliente::all();
        return view('documentacaocliente.index')->with('table', $table);
    }

    public function update($id, Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        $input = $request->all();
        $request = DocumentacaoCliente::findOrFail($id);

        if (!empty($input)) {
            
            if (!$this->validation($input)) {
                return redirect()->back()->with('alert', 'É necessário adicionar uma descrição.');
            }

            $documento['descricao'] = $input['descricao'];
            $documento['data_atualizacao'] = date('Y-m-d H:i:s');
            $documento['id_user_atualiza'] = Auth::user()->id;
            $documento['observacao'] = $input['observacao'];

            $request->fill($documento);
            $request->save();

            return redirect()->back()->with('status', 'Documento atualizado com sucesso.');

        }

        return view ('documentacaocliente.editar')->with('request', $request);

    }

    public function destroy($id)
    {
        if (!empty($id)) {
            DocumentacaoCliente::destroy($id);
            return redirect()->back()->with('status', 'Documento excluido com sucesso.');
        }

        $return = DocumentacaoCliente::all();
        return view('documentacaocliente.index')->withRegistros('return',$return);
    }


    public function upload($image)
    {
        // getting all of the post data
        $file = Input::file('image');
        echo "<pre>";
        print_r($file);exit;
        // checking file is valid.
        if (Input::file('image')->isValid()) {
            $atividade_id = Input::get('atividade_id');

            $atividade = Atividade::findOrFail($atividade_id);
            
            $destinationPath = 'uploads/'.$atividade_id; // upload path
            $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
            $fileName = time().'.'.$extension; // renameing image
            $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces

            Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

            //Save status
            $atividade->arquivo_comprovante = $fileName;
            $atividade->save();

            // sending back with message
            Session::flash('success', 'Upload successfully');
            return redirect()->route('arquivos.index')->with('status', 'Arquivo carregado com sucesso!');
        }
        else {
            // sending back with error message.
            Session::flash('error', 'Uploaded file is not valid');
            return redirect()->route('arquivos.index')->with('status', 'Erro ao carregar o arquivo.');
        }
    }
}

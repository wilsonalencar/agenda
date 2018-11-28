<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use App\Models\Empresa;
use App\Models\User;
use App\Models\DocumentacaoCliente;
use App\Services\EntregaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Datatables;
use App\Http\Requests;
use Auth;
use DB;


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

            if ($request->hasFile('image')) {
                $filename = $this->upload($request);
                $documento['arquivo'] = $filename;
            }

            DocumentacaoCliente::create($documento);
            return redirect()->back()->with('status', 'Documento adicionado com sucesso.');
        }

        return view('documentacaocliente.create');
    }

    public function validation($input, $edit = false)
    {
        if (empty($input['descricao'])) {
            $this->msg = 'É necessário adicionar uma descrição.';
            return false;
        }

        if (empty($input['image']) && !$edit) {
            $this->msg = 'É necessário adicionar um documento.';
            return false;
        }

        return true;
    }

    public function download($id)
    {
        $documento = DocumentacaoCliente::findOrFail($id);
        if (empty($documento->arquivo)) {
            return redirect()->back()->with('alert', 'Não existe arquivo para o documento Cadastrado.');
        }

        $way = 'fiscal/'.$documento->arquivo;
        if (!file_exists($way)) {
            return redirect()->back()->with('alert', 'Arquivo cadastrado não existe na pasta.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($way).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($way));
        flush();
        readfile($way);
    }

    public function index()
    {
        $table = DocumentacaoCliente::where('emp_id', $this->s_emp->id)->get();
        return view('documentacaocliente.index')->with('table', $table);
    }

    public function update($id, Request $request)
    {
        $input = $request->all();
        $documento = DocumentacaoCliente::findOrFail($id);

        if (!empty($input)) {
            if (!$this->validation($input, true)) {
                return redirect()->back()->with('alert', $this->msg);
            }

            $documento->descricao = $input['descricao'];
            $documento->data_atualizacao = date('Y-m-d H:i:s');
            $documento->id_user_atualiza = Auth::user()->id;
            $documento->observacao = $input['observacao'];
            
            if ($request->hasFile('image')) {
                $filename = $this->upload($request);
                $documento->arquivo = $filename;
                $documento->versao = $input['versao']+1;      
            }

            $documento->save();
            return redirect()->back()->with('status', 'Documento atualizado com sucesso.');

        }

        return view ('documentacaocliente.editar')->with('request', $documento);

    }

    public function uploadSingle(Request $request)
    {
        $input = $request->all();
        $documento = DocumentacaoCliente::findOrFail($input['id']);

        if (!empty($input)) {
               
            $documento->data_atualizacao = date('Y-m-d H:i:s');
            $documento->id_user_atualiza = Auth::user()->id;
            
            if ($request->hasFile('image')) {
                $filename = $this->upload($request);
                $documento->arquivo = $filename;
                $documento->versao = $documento->versao+1;      
            }

            $documento->save();
            return redirect()->back()->with('status', 'Documento atualizado com sucesso.');
        }

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
        $file = Input::file('image');    
        $destinationPath = 'fiscal/'; 
        
        $fileName = Input::file('image')->getClientOriginalName();
        // $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
        // $fileName = time().'.'.$extension; // renameing image
        // $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces

        Input::file('image')->move($destinationPath, $fileName); 
        return $fileName;
    }
}

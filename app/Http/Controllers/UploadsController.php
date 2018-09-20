<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Atividade;
use App\Models\CronogramaAtividade;
use App\Http\Requests;
use Auth;
use DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class UploadsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function entrega($atividade_id) {

        $usuario = User::findOrFail(Auth::user()->id);
        //$atividade = Atividade::findOrFail($atividade_id);
        $atividade = Atividade::with(['comentarios'])->findOrFail($atividade_id);

        return view('entregas.upload')->withUser($usuario)->withAtividade($atividade);
    }

    public function entregaCronograma($atividade_id) {

        $usuario = User::findOrFail(Auth::user()->id);
        //$atividade = Atividade::findOrFail($atividade_id);
        $atividade = CronogramaAtividade::findOrFail($atividade_id);

        return view('entregas.upload-cronograma')->withUser($usuario)->withAtividade($atividade);
    }

    public function entregaCronogramaData($data_atividade) {
        $usuario = User::findOrFail(Auth::user()->id);
        $atividadesFiltered = array();
        $atividades = DB::select('Select A.*, C.nome as tributo, E.uf, F.name from cronogramaatividades A left join regras B on A.regra_id = B.id left join tributos C on B.tributo_id = C.id left join estabelecimentos D on A.estemp_id = D.id left join municipios E on D.cod_municipio = E.codigo left join users F on A.Id_usuario_analista = F.id where A.data_atividade = "'.$data_atividade.'";');
        
        if (!empty($atividades)) {
            foreach ($atividades as $key => $atividade) {
                if (strtotime($atividade->limite) < strtotime($atividade->data_atividade)) {
                    $atividadesFiltered[$atividade->tributo][$atividade->uf][$atividade->status]['PrazoEstourado'][] = $atividade;
                } else {
                    $atividadesFiltered[$atividade->tributo][$atividade->uf][$atividade->status]['Prazo'][] = $atividade;
                }
            }
        }

    return view('entregas.upload-cronograma-data')->withUser($usuario)->with('atividades', $atividadesFiltered);
    }

    public function upload() {
        // getting all of the post data
        $file = array('image' => Input::file('image'));
        // setting up rules
        $rules = array('image' => 'required|mimes:pdf,zip'); //mimes:jpeg,bmp,png and for max size max:10000
        // doing the validation, passing post data, rules and the messages
        $validator = Validator::make($file, $rules);
        if ($validator->fails()) {
            // send back to the page with the input data and errors
            Session::flash('error', 'Somente arquivos ZIP ou PDF são aceitos.');
            $atividade_id = Input::get('atividade_id');
            return Redirect::to('upload/'.$atividade_id.'/entrega')->withInput()->withErrors($validator);
        }
        else {
            // checking file is valid.
            if (Input::file('image')->isValid()) {
                $atividade_id = Input::get('atividade_id');
                $atividade = Atividade::findOrFail($atividade_id);
                $estemp = $atividade->estemp;
                $regra = $atividade->regra;
                $tipo = $regra->tributo->tipo;
                $tipo_label = 'UNDEFINED';
                switch($tipo) {
                    case 'F':
                        $tipo_label = 'FEDERAIS'; break;
                    case 'E':
                        $tipo_label = 'ESTADUAIS'; break;
                    case 'M':
                        $tipo_label = 'MUNICIPAIS'; break;
                }
                $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8).'/'.$estemp->cnpj.'/'.$tipo_label.'/'.$regra->tributo->nome.'/'.$atividade->periodo_apuracao; // upload path
                $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
                $fileName = time().'.'.$extension; // renameing image
                $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces
                Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

                //Save status
                $atividade->arquivo_entrega = $fileName;
                $atividade->usuario_entregador = Auth::user()->id;
                $atividade->data_entrega = date("Y-m-d H:i:s");
                $atividade->status = 2;
                $atividade->save();
                // sending back with message
                Session::flash('success', 'Upload successfully');
                return redirect()->route('entregas.index')->with('status', 'Arquivo carregado com sucesso!');
            }
            else {
                // sending back with error message.
                Session::flash('error', 'Uploaded file is not valid');
                return redirect()->route('entregas.index')->with('status', 'Erro ao carregar o arquivo.');
            }
        }
    }    

    public function uploadCron() {
        // getting all of the post data
        $file = array('image' => Input::file('image'));
        // setting up rules
        $rules = array('image' => 'required|mimes:pdf,zip'); //mimes:jpeg,bmp,png and for max size max:10000
        // doing the validation, passing post data, rules and the messages
        $validator = Validator::make($file, $rules);
        if ($validator->fails()) {
            // send back to the page with the input data and errors
            Session::flash('error', 'Somente arquivos ZIP ou PDF são aceitos.');
            $atividade_id = Input::get('atividade_id');
            return Redirect::to('uploadCron/'.$atividade_id.'/entrega')->withInput()->withErrors($validator);
        }
        else {
            // checking file is valid.
            if (Input::file('image')->isValid()) {
                $atividade_id = Input::get('atividade_id');
                $atividade = CronogramaAtividade::findOrFail($atividade_id);
                $estemp = $atividade->estemp;
                $regra = $atividade->regra;
                $tipo = $regra->tributo->tipo;
                $tipo_label = 'UNDEFINED';
                switch($tipo) {
                    case 'F':
                        $tipo_label = 'FEDERAIS'; break;
                    case 'E':
                        $tipo_label = 'ESTADUAIS'; break;
                    case 'M':
                        $tipo_label = 'MUNICIPAIS'; break;
                }
                $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8).'/'.$estemp->cnpj.'/'.$tipo_label.'/'.$regra->tributo->nome.'/'.$atividade->periodo_apuracao; // upload path
                $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
                $fileName = time().'.'.$extension; // renameing image
                $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces
                Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

                //Save status
                $atividade->arquivo_entrega = $fileName;
                $atividade->usuario_entregador = Auth::user()->id;
                $atividade->data_entrega = date("Y-m-d H:i:s");
                $atividade->status = 2;
                $atividade->save();
                // sending back with message
                Session::flash('success', 'Upload successfully');
                return redirect()->route('entregas.index')->with('status', 'Arquivo carregado com sucesso!');
            }
            else {
                // sending back with error message.
                Session::flash('error', 'Uploaded file is not valid');
                return redirect()->route('entregas.index')->with('status', 'Erro ao carregar o arquivo.');
            }
        }
    }

}

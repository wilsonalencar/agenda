<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;


class RegrasenviolotesController extends Controller
{
    protected $eService;   
    public $msg;
    public $estabelecimento_id;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }

    public function lote_consulta(Request $request)
    {
        $standing = DB::select("SELECT 
            A.id, C.razao_social, B.nome, A.regra_geral
        FROM
            regraenviolote A
                INNER JOIN
            empresas C ON A.id_empresa = C.id
                INNER JOIN
            tributos B ON A.id_tributo = B.id");
        
        $array = json_decode(json_encode($standing),true);
        return view('regras.consulta_lote')->with('array', $array);
    }

    public function envio_lote(Request $request)
    {
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        return view('regras.envio_lote')->withTributos($tributos)->withEmpresas($empresas);
    }

    private function validaCampos($input){
        
        if (empty($input['email_1'])) {
            $this->msg = "Favor informar o e-mail obrigatório";
            return false;
        }
        if (!empty($input['email_1']) && !$this->validaEmail($input['email_1'])) {
            return false;
        }
        if (!empty($input['email_2']) && !$this->validaEmail($input['email_2'])) {
            return false;
        }

        if (!empty($input['email_3']) && !$this->validaEmail($input['email_3'])) {
            return false;
        }

        if (!$this->validaExistencia($input)) {
            return false;
        }

        return true;
    }    

    private function validaCamposCNPJ($input){
        if (empty($input['cnpj'])) {
            $this->msg = "Favor informar o cnpj para salvar";
            return false;
        }

        if (!$this->validaCNPJ($input['cnpj'], $input['id_empresa'])) {
            return false;
        }

        return true;
    }

    private function validaEmail($email) 
    {
        $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, $email)){
        return true;
        } else {
        $this->msg = 'Email é inválido, favor verificar !!';
        return false;
        }
    }

    private function validaCNPJ($cnpj, $id_empresa) 
    {        
        $existe = DB::SELECT("SELECT id FROM estabelecimentos WHERE empresa_id = ".$id_empresa." AND cnpj = ".$cnpj."");
        if (empty($existe)) {
            $this->msg = 'Esse CNPJ não consta como estabelecimento dessa empresa.';
            return false;
        }        
        $var = json_decode(json_encode($existe),true);
        foreach ($var as $key => $value) {        
        }

        $this->estabelecimento_id = $value['id'];
        return true;
    }

    private function validaExistencia($input) 
    {        
        $dados = DB::SELECT("SELECT id FROM regraenviolote WHERE id_empresa = ".$input['select_empresas']." AND id_tributo = ".$input['select_tributos']." AND regra_geral = '".$input['regra_geral']."' AND id <> ".$input['id']."");
        if (!empty($dados)) {
            $this->msg = 'Duplicidade detectada, favor verificar os dados informados';
            return false;
        }        
        return true;
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
     
        if ($input['add_cnpj']) {
            if (!$this->cnpj_save($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
            return redirect()->back()->with('status', $this->msg);
        }

        $input['regra_geral'] = 'N';
        if ($input['label_regra']) {
            $input['regra_geral'] = 'S';
        }

        
        if (!empty($input)) {
            if (!$this->validaCampos($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
        }

        foreach ($input as $key => $value) {
            if (empty($input[$key])) {
                $input[$key] = 'NULL';
            }
        }

        //edit
        if ($input['id'] > 0) {
            $standing = "UPDATE regraenviolote SET id_empresa = ".$input['select_empresas'].", id_tributo = ".$input['select_tributos'].", email_1 = '".$input['email_1']."', regra_geral = '".$input['regra_geral']."', ";
                if ($input['email_2'] == 'NULL') {
                    $standing .= "email_2 = ".$input['email_2'].", ";
                }

                if ($input['email_2'] != 'NULL') {
                    $standing .= "email_2 = '".$input['email_2']."', ";
                }

                if ($input['email_3'] == 'NULL') {
                    $standing .= "email_3 = ".$input['email_3']."";
                }

                if ($input['email_3'] != 'NULL') {
                    $standing .= "email_3 = '".$input['email_3']."'";
                }

                $standing .= " WHERE id = ".$input['id']."";
                $insert = DB::update($standing);
                return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
        }

        //insert
        $standing = "INSERT INTO regraenviolote(id_empresa, id_tributo, email_1, regra_geral, email_2, email_3) VALUES (".$input['select_empresas'].",".$input['select_tributos'].",'".$input['email_1']."','".$input['regra_geral']."',";

        if ($input['email_2'] == 'NULL') {
            $standing .= $input['email_2'].",";
        }

        if ($input['email_2'] != 'NULL') {
            $standing .= "'".$input['email_2']."',";
        }

        if ($input['email_3'] == 'NULL') {
            $standing .= $input['email_3'].")";
        }

        if ($input['email_3'] != 'NULL') {
            $standing .= "'".$input['email_3']."')";
        }
    
        $insert = DB::insert($standing);

        //return redirect()->route('regras.store')->with('status', 'Atividade adicionada com sucesso!');
        return redirect()->back()->with('status', 'Regra adicionada com sucesso!');
    }

    private function clearCNPJ($cnpj)
    {
        $cnpj = trim($cnpj);
        $cnpj = str_replace(".", "", $cnpj);
        $cnpj = str_replace(",", "", $cnpj);
        $cnpj = str_replace("-", "", $cnpj);
        $cnpj = str_replace("/", "", $cnpj);
        return $cnpj;
    }

    public function cnpj_save($dados)
    {       
        if (!empty($dados)) {

            if (!empty($dados['cnpj'])) {
                $dados['cnpj'] = $this->clearCNPJ($dados['cnpj']);
            }

            if (!$this->validaCamposCNPJ($dados)) {
                return false;
            }
        }

        $standing = "INSERT INTO regraenviolotefilial(id_regraenviolote, id_estabelecimento) VALUES (".$dados['id'].",".$this->estabelecimento_id.")";
        
        $insert = DB::insert($standing);
        $this->msg = 'CNPJ inserido com sucesso';
        return true;
    }

    public function edit_lote(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        $dados = DB::select("SELECT 
            id, id_empresa, id_tributo, email_1, email_2, email_3, regra_geral FROM regraenviolote WHERE id = ".$key."");

        $dadosfiliais = DB::select("SELECT 
            A.id, B.CNPJ, B.codigo FROM regraenviolotefilial A INNER JOIN estabelecimentos B on A.id_estabelecimento = B.id WHERE A.id_regraenviolote = ".$key."");

        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $dados = json_decode(json_encode($dados),true);
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);

        return view('regras.edit_lote')->with('dados', $dados)->with('dadosfiliais', $dadosfiliais)->withTributos($tributos)->withEmpresas($empresas);   
    }

    public function excluir(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            $insert = DB::delete('DELETE FROM regraenviolote WHERE id = '.$key.' ');
        }

        return redirect()->back()->with('status', 'Regra excluída com sucesso!');
    }

    public function excluirFilial(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            $insert = DB::delete('DELETE FROM regraenviolotefilial WHERE id = '.$key.' ');
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
    }
}

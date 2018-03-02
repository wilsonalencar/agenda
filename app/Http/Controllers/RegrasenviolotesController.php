<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regraenviolote;
use App\Models\Estabelecimento;
use App\Models\Regraenviolotefilial;
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

    private function getCNPJ($input){

        if (!$this->carregaCNPJ($input['cnpj'], $input['id_empresa'])) {
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

    private function carregaCNPJ($cnpj, $id_empresa) 
    {        
        $cnpj = $this->clearCNPJ($cnpj);
        $existe = DB::SELECT("SELECT id FROM estabelecimentos WHERE empresa_id = ".$id_empresa." AND cnpj = ".$cnpj."");
        $var = json_decode(json_encode($existe),true);
        if (empty($var)) {
            $this->msg = 'CNPJ não consta para essa empresa';
            return false;
        }
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
        
        //se estiver adicionando CNPJ
        if ($input['add_cnpj']) {
            $this->validate($request, [
            'cnpj' => 'required'
            ],
            $messages = [
                'cnpj.required' => 'Informar o CNPJ desejado.'

            ]);

            if (!$this->getCNPJ($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
                
            $value['id_regraenviolote'] = $input['id'];
            $value['id_estabelecimento'] = $this->estabelecimento_id;
            Regraenviolotefilial::create($value);
            return redirect()->back()->with('status', 'Filial adicionada com sucesso.');
        }

        //se não continua
        $this->validate($request, [
            'email_1' => 'required'
        ],
        $messages = [
            'email_1.required' => 'Informar o email obrigatório.'

        ]);
        
        $input['regra_geral'] = 'N';
        if ($input['label_regra']) {
            $input['regra_geral'] = 'S';
        }

        if (!empty($input)) {
            if (!$this->validaCampos($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
        }

        //edit
        if ($input['id'] > 0) {
            $Regraenviolote = Regraenviolote::findOrFail($input['id']);
            $Regraenviolote->fill($input)->save();
            return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
        }
        
        $value['id_empresa'] = $input['select_empresas'];
        $value['id_tributo'] = $input['select_tributos'];
        $value['email_1'] = $input['email_1'];
        $value['email_2'] = $input['email_2'];
        $value['email_3'] = $input['email_3'];
        $value['regra_geral'] = $input['regra_geral'];

        //se Não, ele cria
        Regraenviolote::create($value);
        return redirect()->route('regraslotes.edit_lote', Regraenviolote::create($value)->id)->with('status', 'Regra adicionada com sucesso!');
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

    public function edit_lote(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        $dados = Regraenviolote::findOrFail($key);
        $dadosfiliais = $dados->filiais;
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);

        foreach ($dadosfiliais as $key => $value) {
            $dadosfiliais[$key]['dadosFilial'] = Estabelecimento::select('cnpj', 'codigo')->where('id', $value['id_estabelecimento'])->get();
        }
        
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);    
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->lists('razao_social','id');
        $dados = json_decode(json_encode($dados),true);

        return view('regras.edit_lote')->with('dados', $dados)->with('dadosfiliais', $dadosfiliais)->withTributos($tributos)->withEmpresas($empresas);   
    }

    private function checkFiliais($id_regra)
    {
        $dados = DB::SELECT("SELECT id FROM regraenviolotefilial WHERE id_regraenviolote = ".$id_regra."");
        if (!empty($dados)) {
            return false;
        }        
        return true;
    }

    public function excluir(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!$this->checkFiliais($key)) {
            return redirect()->back()->with('alert', 'Para excluir esse registro, você terá que excluir os registros internos (Filiais cadastradas)!');
        }

        if (!empty($key)) {
            Regraenviolote::destroy($key);
        }

        return redirect()->back()->with('status', 'Regra excluída com sucesso!');
    }

    public function excluirFilial(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            Regraenviolotefilial::destroy($key);
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
    }
}

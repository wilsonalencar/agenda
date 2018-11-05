<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
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
        return view('arquivos.index')->with('filter_cnpj',Input::get("vcn"))->with('filter_codigo',Input::get("vco"))->with('filter_tributo',Input::get("vct"));
    }   

    public function Downloads(Request $request)
    {
        $input = $request->all();
        if (!empty($input)) {
            if (empty($input['ufs']) || empty($input['tributo_id']) || (empty($input['periodo_apuracao_inicio']) || empty($input['periodo_apuracao_fim']))) {
                return redirect()->back()->with('status', 'Os campos Tributo, UF e Período de apuração são obrigatórios para essa busca.');
            }

            $periodo = $this->calcPeriodo($input['periodo_apuracao_inicio'], $input['periodo_apuracao_fim']);
            $atividades = Atividade::select('atividades.*')
                        ->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
                        ->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
                        ->join('regras', 'atividades.regra_id', '=', 'regras.id')
                        ->whereIn('atividades.periodo_apuracao', $periodo)->whereIn('municipios.uf', $input['ufs'])
                        ->where('regras.tributo_id', $input['tributo_id'])->where('atividades.emp_id', $this->s_emp->id);
                
            if (!empty($input['estabelecimentos_selected'])) {
                $atividades = $atividades->whereIn('atividades.estemp_id', $input['estabelecimentos_selected']);
            }

            if (!empty($input['data_entrega_inicio'])) {
                $atividades = $atividades->whereRaw('DATE_FORMAT(atividades.data_entrega, "%Y-%m-%d") between "'.$input['data_entrega_inicio'].'" AND "'.$input['data_entrega_fim'].'"');
            }

            if (!empty($input['data_aprovacao_inicio'])) {
                $atividades = $atividades->whereRaw('DATE_FORMAT(atividades.data_aprovacao, "%Y-%m-%d") between "'.$input['data_aprovacao_inicio'].'" AND "'.$input['data_aprovacao_fim'].'"');
            }
            $files = array();
            $atividades = $atividades->get();

            if (count($atividades) > 0) {
                foreach ($atividades as $kk => $atividade) {
                    if ($atividade->arquivo_entrega != '-' && !empty($atividade->arquivo_entrega)) {
                        $files[] = $this->downloadById($atividade->id);
                    }
                }
                $this->zipDownload($files);
            } else {
                return redirect()->back()->with('status', 'Não foram encontradas atividades para essa busca.');
            }
        }

        $estabelecimentos = Estabelecimento::selectRaw("codigo, id")->orderby('codigo')->groupBy('codigo')->lists('codigo','id');
        $ufs = Municipio::selectRaw("uf, uf")->orderby('uf','asc')->lists('uf','uf');
        $tributos = Tributo::selectRaw("nome, id")->lists('nome','id');

        return view('arquivos.downloads')->with('estabelecimentos', $estabelecimentos)->with('ufs', $ufs)->with('tributos', $tributos);
    }

    private function zipDownload($files){
        $fileName = date('dmYHis').'.zip';
        $zip = new \ZipArchive();
        touch($fileName);

        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
            foreach ($files as $index => $file) {
                $singlefilename = explode('/', $file);
                foreach ($singlefilename as $xx => $v) {
                }
                if (file_exists($file)) {
                    $zip->addFile($file, $v);
                }
            }

            $zip->close();
            $this->ForceDown($fileName);
        }
    }

    private function calcPeriodo($inicio, $fim){

        $dataBusca['periodo_inicio'] = $inicio;
        $dataBusca['periodo_fim'] = $fim ;
        $dataExibe = array("periodo_inicio"=>$dataBusca['periodo_inicio'], "periodo_fim"=>$dataBusca['periodo_fim']);   
        
        $dataBusca['periodo_inicio'] = str_replace('/', '-', '01/'.$dataBusca['periodo_inicio']);
        $dataBusca['periodo_fim'] = str_replace('/', '-', '01/'.$dataBusca['periodo_fim']);
        list($dia, $mes, $ano) = explode( "-",$dataBusca['periodo_inicio']);
        $dataBusca['periodo_inicio'] = getdate(strtotime($dataBusca['periodo_inicio']));
        $dataBusca['periodo_fim'] = getdate(strtotime($dataBusca['periodo_fim']));
        $dif = ( ($dataBusca['periodo_fim'][0] - $dataBusca['periodo_inicio'][0]) / 86400 );
        $meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array

        for($x = 0; $x < $meses; $x++){
            $datas[] =  date("mY",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
        }

        return $datas;
    }

    public function downloadById($id) {

        $atividade = Atividade::findOrFail($id);
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

        @$destinationPath = substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/'.$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        $headers = array(
            'Content-Type' => 'application/pdf',
        );

        $file_path = public_path('uploads/'.$destinationPath);
        return $file_path;
    }

    private function ForceDown($filepath)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush();
        readfile($filepath);
        unlink($filepath);
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

            $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
            $type = 'emp';

            if (sizeof($estemp)==0) { 
                $estemp = Estabelecimento::select('id')->where('codigo','like','%'.$filter_codigo)->get();
                $type = 'estab';
            }

            if (sizeof($estemp)>0) {
                $atividades = $atividades->whereIn('estemp_id', $estemp)->where('estemp_type',$type);
            } else {
                $atividades = new Collection();
            }

        }

        if($filter_tributo = $request->get('tributo')){

            $tributosearch = Tributo::select('id')->where('nome', 'like', '%'.$filter_tributo.'%')->get();
            if (sizeof($tributosearch)>0) {

                $atividades = $atividades->whereHas('regra.tributo', function ($query) use ($tributosearch) {
                $query->whereIn('id', $tributosearch);
            });

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

    public function upload()
    {
        // getting all of the post data
        $file = array('image' => Input::file('image'));
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

        $dadosOriginais = json_decode(json_encode(DB::select('Select A.*, B.name as entregador, C.name as aprovador from atividades A left join users B on A.usuario_entregador = B.id left join users C on A.usuario_aprovador = C.id where A.retificacao_id = '.$atividade->id.';')),true);
       
        if (empty($dadosOriginais)) {
            $dadosOriginais = false;
        }

        return view('arquivos.show')->withAtividade($atividade)->withDownload($destinationPath)->with('dadosOriginais', $dadosOriginais);
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

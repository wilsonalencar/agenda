<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Models\Tributo;
use App\Models\Municipio;
use Carbon\Carbon;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/
//Everyone
Route::group(['middleware' => ['web']], function () {
    // Authentication Routes...
    Route::auth();

    /*STATIC PAGES*/
    Route::get('/', [
        'as' => 'home',
        'uses' => 'PagesController@home'
    ]);

    Route::get('/home', [
        'as' => 'home',
        'uses' => 'PagesController@home'
    ]);

    Route::get('/aprovacao', [
        'as' => 'aprovacao',
        'uses' => 'PagesController@aprovacao'
    ]);


    Route::get('/graficos', [
        'as' => 'graficos',
        'uses' => 'PagesController@graficos'
    ]);

    Route::get('/desempenho_entregas', [
        'as' => 'desempenho_entregas',
        'uses' => 'PagesController@desempenho_entregas'
    ]);

    Route::post('atualizarsenha', array('as'=>'atualizarsenha', 'uses'=>'UsuariosController@atualizarsenha'));

    Route::get('/grafico1', [
        'as' => 'grafico1',
        'uses' => 'PagesController@imgGrafico1'
    ]);

    Route::get('/grafico2', [
        'as' => 'grafico2',
        'uses' => 'PagesController@imgGrafico2'
    ]);
});

// Just the Owner, Admin, Manager, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|gcliente']], function () {

    Route::post('home', array('as'=>'home', 'uses'=>'PagesController@home'));
    Route::post('aprovacao', array('as'=>'aprovacao', 'uses'=>'PagesController@aprovacao'));
    Route::post('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));
    Route::get('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));

    Route::get('/download/{file}', 'DownloadsController@download');

    Route::resource('entregas', 'EntregasController');
    Route::get('entrega/data', array('as'=>'entregas.data', 'uses'=>'EntregasController@anyData'));

    Route::resource('arquivos', 'ArquivosController');
    Route::get('arquivo/data', array('as'=>'arquivos.data', 'uses'=>'ArquivosController@anyData'));

    Route::post('atividade/storeComentario', array('as'=>'atividades.storeComentario', 'uses'=>'AtividadesController@storeComentario'));

    Route::get('upload/{user}/entrega', array('as'=>'upload.entrega', 'uses'=>'UploadsController@entrega'));
    Route::post('upload/sendUpload', 'UploadsController@upload');

    Route::post('about', array('as'=>'about', 'uses'=>'PagesController@about'));
    Route::get('/about', [
        'as' => 'about',
        'uses' => 'PagesController@about'
    ]);

    Route::get('/dropdown-municipios', function(Request $request){

        $input = $request->input('option');
        $municipios = Municipio::where('uf',$input);

        return Response::make($municipios->get(['codigo','nome']));
    });
});

//Everyone registered
Route::group(['middleware' => ['web','auth','role:user|analyst|supervisor|manager|admin|owner']], function () {

    Route::get('/find-activities-detail', function(Request $request){

        Carbon::setTestNow();  //reset time
        $today = Carbon::today()->toDateString();

        $input_tributo = $request->input('option_tributo');  //Tributo
        $input_periodo = $request->input('option_periodo');  //Periodo Apuração
        $input_data = $request->input('option_data');        //Data limite entrega
        $input_serie_id = $request->input('option_serie_id');//Serie ID
        //$input_serie_nome = $request->input('option_serie_nome');

        $tributo = Tributo::where('nome',$input_tributo)->first();
        $tributo_id = $tributo->id;
        $data_limite = substr($input_periodo,-4,4).'-'.substr($input_data,-2,2).'-'.substr($input_data,0,2);

        $atividades = Atividade::select('*')
            ->with('estemp')
            ->whereHas('regra' , function ($query) use ($tributo_id) {
                $query->where('tributo_id', $tributo_id);
            })
            ->where('periodo_apuracao',str_replace('-','',$input_periodo))
            ->where('limite','like',"$data_limite%");

        switch ($input_serie_id) {
            case 0:
                $atividades->where('status',1)->where('limite','>',$today);
                break;
            case 1:
                $atividades->where('status',1)->where('limite','<=',$today);
                break;
            case 2:
                $atividades->where('status',2)->where('limite','>',$today);
                break;
            case 3:
                $atividades->where('status',2)->where('limite','<=',$today);
                break;
            case 4:
                $atividades->where('status',3)->whereRaw('data_aprovacao <= limite');
                break;
            case 5:
                $atividades->where('status',3)->whereRaw('data_aprovacao > limite');
                break;
            default:
                break;
        }
        return Response::make($atividades->get(['id','descricao','estemp.codigo']));
    });

    Route::get('/dropdown-regras', function(Request $request){

        $input = $request->input('option');
        $tributo = Tributo::find($input);
        $regras = $tributo->regras();

        return Response::make($regras->get(['id','nome_especifico','ref','regra_entrega']));
    });

    Route::get('/calendar', [
        'as' => 'calendar',
        'uses' => 'PagesController@calendar'
    ]);

    Route::resource('calendarios', 'CalendariosController');
    Route::get('/calendario', array('as'=>'calendario', 'uses'=>'CalendariosController@index'));
    Route::get('/feriados', array('as'=>'feriados', 'uses'=>'CalendariosController@showFeriados'));

});

// Just the Owner, Admin, Manager, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|manager|admin|owner|gbravo|gcliente']], function () {

    Route::post('home', array('as'=>'home', 'uses'=>'PagesController@home'));
    Route::post('graficos', array('as'=>'graficos', 'uses'=>'PagesController@graficos'));
    Route::post('desempenho_entregas', array('as'=>'desempenho_entregas', 'uses'=>'PagesController@desempenho_entregas'));
    Route::post('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));
    Route::get('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));

    Route::get('/download/{file}', 'DownloadsController@download');

    Route::resource('entregas', 'EntregasController');
    Route::get('entrega/data', array('as'=>'entregas.data', 'uses'=>'EntregasController@anyData'));

    Route::resource('arquivos', 'ArquivosController');
    Route::get('arquivo/data', array('as'=>'arquivos.data', 'uses'=>'ArquivosController@anyData'));

    Route::post('atividade/storeComentario', array('as'=>'atividades.storeComentario', 'uses'=>'AtividadesController@storeComentario'));

    Route::get('upload/{user}/entrega', array('as'=>'upload.entrega', 'uses'=>'UploadsController@entrega'));
    Route::post('upload/sendUpload', 'UploadsController@upload');

});

Route::group(['middleware' => ['web']], function () {
    Route::get('mensageriaprocadms/jobprocadms', array('as'=>'mensageriaprocadms.Job', 'uses'=>'MensageriaprocadmsController@Job'));
});

// Just the Owner, Admin, Manager, MSAF, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {
    Route::get('cargas', array('as'=>'cargas', 'uses'=>'CargasController@index'));
    Route::get('cargas_grafico', array('as'=>'cargas_grafico', 'uses'=>'CargasController@grafico'));
    Route::post('cargas', array('as'=>'cargas', 'uses'=>'CargasController@index'));
    Route::post('cargas/reset', array('as'=>'cargas.reset', 'uses'=>'CargasController@resetData'));
    Route::get('cargas/data', array('as'=>'cargas.data', 'uses'=>'CargasController@anyData'));
    Route::get('carga/{state}/{estab}/changeStateEntrada', array('as'=>'cargas.changeStateEntrada', 'uses'=>'CargasController@changeStateEntrada'));
    Route::get('carga/{state}/{estab}/changeStateSaida', array('as'=>'cargas.changeStateSaida', 'uses'=>'CargasController@changeStateSaida'));
});

// Just the Owner, Admin, Manager, MSAF, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner|gbravo']], function () {
    Route::get('integracao', array('as'=>'cargas', 'uses'=>'CargasController@index'));
    Route::get('integracao_grafico', array('as'=>'cargas_grafico', 'uses'=>'CargasController@grafico'));
    Route::post('integracao', array('as'=>'cargas', 'uses'=>'CargasController@index'));
    Route::post('integracao/reset', array('as'=>'cargas.reset', 'uses'=>'CargasController@resetData'));
    Route::get('integracao/data', array('as'=>'cargas.data', 'uses'=>'CargasController@anyData'));
    Route::get('integracao/{state}/{estab}/changeStateEntrada', array('as'=>'cargas.changeStateEntrada', 'uses'=>'CargasController@changeStateEntrada'));
    Route::get('carga/{state}/{estab}/changeStateSaida', array('as'=>'cargas.changeStateSaida', 'uses'=>'CargasController@changeStateSaida'));
});

// Just the Owner, Admin, Manager and the Supervisor
Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|gcliente']], function () {

    Route::resource('atividades', 'AtividadesController');
    Route::get('atividade/data', array('as'=>'atividades.data', 'uses'=>'AtividadesController@anyData'));
    Route::get('atividade/{atividade}/aprovar', array('as'=>'atividades.aprovar', 'uses'=>'AtividadesController@aprovar'));
    Route::get('atividade/{atividade}/reprovar', array('as'=>'atividades.reprovar', 'uses'=>'AtividadesController@reprovar'));
    Route::get('atividade/{atividade}/retificar', array('as'=>'atividades.retificar', 'uses'=>'AtividadesController@retificar'));
    Route::get('atividade/{atividade}/cancelar', array('as'=>'atividades.cancelar', 'uses'=>'AtividadesController@cancelar'));

    Route::post('dashboard_tributo', array('as'=>'dashboard_tributo', 'uses'=>'PagesController@dashboard_tributo'));
    Route::get('dashboard_tributo', array('as'=>'dashboard_tributo', 'uses'=>'PagesController@dashboard_tributo'));
    Route::post('dashboard', array('as'=>'dashboard', 'uses'=>'PagesController@dashboard'));
    Route::get('dashboard', array('as'=>'dashboard', 'uses'=>'PagesController@dashboard'));

    Route::get('consulta_procadm', array('as'=>'consulta_procadm', 'uses'=>'ProcessosAdmsController@consulta_procadm'));
    Route::get('consulta_procadm/rpt', array('as'=>'consulta_procadm/rpt', 'uses'=>'ProcessosAdmsController@rlt_processos'));
    Route::get('rlt_detalhado', array('as'=>'rlt_detalhado', 'uses'=>'ProcessosAdmsController@rlt_detalhado'));
    Route::get('processosadms/dataRLT', array('as'=>'processosadms.dataRLT', 'uses'=>'ProcessosAdmsController@anyDataRLT'));
    Route::get('processosadms/search_observacao', array('as'=>'processosadms.searchObservacao', 'uses'=>'ProcessosAdmsController@searchObservacao'));

    Route::post('processosadms/action_valid_import', array('as'=>'processosadms.action_valid_import', 'uses'=>'ProcessosAdmsController@action_valid_import'));
    Route::post('processosadms/action_import', array('as'=>'processosadms.action_import', 'uses'=>'ProcessosAdmsController@action_import'));
    Route::get('processosadms/delete/{processosadms}', array('as'=>'processosadms.delete', 'uses'=>'ProcessosAdmsController@delete'));
    Route::get('estabelecimento/search_area', array('as'=>'estabelecimentos.searchArea', 'uses'=>'EstabelecimentosController@searchArea'));
    Route::get('processosadms/data', array('as'=>'processosadms.data', 'uses'=>'ProcessosAdmsController@anyData'));
    Route::get('processosadms/import', array('as'=>'processosadms.import', 'uses'=>'ProcessosAdmsController@import'));
    Route::get('processosadms/search', array('as'=>'processosadms.search', 'uses'=>'ProcessosAdmsController@search'));
    Route::resource('processosadms', 'ProcessosAdmsController');

    Route::post('status_empresas', array('as'=>'status_empresas', 'uses'=>'PagesController@status_empresas'));
    Route::get('status_empresas', array('as'=>'status_empresas', 'uses'=>'PagesController@status_empresas'));

    Route::post('sendEmailExport', array('as'=>'sendEmailExport', 'uses'=>'UsuariosController@sendEmailExport'));

});


// Just Admin, Owner, Supervisor
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {
    
    Route::get('movtocontacorrentes/search', array('as'=>'movtocontacorrentes.search', 'uses'=>'MovtocontacorrentesController@search'));
    Route::get('movtocontacorrentes/confirm/{movtocontacorrente}', array('as'=>'movtocontacorrentes.confirm', 'uses'=>'MovtocontacorrentesController@confirm'));
    Route::get('movtocontacorrentes/import', array('as'=>'movtocontacorrentes.import', 'uses'=>'MovtocontacorrentesController@import'));
    Route::get('movtocontacorrente/data', array('as'=>'movtocontacorrentes.data', 'uses'=>'MovtocontacorrentesController@anyData'));
    Route::get('movtocontacorrentes/delete/{movtocontacorrente}', array('as'=>'movtocontacorrentes.delete', 'uses'=>'MovtocontacorrentesController@delete'));
    Route::post('movtocontacorrentes/action_import', array('as'=>'movtocontacorrentes.action_import', 'uses'=>'MovtocontacorrentesController@action_import'));
    Route::post('movtocontacorrentes/action_valid_import', array('as'=>'movtocontacorrentes.action_valid_import', 'uses'=>'MovtocontacorrentesController@action_valid_import'));
    Route::get('movtocontacorrente', array('as'=>'movtocontacorrente', 'uses'=>'MovtocontacorrentesController@index'));
    Route::resource('movtocontacorrentes', 'MovtocontacorrentesController');

});

// Just Admin, Owner, Supervisor
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {
    /*
    
    Route::get('movtocontacorrentes/confirm/{movtocontacorrente}', array('as'=>'movtocontacorrentes.confirm', 'uses'=>'MovtocontacorrentesController@confirm'));
    Route::get('movtocontacorrente/data', array('as'=>'movtocontacorrentes.data', 'uses'=>'MovtocontacorrentesController@anyData'));
    
    
    Route::get('movtocontacorrente', array('as'=>'movtocontacorrente', 'uses'=>'MovtocontacorrentesController@index'));
    */


});

// Just Admin, Owner, Supervisor
Route::group(['middleware' => ['web','auth','role:admin|owner|supervisor']], function () {

    Route::resource('empresas', 'EmpresasController');
    Route::get('empresa/data', array('as'=>'empresas.data', 'uses'=>'EmpresasController@anyData'));

    Route::resource('estabelecimentos', 'EstabelecimentosController');
    Route::get('estabelecimento/data', array('as'=>'estabelecimentos.data', 'uses'=>'EstabelecimentosController@anyData'));

    Route::resource('municipios', 'MunicipiosController');
    Route::get('municipio/data', array('as'=>'municipios.data', 'uses'=>'MunicipiosController@anyData'));

});

// Just Admin, Owner
Route::group(['middleware' => ['web','auth','role:admin|owner']], function () {

    Route::get('mensageriaprocadms/search_role', array('as'=>'mensageriaprocadms.searchRole', 'uses'=>'MensageriaprocadmsController@searchRole'));
    Route::resource('mensageriaprocadms', 'MensageriaprocadmsController');

    Route::resource('categorias', 'CategoriasController');
    Route::get('categoria/data', array('as'=>'categorias.data', 'uses'=>'CategoriasController@anyData'));

    Route::resource('tributos', 'TributosController');
    Route::get('tributo/data', array('as'=>'tributos.data', 'uses'=>'TributosController@anyData'));

    Route::resource('regras', 'RegrasController');
    Route::get('regra/data', array('as'=>'regras.data', 'uses'=>'RegrasController@anyData'));
    Route::get('regra/{regra}/{estabelecimento}/{enable}/setBlacklist', array('as'=>'regras.setBlacklist', 'uses'=>'RegrasController@setBlacklist'));

    Route::resource('regraslotes', 'RegrasenviolotesController');
    Route::get('regra/job_envio_email', array('as'=>'regraslotes.Job', 'uses'=>'RegrasenviolotesController@Job'));
    Route::get('regra/envio_lote', array('as'=>'regraslotes.envio_lote', 'uses'=>'RegrasenviolotesController@envio_lote'));
    Route::get('regra/edit_lote', array('as'=>'regraslotes.edit_lote', 'uses'=>'RegrasenviolotesController@edit_lote'));
    Route::get('regra/lote_consulta', array('as'=>'regraslotes.lote_consulta', 'uses'=>'RegrasenviolotesController@lote_consulta'));
    Route::get('regra/excluir', array('as'=>'regraslotes.excluir', 'uses'=>'RegrasenviolotesController@excluir'));
    Route::get('regra/excluirFilial', array('as'=>'regraslotes.excluirFilial', 'uses'=>'RegrasenviolotesController@excluirFilial'));

    Route::resource('usuarios', 'UsuariosController');
    Route::get('usuario/data', array('as'=>'usuarios.data', 'uses'=>'UsuariosController@anyData'));
    Route::get('usuario/{user}/sendEmailReminder', array('as'=>'usuarios.sendEmailReminder', 'uses'=>'UsuariosController@sendEmailReminder'));

    Route::get('empresa/{periodo}/{empresa}/geracao', array('as'=>'empresas.geracao', 'uses'=>'EmpresasController@geracao'));
    Route::get('estabelecimento/{tributo}/{estabelecimento}/{periodo_ini}/{periodo_fin}/geracao', array('as'=>'estabelecimentos.geracao', 'uses'=>'EstabelecimentosController@geracao'));

});

// Just the Owner
Route::group(['middleware' => ['web','auth','role:admin|owner']], function () {

    Route::get('usuario/{user}/elevateRole', array('as'=>'usuarios.elevateRole', 'uses'=>'UsuariosController@elevateRole'));
    Route::get('usuario/{user}/decreaseRole', array('as'=>'usuarios.decreaseRole', 'uses'=>'UsuariosController@decreaseRole'));

});



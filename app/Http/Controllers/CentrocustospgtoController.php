<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\Guiaicms;
use App\Models\CriticasLeitor;
use App\Models\CriticasEntrega;
use App\Models\Atividade;
use App\Models\User;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;



class CentrocustospgtoController extends Controller
{
    public function create(Request $request)
    {
        
    }

    public function createsap(Request $request)
    {

    }
}

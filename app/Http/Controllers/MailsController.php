<?php

namespace App\Http\Controllers;

use DB;
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
use Carbon\Carbon;



class MailsController extends Controller
{
    protected $eService;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
     }

    public function Guiaimcs()
    {
        $registers = CriticasLeitor::where('Enviado', 0)->get();
        if (!empty($registers)) {
            $this->Processar($registers, 'leitorpdf');
        }
    }

    public function UploadFiles()
    {
        $registers = CriticasEntrega::where('Enviado', 0)->get();
        if (!empty($registers)) {
            $this->Processar($registers, 'upload');
        }
    }

    private function Processar($registros, $processo)
    {
        if ($processo == 'upload') {
            $this->uploadMails($registros);
        }
        $this->leitorMails($registros);
    }

    private function uploadMails($registros)
    {
        foreach ($registros as $key => $register) {
            $query = "select id FROM users where id IN (select id_usuario_analista FROM atividadeanalista where Tributo_id = ".$register->Tributo_id." and Emp_id = ".$register->Empresa_id.")";

            //teste
            $query = 'select id from users where id = 68';
            
            $emailsAnalista = DB::select($query);

            $codigoEstabelecimento = '';
            if ($register->Estemp_id > 0) {
                $codigoEstabelecimentoArray = DB::select('select codigo FROM estabelecimentos where id = '.$register->Estemp_id.' LIMIT 1 ');
                
                if (!empty($codigoEstabelecimentoArray[0])) {
                    $codigoEstabelecimento = $codigoEstabelecimentoArray[0]->codigo;
                }
            }

            $tributo_nome = '';
            if ($register->Tributo_id > 0) {
                $nomeTributoArray = DB::select('select nome FROM tributos where id = '.$register->Tributo_id.' LIMIT 1 ');
                
                if (!empty($nomeTributoArray[0])) {
                    $tributo_nome = $nomeTributoArray[0]->nome;
                }
            }
            
            $subject = "Críticas e Alertas Entrega de arquivos em ".$register->Data_critica;
            $text = 'Empresa => '.$register->Empresa_id.' Estabelecimento.Codigo => '.$codigoEstabelecimento.' Tributo => '.$tributo_nome.' Arquivo => '.$register->arquivo.' Critica => '.$register->critica.' importado => '.$register->importado;

            $data = array('subject'=>$subject,'messageLines'=>$text);
            

            if (!empty($emailsAnalista)) {
                
                foreach($emailsAnalista as $row) {
                    $user = User::findOrFail($row->id);
                    $this->eService->sendMail($user, $data, 'emails.notification-leitor-criticas', false);
                }

                $register->Enviado = 1;
                $register->save();
            }
        }
    }
 
    private function leitorMails($registros)
    {
        foreach ($registros as $x => $register) {
            $query = "select id FROM users where id IN (select id_usuario_analista FROM atividadeanalista where Tributo_id = ".$register->Tributo_id." and Emp_id = ".$register->Empresa_id.")";
            $emailsAnalista = DB::select($query);

            $codigoEstabelecimento = '';
            if ($register->Estemp_id > 0) {
                $codigoEstabelecimentoArray = DB::select('select codigo FROM estabelecimentos where id = '.$register->Estemp_id.' LIMIT 1 ');
                
                if (!empty($codigoEstabelecimentoArray[0])) {
                    $codigoEstabelecimento = $codigoEstabelecimentoArray[0]->codigo;
                }
            }

            $tributo_nome = '';
            if ($register->Tributo_id > 0) {
                $nomeTributoArray = DB::select('select nome FROM tributos where id = '.$register->Tributo_id.' LIMIT 1 ');
                
                if (!empty($nomeTributoArray[0])) {
                    $tributo_nome = $nomeTributoArray[0]->nome;
                }
            }
            
            $subject = "Críticas e Alertas Leitor PDF em ".$register->Data_critica;
            $text = 'Empresa => '.$register->Empresa_id.' Estabelecimento.Codigo => '.$codigoEstabelecimento.' Tributo => '.$tributo_nome.' Arquivo => '.$register->arquivo.' Critica => '.$register->critica.' importado => '.$register->importado;

            $data = array('subject'=>$subject,'messageLines'=>$text);
            
            if (!empty($emailsAnalista)) {
                foreach($emailsAnalista as $row) {
                    $user = User::findOrFail($row->id);
                    $this->eService->sendMail($user, $data, 'emails.notification-leitor-criticas', false);
                }
            }     
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Atividade;
use App\Models\Regra;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Services\EntregaService;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


use App\Http\Requests;


class SpedFiscalController extends Controller
{
	protected $eService;
    public $s_emp = null;

    public function __construct(EntregaService $service)
    {
        if (!session()->get('seid')) {
            echo "Nenhuma empresa Selecionada.<br/><br/><a href='home'>VOLTAR</a>";
            exit;
        }

        $this->eService = $service;

        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid')); 
        }
    }

    public function index()
    {
        $user = User::findOrFail(Auth::user()->id);

    	$query = 'SELECT 
                        A.id,
                        A.descricao,
                        E.codigo,
                        E.cnpj,
                        A.periodo_apuracao
                    FROM
                        atividades A
					INNER JOIN
                        regras B on A.regra_id = B.id
                    INNER JOIN 
                    	tributos C on B.tributo_id = C.id
                    INNER JOIN 
                    	estabelecimentos E on A.estemp_id = E.id
                    WHERE  C.id = 1 AND A.status = 1 AND A.emp_id = '.$this->s_emp->id;

        $table = DB::select($query);
   
        if (!empty($table)) {
        	foreach ($table as $key => $activities) {
        		$table[$key]->color = $this->getFileContent($activities->id);
        	}
        }

        return view('spedfiscal.index')->with('table', $table);
    }


    private function getFileContent($id, $returnfile = false)
    {
    	$array = array();
		$path = '';
    	
    	//carregamento
    	$atividade = Atividade::findOrFail($id);
		$emp['cnpj'] = substr($atividade->empresa->cnpj, 0,8);
		$a = explode(' ', $atividade->empresa->razao_social);
		$emp['name'] = $a[0];
		$codigo_estab = $atividade->estemp->codigo;
	
		//formação do path
        $server = explode('/', $_SERVER['SCRIPT_FILENAME']);
        if ($server[0] == 'C:' || $server[0] == 'F:') {
            $path = 'W:';
        }

		//a busca dos arquivos e a formação do array, precisa ser de acordo com os dados acima
        $path .= '/storagebravobpo/'.$emp['name'].'_'.$emp['cnpj'].'/pvaspedfiscal/retornopva/'.$codigo_estab;

        if (!is_dir($path)) {
        	return 'Yellow';
        }

		//carrega files dentro de uma váriavel ($array)
        $array = explode("/", $path);

        $contend_path = scandir($path);

        $arquivos = array();
        foreach ($contend_path as $key => $value) {
        	if (strlen($value) > 2) {
        		$arquivos[] = $path.'/'.$value;
        	}
        }

        if ($returnfile) {
        	if (empty($arquivos)) {
        		return 'Yellow';
        	}
        	return $arquivos;
        }

        if (!empty($arquivos)) {
        	$returnError = false;
        	$returnSuccess = false;
        	foreach ($arquivos as $key => $arquivo) {
        		$namefile = $this->getLastName($arquivo);
        		$exploded_namefile = explode('.', $namefile);
        		foreach ($exploded_namefile as $index => $cuttedfile) {
        			if ($cuttedfile == 'txt' && count($arquivos) == 1 && count($exploded_namefile) == 2) {
        				return 'Yellow';
        			}
        			if ($cuttedfile == 'TXT-ERRO') {
        				$returnError = true;
        			}
        			if ($cuttedfile == 'TXT-SUCESSO') {
        				$returnSuccess = true;
        			}
        			if ($cuttedfile == 'rec' || $cuttedfile == 'REC') {
        				return 'Green';
        			}
        		}
        	}
        	if ($returnSuccess) {
        		return 'Blue';
        	}
        	if ($returnError) {
        		return 'Red';
        	}
        }
        return 'Yellow';
    }

    private function getLastName($file)
    {
		$exploded_arquivo = explode('/', $file);
		foreach ($exploded_arquivo as $k => $namefile) {
		}
    	return $namefile;
    }

    public function DownloadPath($id)
    { 	
    	$files = $this->getFileContent($id, true);
    	if ($files == 'Yellow') {
    		return redirect()->back()->with('alert', 'Não existem arquivos na pasta ou a pasta não existe!');
    	}

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
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Atividade;
use App\Models\Regra;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\Estabelecimento;
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
        $this->eService = $service;
        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid')); 
        }
    }

    public function job()
    {
        $files = array();
        $Emps = Empresa::All();
        $raizes = $this->getPathNames($Emps);
        if (!empty($raizes)) {
            foreach ($raizes as $x => $raiz) {
                $scandir = scandir($raiz);
                foreach ($scandir as $x => $k) {
                    if (strlen($k) > 2) {
                        $files[] = $raiz.'/'.$k;
                    }
                }
            }
        }
        
        if (!empty($files)) {
            foreach ($files as $x => $file) {
                $explode = explode('/', $file);
                foreach ($explode as $l => $filename) {}
                $explode = explode('.', $filename);

                foreach ($explode as $randon => $exploded) {
                    if ($exploded == 'TXT-ERRO') {
                        $this->createCritica($file);
                    }
                }
            }
        }

        echo "Job rodado com sucesso";exit;
    }

    public function createCritica($arquivo)
    {
        $exploded = explode('/', $arquivo);
        $empresaraiz = explode('_', $exploded[2]);
        $empresacnpjini = $empresaraiz[1];
        
        $empresaraizid = 0;
        $empresaRaizBusca = DB::select('select id, razao_social, cnpj from empresas where LEFT(cnpj, 8)= "'.$empresacnpjini.'"');
        if (!empty($empresaRaizBusca[0]->id)) {
            $empresaraizid = $empresaRaizBusca[0]->id;
        }

        $empresa_razao = $empresaRaizBusca[0]->razao_social;
        $empresa_cnpj = $empresaRaizBusca[0]->cnpj;
        $filial = $exploded[5];
        $filename = $exploded[6];
        $estemp = Estabelecimento::where('codigo', $filial)->where('empresa_id', $empresaraizid)->first();

        //buscar email através de empresa e tributo
        $user_id = $this->loadResponsavel($empresaraizid, $estemp->id);
        //enviando email
        $now = date('d/m/Y');
        $subject = "CRÍTICAS SPED FISCAL ICMS-IPI FILIAL : ".$filial." - ".$empresa_razao;
        $text = "Segue arquivo de críticas da empresa ".$empresa_cnpj.", código da filial ".$filial.", para análise e correção.";

        $data = array('subject'=>$subject,'messageLines'=>$text);

        if (!empty($user_id)) {
            $user = User::findOrFail($user_id);
            $this->eService->sendMail($user, $data, 'emails.notification-leitor-criticas', false);
        }
    }    

    private function loadResponsavel($emp_id, $estemp_id)
    {
        $query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = 1 and B.Emp_id = " .$emp_id. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$estemp_id. " AND B.Regra_geral = 'N') limit 1";

        $retornodaquery = DB::select($query);

        $sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = 1 and B.Emp_id = " .$emp_id. " AND B.Regra_geral = 'S') limit 1";
        
        $queryGeral = DB::select($sql);
        
        $idanalistas = $retornodaquery;
        if (empty($retornodaquery)) {
            $idanalistas = $queryGeral;   
        }

        if (!empty($idanalistas)) {
            foreach ($idanalistas as $k => $analista) {
                return $analista->id;
            }
        }
    }

    private function getPathNames($empresas)
    {
        $raizes = array();
        $path = '';
        $server = explode('/', $_SERVER['SCRIPT_FILENAME']);
        if ($server[0] == 'C:' || $server[0] == 'F:') {
            $path = 'W:';
        }

        $path .= '/storagebravobpo/';
        
        if (!empty($empresas)) {
            foreach ($empresas as $key => $empresa) {
                $a = explode(' ', $empresa->razao_social);
                foreach ($empresa->estabelecimentos as $x => $estabelecimento) {
                    $raizes[] = $path.$a[0].'_'.substr($empresa->cnpj, 0,8).'/pvaspedfiscal/retornopva/'.$estabelecimento->codigo;
                }
            }
        }

        if (!empty($raizes)) {
            foreach ($raizes as $anyValue => $raiz) {
                if (!is_dir($raiz)) {
                    unset($raizes[$anyValue]);
                } else {
                    $scandir = scandir($raiz);
                    $files = array();
                    foreach ($scandir as $chave => $file) {
                        if (strlen($file) > 2) {
                            $files[] = $file;
                        }
                    }
                    if (empty($files)) {
                        unset($raizes[$anyValue]);
                    }
                }
            }
        }

        return $raizes;
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
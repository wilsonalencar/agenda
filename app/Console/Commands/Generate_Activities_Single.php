<?php

namespace App\Console\Commands;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Municipio;
use App\Models\Regra;
use App\Services\EntregaService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class Generate_Activities_Single extends Command
{
    protected $eService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:single {cnpj} {codigo} {tributo_id} {periodo_ini} {periodo_fin?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create automatic activities for one single cnpj';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EntregaService $service)
    {
        $this->eService = $service;
        $periodo_ini = $this->argument('periodo_ini');
        $cnpj = $this->argument('cnpj');
        $codigo = $this->argument('codigo');
        $tributo_id = $this->argument('tributo_id');

        if ($periodo_fin=$this->argument('periodo_fin')) {

            $period_array = $this->_createPeriodArray($periodo_ini,$periodo_fin);
            if (sizeof($period_array)==0) {
                $this->error('Não foi possível gerar o array de período!');
            } else {
                for ($i = 0; $i<sizeof($period_array); $i++) {

                    $periodo = $period_array[$i];
                    $is_valid = preg_match('/^[0-9]*$/', $periodo);

                    if (strlen($periodo) == 6 && $is_valid && is_numeric($tributo_id)) {

                        $retval = $this->eService->generateSingleCnpjActivities($periodo, $cnpj, $codigo, $tributo_id);
                        if (!$retval){
                            $this->info('WARNING: Estab. inativo ou existem atividades geradas para o periodo '.$periodo);
                        } else {
                            $this->info('Geracao concluida para o');
                            $this->info('Periodo '.$periodo);        
                            $this->info('E Tributo '.$tributo_id);
                        }

                    } else {
                        $this->error('Período não é valido ou tributo não está sendo encontrado!');
                    }
                }
            }

        } else {

            $periodo = $periodo_ini;
            //need to improve validation, now is just numeric
            $is_valid = preg_match('/^[0-9]*$/', $periodo);

            if (strlen($periodo) == 6 && $is_valid && is_numeric($tributo_id)) {

                $retval = $this->eService->generateSingleCnpjActivities($periodo, $cnpj, $codigo, $tributo_id);
                if (!$retval){
                    $this->info('WARNING: Estab. inativo ou existem atividades geradas para o periodo '.$periodo);
                } else {
                    $this->info('Geracao concluida para o');
                    $this->info('Periodo '.$periodo);
                    $this->info('E Tributo '.$tributo_id);
                }
            } else {
                $this->error('Período não é valido ou tributo não está sendo encontrado!');
            }
        }
    }

    private function _createPeriodArray ($p_ini, $p_fin) {
        $period = array();
        $p_ini_m = substr($p_ini,0,2);
        $p_ini_y = substr($p_ini,-4,4);
        $p_fin_m = substr($p_fin,0,2);
        $p_fin_y = substr($p_fin,-4,4);
        $diff_y = $p_fin_y-$p_ini_y;

        if ($p_ini_m < 1 || $p_ini_m > 12 || $p_fin_m < 1 || $p_fin_m > 12 || $diff_y > 5 || $diff_y < 0) {
            return $period;
        }

        if ($p_ini_y == $p_fin_y && $p_ini_m<$p_fin_m) {
            for ($i = intval($p_ini_m); $i<= intval($p_fin_m); $i++) {
                if ($i<=9) {
                    $i = '0'.$i;
                }
                $period[] = $i.$p_ini_y;
            }
        } else if ($p_ini_y<$p_fin_y) {
            for ($j=$p_ini_y; $j<$p_fin_y; $j++) {
                if ($j==$p_ini_y) {
                    for ($i = intval($p_ini_m); $i <= 12; $i++) {
                        if ($i <= 9) {
                            $i = '0' . $i;
                        }
                        $period[] = $i . $j;
                    }
                } else {
                    for ($i = 1; $i <= 12; $i++) {
                        if ($i <= 9) {
                            $i = '0' . $i;
                        }
                        $period[] = $i . $j;
                    }
                }
            }
            for ($i = 1; $i <= intval($p_fin_m); $i++) {
                if ($i <= 9) {
                    $i = '0' . $i;
                }
                $period[] = $i . $p_fin_y;
            }
        }

        return $period;
    }

}

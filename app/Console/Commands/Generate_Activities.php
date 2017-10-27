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

class Generate_Activities extends Command
{
    protected $eService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:all {periodo} {empresa?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create automatic activities';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EntregaService $service)
    {
        $this->eService = $service;
        $periodo = $this->argument('periodo');

        //need to improve validation, now is just numeric
        $is_valid = preg_match('/^[0-9]*$/', $periodo);

        if (strlen($periodo)==4 && $is_valid) {
            $this->info('Pedido de geracao em andamento...');
            if ($empresa=$this->argument('empresa')) {
                $retval = $this->eService->generateYearlyActivities($periodo,$empresa);
            } else {
                $this->error('Parameter error!');
            }
            if (!$retval) $this->info('WARNING: Existem atividades geradas para o periodo '.$periodo);
            else $this->info('Periodo '.$periodo);
            $this->info('Geracao concluida');

        } else if (strlen($periodo)==6 && $is_valid) {
            $this->info('Pedido de geracao em andamento...');
            if ($empresa=$this->argument('empresa')) {
                $retval = $this->eService->generateMonthlyActivities($periodo,$empresa);
            } else {
                $this->error('Parameter error!');
            }

            if (!$retval) $this->info('WARNING: Existem atividades geradas para o periodo '.$periodo);
            else $this->info('Periodo '.$periodo);
            $this->info('Geracao concluida');

        } else {
            $this->error('Parameter error!');
        }

    }

}

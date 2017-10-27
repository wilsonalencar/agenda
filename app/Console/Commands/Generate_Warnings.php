<?php

namespace App\Console\Commands;

use App\Models\Atividade;
use App\Services\EntregaService;
use Illuminate\Console\Command;
use App\Models\User;

class Generate_Warnings extends Command
{
    protected $eService;

   /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send {user=-1}s';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic e-mails to the analysts';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EntregaService $service)
    {
        $this->eService = $service;
        $userId = $this->argument('user');

        if ($userId>0) {

            $usuario = User::findOrFail($userId);
            if ($usuario->hasRole('admin') || $usuario->hasRole('owner')) {

                $retval  = $this->eService->generateAdminNotifications($usuario);

            } else if ($usuario->hasRole('supervisor')) {

                $retval  = $this->eService->generateAdminNotifications($usuario);

            } else if ($usuario->hasRole('analyst')) {

                $retval  = $this->eService->generateNotifications($usuario);

            } else {
                $this->info('User '.$usuario->name.' is not admin or analyst');
                return;
            }

            $this->info('Single notification to '.$usuario->name.' has been sent. Warnings: '.$retval);

        } else {

            $with_roles = function ($query) {
                $query->where('role_id','=',4);
            };
            $usuarios = User::select('*')->whereHas('roles', $with_roles)->get();

            $bar = $this->output->createProgressBar(count($usuarios));

            foreach ($usuarios as $usuario) {

                $this->eService->generateNotifications($usuario);
                //$this->info('E-mail notification to '.$usuario->name.' has been sent.');
                $bar->advance();
            }

            $bar->finish();
        }


    }


}

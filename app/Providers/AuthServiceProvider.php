<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        if (strpos(php_uname(), 'Windows') !== false) {
            shell_exec('php Background/UploadMails.php');
        } else {
            exec('php Background/UploadMails.php');
        }

        if (strpos(php_uname(), 'Windows') !== false) {
            shell_exec('php Background/LeitorMails.php');
        } else {
            exec('php Background/LeitorMails.php');
        }

        $this->registerPolicies($gate);

        //
    }
}

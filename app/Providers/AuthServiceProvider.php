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
        // $cmd = 'C:\wamp\bin\php\php7.0.10\php.exe C:\wamp\www\agenda\public\Background\UploadMails.php';
        // if (substr(php_uname(), 0, 7) == "Windows"){ 
        //     pclose(popen("start /B " . $cmd, "r"));  
        // } else { 
        //         exec($cmd . " > /dev/null &");   
        // } 
        
        // $cmd = 'C:\wamp\bin\php\php7.0.10\php.exe C:\wamp\www\agenda\public\Background\LeitorMails.php';
        // if (substr(php_uname(), 0, 7) == "Windows"){ 
        //     pclose(popen("start /B " . $cmd, "r"));  
        // } else { 
        //         exec($cmd . " > /dev/null &");   
        // } 

        $this->registerPolicies($gate);

        //
    }
}

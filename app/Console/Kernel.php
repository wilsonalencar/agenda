<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

//php artisan schedule:run

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

        Commands\Generate_Activities::class,
        Commands\Generate_Activities_Single::class,
        Commands\Generate_Warnings::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('email:send')->weekdays()->at('11:00');        //Horario UTC
        $schedule->command('email:send 22')->weekdays()->at('11:00');
        $schedule->command('email:send 4')->weekdays()->at('11:00');
        $schedule->command('email:send 7')->weekdays()->at('11:00');
        $schedule->command('email:send 5')->weekdays()->at('11:00');
    }
}

<?php

/* call C:\xampp\htdocs\agenda>phpunit from console */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
	//print_r 'AQUI';
    protected $baseUrl = 'http://homo-innagenda';
    protected $eService;	

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function testGenerateMonthlyActivities() {
		// Activate auto activity generation
        $active = true;

        if (!$active) return true;

        $this->eService = new App\Services\EntregaService;
        $this->eService->generateMonthlyActivities('032016','13574594000196');

        file_put_contents('test_output.txt', print_r($retval, true));
    }

    public function testGenerateYearlyActivities() {
        $active = true;

        if (!$active) return true;

        $this->eService = new App\Services\EntregaService;
        $this->eService->generateYearlyActivities('2015','13574594000196');

        file_put_contents('test_output.txt', print_r($retval, true));
    }

}

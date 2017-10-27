<?php

use Illuminate\Database\Seeder;

class FeriadosEstaduaisTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
            array('AC', '23-01;15-06;05-09;17-11'),
            array('AL', '24-06;29-06;16-09;20-11'),
            array('AM', '05-09;20-11;08-12'),
            array('AP', '19-03;25-07;05-10;20-11'),
            array('BA', '02-07'),
            array('CE', '19-03;25-03'),
            array('DF', '21-04;30-11'),
            array('ES', '28-10'),
            array('GO', '28-10'),
            array('MA', '28-07;08-12'),
            array('MG', '21-04'),
            array('MS', '11-10'),
            array('MT', '20-11'),
            array('PA', '15-08'),
            array('PB', '05-08'),
            array('PE', '24-06'),
            array('PI', '13-03;19-10'),
            array('RJ', '23-04;28-10;20-11;08-12'),
            array('RN', '29-06;03-10'),
            array('RO', '04-01;18-06'),
            array('RR', '05-10'),
            array('RS', '20-09'),
            array('SC', '11-08'),
            array('SE', '08-07'),
            array('SP', '09-07'),
            array('TO', '08-09;05-10')
        );

        foreach ($data as $el) {
            DB::table('feriados_estaduais')->insert([
                'uf' => $el[0],
                'datas' => $el[1],
                'created_at' => '2016-02-01 00:00:00',
            ]);
        }
    }
}

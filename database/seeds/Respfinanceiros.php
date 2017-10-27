<?php

use Illuminate\Database\Seeder;

class Respfinanceiros extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $data = array(
                        array('FORNCEDOR'),
                        array('CLIENTE')
        );

        foreach ($data as $el) {
            DB::table('respfinanceiros')->insert([
                'descricao' => $el[0],
                'created_at' => '2016-02-01 00:00:00'
            ]);
        }
    }
}

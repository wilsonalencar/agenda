<?php

use Illuminate\Database\Seeder;

class Statusprocadms extends Seeder
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
            array('BAIXADA'),
            array('EM ANDAMENTO')
        );

        foreach ($data as $el) {
            DB::table('statusprocadms')->insert([
                'descricao' => $el[0],
                'created_at' => '2016-02-01 00:00:00'
            ]);
        }
    }
}

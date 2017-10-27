<?php

use Illuminate\Database\Seeder;

class EmpresasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('empresas')->insert([
            'codigo' => '0000',
            'cnpj' => '13574594000196',
            'razao_social' => 'BK BRASIL OPERACAO E ASSESSORIA A RESTAURANTES S.A.',
            'endereco' => 'AL.RIO NEGRO',
            'num_endereco' => '14 ANDAR CONJ.140 161',
            'cod_municipio' => '3505708',
            'insc_estadual' => '206143418117',
            'insc_municipal' => '-',
            'created_at' => '2016-02-01 00:00:00'
        ]);
    }
}

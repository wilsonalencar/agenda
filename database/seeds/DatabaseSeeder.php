<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(MunicipiosTableSeeder::class);
        $this->call(EmpresasTableSeeder::class);
        $this->call(EstabelecimentosTableSeeder::class);
        $this->call(CategoriasTableSeeder::class);
        $this->call(TributosTableSeeder::class);
        $this->call(RegrasTableSeeder::class);
        $this->call(FeriadosEstaduaisTableSeeder::class);
    }
}

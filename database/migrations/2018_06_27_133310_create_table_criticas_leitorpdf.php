<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCriticasLeitorpdf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('criticasleitor', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('Empresa_id')->nullable()->unsigned();
            $table->foreign('Empresa_id')->references('id')->on('empresas');

            $table->integer('Estemp_id')->nullable()->unsigned();
            $table->foreign('Estemp_id')->references('id')->on('estabelecimentos');

            $table->integer('Tributo_id');
            $table->string('arquivo',100);
            $table->dateTime('Data_critica');
            $table->string('critica',200);
            $table->string('importado', 1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('guiaicms');
    }
}

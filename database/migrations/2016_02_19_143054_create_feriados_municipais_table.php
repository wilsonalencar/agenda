<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeriadosMunicipaisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feriados_municipais', function (Blueprint $table) {
            $table->increments('id');
            $table->string('data');
            $table->string('flag_repeticao');
            $table->string('municipio_id');
            $table->timestamps();
            $table->foreign('municipio_id')->references('codigo')->on('municipios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('feriados_municipais');
    }
}

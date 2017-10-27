<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmpresasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo')->unique();
            $table->string('cnpj')->unique();
            $table->string('razao_social');
            $table->string('endereco');
            $table->string('num_endereco');
            $table->string('cod_municipio');
            $table->string('insc_estadual');
            $table->string('insc_municipal');
            $table->timestamps();
            $table->foreign('cod_municipio')->references('codigo')->on('municipios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estabelecimentos');
        Schema::drop('empresas');
    }
}

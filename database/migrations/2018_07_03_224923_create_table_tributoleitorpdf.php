<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTributoleitorpdf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('criticasentrega', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id')->unsigned();
            $table->integer('Empresa_id')->unsigned();
            $table->integer('Estemp_id')->unsigned();
            $table->string('arquivo', 50);
            $table->timestamps('Data_critica');
            $table->string('critica', 250);
            $table->string('importado', 1);
            $table->foreign('Tributo_id')->references('id')->on('tributos')->onDelete('cascade');
            $table->foreign('Empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('Estemp_id')->references('id')->on('estabelecimentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('criticasentrega');
    }
}

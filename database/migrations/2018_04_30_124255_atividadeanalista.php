<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Atividadeanalista extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atividadeanalista', function (Blueprint $table) {
            $table->increments('id', 10);
            $table->integer('Emp_id')->unsigned();;
            $table->foreign('Emp_id')->references('id')->on('empresas');
            
            $table->integer('Tributo_id')->unsigned();;
            $table->foreign('Tributo_id')->references('id')->on('tributos');
            
            $table->integer('Id_usuario_analista')->unsigned();;
            $table->foreign('Id_usuario_analista')->references('id')->on('users');
            
            $table->char('Regra_geral', 1);
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
        Schema::drop('atividadeanalista');
    }
}

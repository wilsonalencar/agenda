<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Atividadeanalistafilial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atividadeanalistafilial', function (Blueprint $table) {
            $table->increments('id', 10);
            $table->integer('Id_estabelecimento')->unsigned();;
            $table->foreign('Id_estabelecimento')->references('id')->on('estabelecimentos');
            
            $table->integer('Id_atividadeanalista')->unsigned();;
            $table->foreign('Id_atividadeanalista')->references('id')->on('atividadeanalista');
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
        Schema::drop('atividadeanalistafilial');
    }
}

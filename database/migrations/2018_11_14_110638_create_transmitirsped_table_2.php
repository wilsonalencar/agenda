<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransmitirspedTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmitirsped', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_atividade');
            $table->string('nome_arquivo', 30);
            $table->dateTime('data_copia');
            $table->integer('usuario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transmitirsped');
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableObservacaoprocadms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('observacaoprocadms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('processoadm_id')->unsigned();
            $table->text('descricao');
            $table->string('usuario_update', 255);
            $table->timestamps();
            $table->foreign('processoadm_id')->references('id')->on('processosadms')->onDelete('cascade');
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
        Schema::drop('observacaoprocadms');
    }
}

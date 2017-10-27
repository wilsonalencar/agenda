<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProcessosadms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('processosadms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('estabelecimento_id')->unsigned();
            $table->string('periodo_apuracao', 255);
            $table->string('nro_processo', 255);
            $table->string('usuario_last_update', 255);
            $table->integer('resp_financeiro_id')->unsigned();
            $table->integer('status_id')->unsigned();
            $table->string('resp_acompanhamento', 255);
            $table->timestamps();
            $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos')->onDelete('cascade');
            $table->foreign('resp_financeiro_id')->references('id')->on('respfinanceiros')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statusprocadms')->onDelete('cascade');
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
        Schema::drop('processosadms');
    }
}

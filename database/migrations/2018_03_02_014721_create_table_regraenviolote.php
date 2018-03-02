<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRegraenviolote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('regraenviolote', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_empresa')->unsigned();
            $table->integer('id_tributo')->unsigned();
            $table->foreign('id_empresa')->references('id')->on('empresas');
            $table->foreign('id_tributo')->references('id')->on('tributos');
            $table->string('email_1', 255);
            $table->string('email_2', 255)->nullable()->default(NULL);
            $table->string('email_3', 255)->nullable()->default(NULL);
            $table->char('regra_geral',1);  //A-Anual, M-Mensal
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::drop('regraenviolote');
    }
}

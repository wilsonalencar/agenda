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
            $table->integer('id_empresa')->references('id')->on('empresas')->onDelete('cascade');
            $table->integer('id_tributo')->references('id')->on('tributos')->onDelete('cascade');
            $table->string('email_1', 255);
            $table->string('email_2', 255);
            $table->string('email_3', 255);
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

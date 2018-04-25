<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Grupoempresas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('grupoempresas', function (Blueprint $table) {
            $table->increments('id', 10);
            $table->string('Nome_grupo', 30);
            $table->integer('id_empresa')->unsigned();;
            $table->char('Logo_grupo', 1);
            $table->foreign('id_empresa')->references('id')->on('empresas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('grupoempresas');
    }
}

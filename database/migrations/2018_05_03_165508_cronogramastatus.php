<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Cronogramastatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cronogramastatus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('periodo_apuracao');
            $table->char('tipo_periodo',1);  //A-Anual, M-Mensal
            $table->integer('qtd')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cronogramastatus');
    }
}

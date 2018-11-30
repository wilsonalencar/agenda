<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TablePrevisaocarga extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('previsaocarga', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id');
            $table->string('periodo_apuracao',6);
            $table->date('Data_prev_carga');
        });//
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('previsaocarga');
    }
}

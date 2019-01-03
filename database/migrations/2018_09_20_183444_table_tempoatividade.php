<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableTempoatividade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tempoatividade', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id');
            $table->string('UF', 2);
            $table->integer('Qtd_minutos');
        });//
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tempoatividade');
    }
}

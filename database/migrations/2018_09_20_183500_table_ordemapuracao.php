<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableOrdemapuracao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ordemapuracao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id');
            $table->integer('Prioridade');
        });//
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ordemapuracao');
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableCronogramamensal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cronogramamensal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Empresa_id');
            $table->integer('Tributo_id');
            $table->date('DATA_SLA');
            $table->string('periodo_apuracao',6);
            $table->string('uf', 2);
            $table->integer('Qtde_estab');
            $table->integer('Tempo_estab');
            $table->integer('Tempo_total');
            $table->integer('Qtd_dias');
            $table->integer('Tempo_geracao');
            $table->string('Qtd_analistas', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cronogramamensal');
    }
}

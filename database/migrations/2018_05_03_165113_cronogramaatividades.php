<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Cronogramaatividades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cronogramaatividades', function (Blueprint $table) {
            $table->increments('id');
            $table->text('descricao');
            $table->boolean('recibo')->default(0);
            $table->integer('status');
            $table->integer('regra_id')->unsigned();
            $table->integer('estemp_id')->unsigned();
            $table->string('estemp_type');
            $table->string('periodo_apuracao');
            $table->dateTime('inicio_aviso');
            $table->dateTime('limite');
            $table->char('tipo_geracao',1)->default('A'); //Manual|Automatica
            $table->string('arquivo_entrega')->default('-');
            $table->integer('usuario_entregador')->unsigned();
            $table->dateTime('data_entrega');
            $table->integer('usuario_aprovador')->unsigned();
            $table->dateTime('data_aprovacao');
            $table->integer('retificacao_id', 11);
            $table->timestamps();
            $table->foreign('regra_id')->references('id')->on('regras')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cronogramaatividades');
    }
}

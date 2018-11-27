<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentacaoClienteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('documentacaocliente', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('emp_id')->unsigned();
            $table->string('descricao', 250);
            $table->datetime('data_criacao');
            $table->integer('id_user_autor')->unsigned();
            $table->datetime('data_atualizacao')->nullable();
            $table->integer('id_user_atualiza')->nullable();
            $table->integer('versao');
            $table->text('observacao')->nullable();
            $table->string('arquivo', 250);
            $table->foreign('emp_id')->references('id')->on('empresas');
            $table->foreign('id_user_autor')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('documentacaocliente');
    }
}

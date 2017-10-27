<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTributosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tributos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome');
            $table->text('descricao');
            $table->integer('categoria_id')->unsigned();
            $table->char('tipo',1); //Federal|Estadual|Municipal
            $table->boolean('recibo');
            $table->integer('alerta')->unsigned();
            $table->timestamps();
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');
        });

        // Create table for associating tributos to users (Many-to-Many)
        Schema::create('tributo_user', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('tributo_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tributo_id')->references('id')->on('tributos')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['user_id', 'tributo_id']);
        });

        // Create table for associating tributos to users (Many-to-Many)
        Schema::create('empresa_tributo', function (Blueprint $table) {
            $table->integer('empresa_id')->unsigned();
            $table->integer('tributo_id')->unsigned();
            $table->integer('adiantamento_entrega')->unsigned()->default(0);

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tributo_id')->references('id')->on('tributos')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['empresa_id', 'tributo_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tributo_user');
        Schema::drop('empresa_tributo');
        Schema::drop('tributos');
    }
}

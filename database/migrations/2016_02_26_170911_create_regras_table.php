<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegrasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regras', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome_especifico');  //Caso exista um nome especifico
            $table->integer('tributo_id')->unsigned();
            $table->string('ref');
            $table->string('regra_entrega');
            $table->char('freq_entrega',1)->default('M'); //Mensal,Anual
            $table->string('legislacao');
            $table->text('obs');
            $table->integer('ativo')->default(1);
            $table->timestamps();
            $table->foreign('tributo_id')->references('id')->on('tributos')->onDelete('cascade');
        });

        // Create table for associating tributos to users (Many-to-Many)
        Schema::create('estabelecimento_regra', function (Blueprint $table) {
            $table->integer('estabelecimento_id')->unsigned();
            $table->integer('regra_id')->unsigned();

            $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('regra_id')->references('id')->on('regras')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['estabelecimento_id', 'regra_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('estabelecimento_regra');
        Schema::drop('regras');
    }
}

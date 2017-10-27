<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAtividadesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atividades', function (Blueprint $table) {
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
            $table->integer('retificacao_id')->unsigned();
            $table->timestamps();
            $table->foreign('regra_id')->references('id')->on('regras')->onDelete('cascade');
            $table->foreign('retificacao_id')->references('id')->on('atividades');

        });

        // Create table for associating users to atividades (Many-to-Many)
        Schema::create('atividade_user', function (Blueprint $table) {
            $table->integer('atividade_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('atividade_id')->references('id')->on('atividades')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['atividade_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('atividade_user');
        Schema::drop('atividades');
    }
}

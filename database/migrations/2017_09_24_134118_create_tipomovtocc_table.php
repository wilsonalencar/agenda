<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTipomovtoccTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movtocontacorrentes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('estabelecimento_id')->unsigned();
            $table->string('periodo_apuracao', 255);
            $table->string('usuario_update', 255);
            $table->decimal('vlr_guia', 10, 2);
            $table->decimal('vlr_gia', 10, 2);
            $table->decimal('vlr_sped', 10, 2);
            $table->decimal('vlr_dipam', 10, 2);
            $table->char('dipam', 1)->default('N'); // SIM ; NAO
            $table->timestamps();
            $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('movtocontacorrentes');
    }
}

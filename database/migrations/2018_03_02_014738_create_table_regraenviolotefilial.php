<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRegraenviolotefilial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regraenviolotefilial', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_regraenviolote')->unsigned();
            $table->integer('id_estabelecimento')->unsigned();
            $table->foreign('id_regraenviolote')->references('id')->on('regraenviolote');
            $table->foreign('id_estabelecimento')->references('id')->on('estabelecimentos');            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::drop('regraenviolotefilial');        
    }
}

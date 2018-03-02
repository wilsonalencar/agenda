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
            $table->integer('id_regraenviolote')->references('id')->on('regraenviolote')->onDelete('cascade');
            $table->integer('id_estabelecimento')->references('id')->on('estabelecimentos')->onDelete('cascade');            
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

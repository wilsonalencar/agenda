<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTributoleitor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tributoleitorpdf', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id')->unsigned();
            $table->string('leitorpdf', 1);
            
            $table->foreign('Tributo_id')->references('id')->on('tributos')->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tributoleitorpdf');
    }
}

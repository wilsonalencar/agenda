<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCentrocustospagto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('centrocustospagto', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Empresa_id');
            $table->integer('Estemp_id');
            $table->string('centrocusto',20);  
            $table->string('descricao',50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('centrocustospagto');
    }
}

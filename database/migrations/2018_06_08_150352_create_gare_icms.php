<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGareIcms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guiaicms', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('TRIBUTO_ID');
            $table->string('CNPJ',20);
            $table->string('IE',20);
            $table->string('COD_RECEITA',10);
            $table->string('REFERENCIA',7);
            $table->dateTime('DATA_VENCTO');
            $table->string('INSCR_DIVIDA',20);
            $table->string('N_AIM_ADI_PARC',20);
            $table->decimal('VLR_RECEITA', 10, 2);
            $table->decimal('JUROS_MORA', 10, 2);
            $table->decimal('MULTA_MORA_INFRA', 10, 2);
            $table->decimal('ACRESC_FINANC', 10, 2);
            $table->decimal('HONORARIOS_ADV', 10, 2);
            $table->decimal('VLR_TOTAL', 10, 2);
            $table->string('CONTRIBUINTE',100);
            $table->string('ENDERECO',100);
            $table->string('MUNICIPIO',30);
            $table->string('UF',2);
            $table->string('TELEFONE',20);
            $table->string('CNAE',20);
            $table->string('OBSERVACAO',100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('guiaicms');
    }
}

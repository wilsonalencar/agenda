<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablesToMelhorias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('previsaocarga', 'uf')) {
            Schema::table('previsaocarga', function (Blueprint $table) {
                $table->string('uf', 2);
                $table->integer('empresa_id');
            });
        }

        if (! Schema::hasColumn('tempoatividade', 'uf')) {
            Schema::table('tempoatividade', function (Blueprint $table) {
                $table->string('uf', 2);
                $table->integer('empresa_id');
            });
        }

        Schema::create('Tempoexcecao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Tributo_id');
            $table->integer('Empresa_id');
            $table->integer('Estab_id');
            $table->integer('Qtd_minutos');
            $table->integer('Id_usuarioanalista');
        });

        Schema::create('dataextra', function (Blueprint $table) {
            $table->increments('id');
            $table->date('data');
            $table->string('periodo_apuracao', 6);
        });

        Schema::create('dataextraanalista', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Dataextra_id');
            $table->integer('Id_usuarioanalista');
            $table->integer('Tempo_extra');
        });

        Schema::create('analistadisponibilidade', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Id_usuarioanalista');
            $table->integer('Empresa_id');
            $table->integer('Qtd_min_disp_dia');
            $table->date('Data_ini_disp');
            $table->date('Data_fim_disp');
            $table->string('Período_apuracao', 6);
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
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAddColumnTempo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('cronogramaatividades', 'tempo')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('tempo')->nullable()->default(NULL);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('cronogramaatividades', 'data_atividade')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('tempo');
            });
        }
    }
}

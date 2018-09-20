<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CronogramaDataAtividades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('cronogramaatividades', 'data_atividade')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dateTime('data_atividade')->nullable()->default(NULL);
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
                $table->dropColumn('data_atividade');
            });
        }
    }
}

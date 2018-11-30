<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCronogramaatividadesCronogramamensal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('cronogramaatividades', 'cronograma_mensal')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('cronograma_mensal')->nullable()->default(NULL);
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
        if (Schema::hasColumn('cronogramaatividades', 'cronograma_mensal')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dropColumn('cronograma_mensal');
            });
        }
    }
}

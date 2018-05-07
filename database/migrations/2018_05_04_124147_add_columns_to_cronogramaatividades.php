<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToCronogramaatividades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('cronogramaatividades', 'Id_usuario_analista')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('Id_usuario_analista')->unsigned()->nullable();
                $table->foreign('Id_usuario_analista')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasColumn('cronogramaatividades', 'Resp_cronograma')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('Resp_cronograma')->unsigned()->nullable();
                $table->foreign('Resp_cronograma')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasColumn('cronogramaatividades', 'Data_cronograma')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dateTime('Data_cronograma');
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
        if (Schema::hasColumn('cronogramaatividades', 'Id_usuario_analista')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dropColumn('Id_usuario_analista');
            });
        }
        if (Schema::hasColumn('cronogramaatividades', 'Resp_cronograma')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dropColumn('Resp_cronograma');
            });
        }
        if (Schema::hasColumn('cronogramaatividades', 'Data_cronograma')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dropColumn('Data_cronograma');
            });
        }
    }
}

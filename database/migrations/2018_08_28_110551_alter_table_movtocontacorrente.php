<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovtocontacorrente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('movtocontacorrentes', 'Data_inicio')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->integer('Responsavel')->default(null);
                $table->dateTime('Data_inicio')->default(null);
                $table->dateTime('DataPrazo')->default(null);
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
        if (Schema::hasColumn('movtocontacorrentes', 'Data_inicio')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->dropColumn('Data_inicio');
                $table->dropColumn('Responsavel');
                $table->dropColumn('DataPrazo');
            });
        }
    }
}

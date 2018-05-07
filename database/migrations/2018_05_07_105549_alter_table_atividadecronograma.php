<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAtividadecronograma extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       if (! Schema::hasColumn('cronogramaatividades', 'emp_id')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->integer('emp_id')->after('estemp_type')->unsigned()->nullable();
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
        if (Schema::hasColumn('cronogramaatividades', 'emp_id')) {
            Schema::table('cronogramaatividades', function (Blueprint $table) {
                $table->dropColumn('emp_id');
            });
        }
    }
}
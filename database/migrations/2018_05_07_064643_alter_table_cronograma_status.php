<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCronogramaStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('cronogramastatus', 'emp_id')) {
            Schema::table('cronogramastatus', function (Blueprint $table) {
                $table->integer('emp_id')->after('qtd')->unsigned()->nullable();
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
        if (Schema::hasColumn('cronogramastatus', 'emp_id')) {
            Schema::table('cronogramastatus', function (Blueprint $table) {
                $table->dropColumn('emp_id');
            });
        }
    }
}

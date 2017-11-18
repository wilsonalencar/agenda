<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMovtocontacorrente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (! Schema::hasColumn('movtocontacorrentes', 'observacao')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->string('observacao', 255)->nullable()->after('dipam');
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
        //
        if (Schema::hasColumn('movtocontacorrentes', 'observacao')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->dropColumn('observacao');
            });
        }
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampoStatusContaCorrente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        //
        if (! Schema::hasColumn('movtocontacorrentes', 'status_id')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->integer('status_id')->unsigned()->nullable()->default(null)->after('dipam');
                $table->foreign('status_id')->references('id')->on('statusprocadms')->onDelete('cascade');
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
        if (Schema::hasColumn('movtocontacorrentes', 'status_id')) {
            Schema::table('movtocontacorrentes', function (Blueprint $table) {
                $table->dropColumn('status_id');
            });
        }
    }
}

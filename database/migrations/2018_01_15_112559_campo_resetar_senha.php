<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampoResetarSenha extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (! Schema::hasColumn('users', 'reset_senha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('reset_senha')->unsigned()->nullable()->default(0)->after('password');
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
        if (Schema::hasColumn('users', 'reset_senha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('reset_senha');
            });
        }
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserindoUsuarioData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('guiaicms', 'USUARIO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->integer('USUARIO')->default(null);
                $table->dateTime('DATA')->default(null);
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
        if (Schema::hasColumn('guiaicms', 'USUARIO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->dropColumn('USUARIO');
                $table->dropColumn('DATA');
            });
        }
    }
}

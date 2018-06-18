<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SapMunicipios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('municipios', 'codigo_sap')) {
            Schema::table('municipios', function (Blueprint $table) {
                $table->integer('codigo_sap')->nullable()->default(null)->after('uf');
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
        if (Schema::hasColumn('municipios', 'codigo_sap')) {
            Schema::table('municipios', function (Blueprint $table) {
                $table->dropColumn('codigo_sap');
            });
        }
    }
}

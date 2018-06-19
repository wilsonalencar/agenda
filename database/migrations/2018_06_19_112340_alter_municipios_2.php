<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMunicipios2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->string('codigo_sap',10)->nullable()->default(null)->after('uf');
        });
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

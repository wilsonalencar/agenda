<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGuiaicmsCodIdent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('guiaicms', 'COD_IDENTIFICACAO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->string('COD_IDENTIFICACAO', 50)->nullable()->default(NULL);
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
        if (Schema::hasColumn('guiaicms', 'COD_IDENTIFICACAO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->dropColumn('COD_IDENTIFICACAO');
            });
        }
    }
}

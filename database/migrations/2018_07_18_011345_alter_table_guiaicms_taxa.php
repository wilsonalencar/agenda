<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGuiaicmsTaxa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('guiaicms', 'TAXA')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->decimal('TAXA', 10, 2)->nullable()->default(NULL)->after('CODBARRAS');
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
        if (Schema::hasColumn('guiaicms', 'TAXA')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->dropColumn('TAXA');
            });
        }
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColumnImposto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('guiaicms', 'IMPOSTO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->string('IMPOSTO', 10)->nullable()->default(NULL);
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
        if (Schema::hasColumn('guiaicms', 'IMPOSTO')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->dropColumn('IMPOSTO');
            });
        }
    }
}

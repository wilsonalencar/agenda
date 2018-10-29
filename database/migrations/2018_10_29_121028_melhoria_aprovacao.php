<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MelhoriaAprovacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('regraenviolote', 'envioaprovacao')) {
            Schema::table('regraenviolote', function (Blueprint $table) {
                $table->string('envioaprovacao', 1)->nullable()->default('S');
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
        if (Schema::hasColumn('regraenviolote', 'envioaprovacao')) {
            Schema::table('regraenviolote', function (Blueprint $table) {
                $table->dropColumn('envioaprovacao');
            });
        }
    }
}

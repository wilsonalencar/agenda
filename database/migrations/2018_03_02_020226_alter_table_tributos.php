<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTributos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('tributos', 'pasta_arquivos')) {
            Schema::table('tributos', function (Blueprint $table) {
                $table->string('pasta_arquivos', 255)->nullable()->after('alerta');
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
        if (Schema::hasColumn('tributos', 'pasta_arquivos')) {
            Schema::table('tributos', function (Blueprint $table) {
                $table->dropColumn('pasta_arquivos');
            });
        }
    }
}

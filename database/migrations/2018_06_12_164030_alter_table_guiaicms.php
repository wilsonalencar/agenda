<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGuiaicms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         if (! Schema::hasColumn('guiaicms', 'MULTA_PENAL_FORMAL')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->decimal('MULTA_PENAL_FORMAL',10,2);
                $table->string('CODBARRAS',100);
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
        if (Schema::hasColumn('guiaicms', 'MULTA_PENAL_FORMAL')) {
            Schema::table('guiaicms', function (Blueprint $table) {
                $table->dropColumn('MULTA_PENAL_FORMAL');
                $table->dropColumn('CODBARRAS');
            });
        }
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstabelecimentos3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
    {
        if (! Schema::hasColumn('estabelecimentos', 'Dt_alteracao_saida')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->timestamp('Dt_alteracao_saida')->after('Dt_alteracao_entrada');
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
        if (Schema::hasColumn('estabelecimentos', 'Dt_alteracao_saida')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->timestamp('Dt_alteracao_saida');
            });
        }
    }
}

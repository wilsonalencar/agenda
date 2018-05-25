<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstabelecimentos2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('estabelecimentos', 'Dt_alteracao_entrada')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->timestamp('Dt_alteracao_entrada')->after('Id_usuario_entrada');
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
        if (Schema::hasColumn('estabelecimentos', 'Dt_alteracao_entrada')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->timestamp('Dt_alteracao_entrada');
            });
        }
    }
}

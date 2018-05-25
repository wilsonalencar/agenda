<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstabelecimentos1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('estabelecimentos', 'Id_usuario_saida')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->integer('Id_usuario_saida')->unsigned()->nullable()->default(null)->after('empresa_id');
                $table->foreign('Id_usuario_saida')->references('id')->on('users')->onDelete('cascade');
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
        if (Schema::hasColumn('estabelecimentos', 'Id_usuario_saida')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->dropColumn('Id_usuario_saida');
            });
        }
    }
}

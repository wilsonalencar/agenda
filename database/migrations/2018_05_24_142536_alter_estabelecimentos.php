<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstabelecimentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('estabelecimentos', 'Id_usuario_entrada')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->integer('Id_usuario_entrada')->unsigned()->nullable()->default(null)->after('empresa_id');
                $table->foreign('Id_usuario_entrada')->references('id')->on('users')->onDelete('cascade');
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
        if (Schema::hasColumn('estabelecimentos', 'Id_usuario_entrada')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->dropColumn('Id_usuario_entrada');
            });
        }
    }
}

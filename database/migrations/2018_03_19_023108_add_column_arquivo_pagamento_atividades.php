<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnArquivoPagamentoAtividades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up()
    {
        //
        if (! Schema::hasColumn('atividades', 'arquivo_comprovante')) {
            Schema::table('atividades', function (Blueprint $table) {
                $table->string('arquivo_comprovante')->default('-');
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
        //
        if (Schema::hasColumn('atividades', 'arquivo_comprovante')) {
            Schema::table('atividades', function (Blueprint $table) {
                $table->dropColumn('arquivo_comprovante');
            });
        }
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColumnsAtividades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('atividades', 'vlr_recibo_1')) {
            Schema::table('atividades', function (Blueprint $table) {
                $table->decimal('vlr_recibo_1', 15,2)->default(NULL);
                $table->decimal('vlr_recibo_2', 15,2)->default(NULL);
                $table->decimal('vlr_recibo_3', 15,2)->default(NULL);
                $table->decimal('vlr_recibo_4', 15,2)->default(NULL);
                $table->decimal('vlr_recibo_5', 15,2)->default(NULL);
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
    }
}

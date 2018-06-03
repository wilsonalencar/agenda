
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoricoMovto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historicocontacorrente', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Id_contacorrente')->unsigned();
            $table->text('Alteracao_realizada');
            $table->integer('Id_usuario_alteracao')->unsigned();
            $table->timestamps('Data_alteracao');
            $table->foreign('Id_usuario_alteracao')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('historicocontacorrente');
    }
}

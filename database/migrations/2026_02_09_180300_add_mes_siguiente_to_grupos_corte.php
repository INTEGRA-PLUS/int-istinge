<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMesSiguienteToGruposCorte extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grupos_corte', function (Blueprint $table) {
            $table->tinyInteger('mes_siguiente')->default(0)->after('fecha_suspension')
                  ->comment('1 = La suspensión se aplica en el mes siguiente al de facturación');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grupos_corte', function (Blueprint $table) {
            $table->dropColumn('mes_siguiente');
        });
    }
}

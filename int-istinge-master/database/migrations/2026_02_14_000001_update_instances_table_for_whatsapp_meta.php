<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInstancesTableForWhatsappMeta extends Migration
{
    public function up()
    {
        Schema::table('instances', function (Blueprint $table) {
            // Solo agregar si no existen
            if (!Schema::hasColumn('instances', 'phone_number_id')) {
                $table->string('phone_number_id')->nullable()->after('uuid_whatsapp');
            }
            if (!Schema::hasColumn('instances', 'waba_id')) {
                $table->string('waba_id')->nullable()->after('phone_number_id');
            }
            if (!Schema::hasColumn('instances', 'meta')) {
                $table->json('meta')->nullable()->after('type');
            }
            if (!Schema::hasColumn('instances', 'activo')) {
                $table->boolean('activo')->default(true)->after('meta');
            }
        });
    }

    public function down()
    {
        Schema::table('instances', function (Blueprint $table) {
            // Check before dropping to avoid errors if they didn't exist
            $columns = [];
            if (Schema::hasColumn('instances', 'phone_number_id')) $columns[] = 'phone_number_id';
            if (Schema::hasColumn('instances', 'waba_id')) $columns[] = 'waba_id';
            if (Schema::hasColumn('instances', 'meta')) $columns[] = 'meta';
            if (Schema::hasColumn('instances', 'activo')) $columns[] = 'activo';
            
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}

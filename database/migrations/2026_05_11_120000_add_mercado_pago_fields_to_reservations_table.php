<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('mercado_pago_preference_id')->nullable()->unique()->after('source');
            $table->string('mercado_pago_payment_id')->nullable()->after('mercado_pago_preference_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'mercado_pago_preference_id',
                'mercado_pago_payment_id',
            ]);
        });
    }
};

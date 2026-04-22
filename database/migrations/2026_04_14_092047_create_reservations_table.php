<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('reservation_code')->unique();
            $table->string('guest_name');
            $table->string('guest_email')->index();
            $table->string('guest_phone')->nullable();
            $table->date('check_in')->index();
            $table->date('check_out')->index();
            $table->unsignedTinyInteger('number_of_guests');
            $table->string('status')->index();
            $table->string('payment_status')->index();
            $table->string('currency', 3)->default(config('booking.currency'));
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('fees_total')->default(0);
            $table->unsignedInteger('total');
            $table->json('pricing_breakdown');
            $table->text('notes')->nullable();
            $table->string('source')->default(config('booking.default_reservation_source'))->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

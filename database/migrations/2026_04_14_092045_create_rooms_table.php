<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle');
            $table->text('description');
            $table->string('bed_type');
            $table->unsignedTinyInteger('capacity');
            $table->string('size')->nullable();
            $table->unsignedInteger('base_nightly_rate');
            $table->string('currency', 3)->default(config('booking.currency'));
            $table->json('amenities');
            $table->json('kitchen_amenities')->nullable();
            $table->json('bathroom_amenities')->nullable();
            $table->json('other_amenities')->nullable();
            $table->json('policies');
            $table->json('highlights');
            $table->string('main_image_url')->nullable();
            $table->json('gallery_images')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

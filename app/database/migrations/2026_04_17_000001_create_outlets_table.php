<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // nama gerai: "Warung Kopi Terminal 1"
            $table->string('location')->nullable();          // lokasi: "Terminal 1, Lantai 2"
            $table->enum('type', ['food_court', 'restaurant', 'cafe', 'kiosk'])->default('food_court');
            $table->string('phone', 20)->nullable();
            $table->time('open_time')->default('06:00');
            $table->time('close_time')->default('22:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};

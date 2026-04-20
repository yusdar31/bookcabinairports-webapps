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
            $table->string('room_number', 10)->unique();     // nomor kamar: "C-101"
            $table->enum('type', ['standard', 'vip'])->default('standard');
            $table->string('floor', 5)->nullable();           // lantai: "1"
            $table->decimal('price_per_hour', 10, 2);         // harga per jam (IDR)
            $table->decimal('price_per_night', 10, 2);        // harga per malam (IDR)
            $table->enum('status', ['available', 'occupied', 'maintenance', 'cleaning'])->default('available');
            $table->text('amenities')->nullable();             // JSON list fasilitas: ["wifi", "ac", "locker"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

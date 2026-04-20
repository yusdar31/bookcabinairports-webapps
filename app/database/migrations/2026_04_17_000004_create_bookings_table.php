<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();     // kode booking: "BK-20260417-001"
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // resepsionis yang memproses
            $table->string('guest_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 20)->nullable();
            $table->string('guest_id_number', 30)->nullable(); // nomor KTP/Paspor
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->enum('status', [
                'pending',        // menunggu pembayaran
                'confirmed',      // pembayaran dikonfirmasi
                'checked_in',     // tamu sudah masuk
                'checked_out',    // tamu sudah keluar
                'cancelled',      // dibatalkan
                'no_show',        // tidak datang
            ])->default('pending');
            $table->enum('payment_method', ['midtrans', 'cash', 'transfer', 'ota'])->default('midtrans');
            $table->string('payment_reference')->nullable();   // ID transaksi Midtrans / OTA
            $table->string('pin_code', 6)->nullable();         // PIN kamar untuk check-in mandiri
            $table->string('qr_token')->nullable();            // token QR untuk check-in
            $table->string('source', 20)->default('direct');   // sumber: "direct", "ota", "website"
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['check_in', 'check_out']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

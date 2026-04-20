<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 20)->unique(); // kode transaksi: "TX-20260417-001"
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // kasir yang memproses
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);        // PPN 11%
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('payment_method', ['cash', 'qris', 'debit', 'credit', 'transfer'])->default('cash');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'completed', 'voided', 'refunded'])->default('pending');
            $table->string('offline_id')->nullable()->unique(); // UUID dari IndexedDB untuk deduplikasi
            $table->boolean('synced_from_offline')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->restrictOnDelete();
            $table->string('menu_name');                       // snapshot nama menu saat transaksi
            $table->decimal('unit_price', 10, 2);              // snapshot harga saat transaksi
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);
            $table->text('notes')->nullable();                 // catatan: "pedas level 3"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};

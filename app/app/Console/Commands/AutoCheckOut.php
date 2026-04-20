<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckOut extends Command
{
    protected $signature = 'bookings:auto-checkout
                            {--dry-run : Tampilkan booking yang akan di-checkout tanpa mengeksekusi}
                            {--grace=30 : Menit toleransi setelah check_out time sebelum auto-checkout}';

    protected $description = 'Otomatis check-out tamu yang melewati waktu check-out';

    public function handle(): int
    {
        $gracePeriod = (int) $this->option('grace');
        $isDryRun    = $this->option('dry-run');
        $cutoff      = now()->subMinutes($gracePeriod);

        $bookings = Booking::where('status', 'checked_in')
            ->where('check_out', '<=', $cutoff)
            ->with('room')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('✅ Tidak ada booking yang perlu di-auto-checkout.');
            return self::SUCCESS;
        }

        $this->info("🔍 Ditemukan {$bookings->count()} booking untuk auto-checkout:");
        $this->newLine();

        $headers = ['Booking Code', 'Kamar', 'Tamu', 'Check-out', 'Terlambat'];
        $rows = [];

        foreach ($bookings as $booking) {
            $lateMinutes = $booking->check_out->diffInMinutes(now());

            $rows[] = [
                $booking->booking_code,
                $booking->room->room_number ?? '-',
                $booking->guest_name,
                $booking->check_out->format('d/m/Y H:i'),
                $lateMinutes . ' menit',
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠️  Mode dry-run: tidak ada perubahan yang dilakukan.');
            return self::SUCCESS;
        }

        $checkedOut = 0;

        foreach ($bookings as $booking) {
            try {
                $booking->update([
                    'status'           => 'checked_out',
                    'actual_check_out' => now(),
                ]);

                // Bebaskan kamar → status 'cleaning'
                if ($booking->room) {
                    $booking->room->update(['status' => 'cleaning']);
                }

                Log::info("Auto check-out: {$booking->booking_code} (kamar: {$booking->room->room_number})");
                $checkedOut++;
            } catch (\Throwable $e) {
                $this->error("❌ Gagal checkout {$booking->booking_code}: {$e->getMessage()}");
                Log::error("Auto check-out failed: {$booking->booking_code}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("✅ Berhasil auto-checkout {$checkedOut}/{$bookings->count()} booking.");

        return self::SUCCESS;
    }
}

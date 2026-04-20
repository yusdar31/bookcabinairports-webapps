<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetCleaningRooms extends Command
{
    protected $signature = 'rooms:reset-cleaning
                            {--minutes=60 : Menit maksimal status cleaning sebelum direset ke available}';

    protected $description = 'Reset status kamar dari cleaning ke available setelah periode tertentu';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff  = now()->subMinutes($minutes);

        $rooms = Room::where('status', 'cleaning')
            ->where('updated_at', '<=', $cutoff)
            ->get();

        if ($rooms->isEmpty()) {
            $this->info('✅ Tidak ada kamar cleaning yang perlu direset.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($rooms as $room) {
            $room->update(['status' => 'available']);
            Log::info("Room reset: {$room->room_number} → available");
            $count++;
        }

        $this->info("✅ {$count} kamar direset dari cleaning → available.");

        return self::SUCCESS;
    }
}

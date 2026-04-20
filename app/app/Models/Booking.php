<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'room_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_id_number',
        'check_in',
        'check_out',
        'actual_check_in',
        'actual_check_out',
        'total_price',
        'status',
        'payment_method',
        'payment_reference',
        'pin_code',
        'qr_token',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'actual_check_in' => 'datetime',
            'actual_check_out' => 'datetime',
            'total_price' => 'decimal:2',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Status helpers ---

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function canCheckIn(): bool
    {
        return $this->status === 'confirmed' && now()->gte($this->check_in->subHours(1));
    }
}

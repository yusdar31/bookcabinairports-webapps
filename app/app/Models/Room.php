<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'type',
        'floor',
        'price_per_hour',
        'price_per_night',
        'status',
        'amenities',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'price_per_night' => 'decimal:2',
            'amenities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }
}

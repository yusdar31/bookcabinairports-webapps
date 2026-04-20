<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'type',
        'phone',
        'open_time',
        'close_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
        ];
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

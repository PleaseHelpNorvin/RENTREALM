<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'room_code',
        'room_picture_url',
        'rent_price',
        'capacity',
        'current_occupants',
        'min_lease',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    protected static function booted()
    {
        static::saving(function ($room) {
            if ($room->current_occupants > $room->capacity) {
                // You can throw a validation exception or return a custom JSON response here
                throw new ModelNotFoundException('Current occupants cannot be greater than capacity.');
            }
        });
    }
}

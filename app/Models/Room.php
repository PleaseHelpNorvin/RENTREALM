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
        'room_picture_url',
        'room_code',
        'description',
        'room_details',
        'category',
        'rent_price',
        'reservation_fee',
        'capacity',
        'current_occupants',
        'min_lease',
        'size',
        'status',
        'unit_type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'room_picture_url' => 'array', 
    ];

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class, 'room_id');
    }

    public function pickedRooms()
    {
        return $this->hasMany(PickedRoom::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalAgreements()
    {
        return $this->hasMany(RentalAgreement::class);
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

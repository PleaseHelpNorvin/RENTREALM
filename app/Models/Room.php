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
        // 'room_picture_url' => 'array', 
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
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

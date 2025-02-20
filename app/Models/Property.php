<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'property_picture_url',
        'gender_allowed',
        'pets_allowed',
        'type', 
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => 'string',
        'status' => 'string',
        'property_picture_url' => 'array',
    ];

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function rentalAgreements()
    {
        return $this->hasManyThrough(RentalAgreement::class, Room::class);
    }
}   

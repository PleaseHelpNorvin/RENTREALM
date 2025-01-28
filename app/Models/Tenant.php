<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'room_id',
        'rental_agreement_id',
        'start_date',
        'end_date',
        'rent_price',
        'deposit',
        'payment_status',
        'status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'has_pets',
        'wifi_enabled',
        'has_laundry_access',
        'has_private_fridge',
        'has_tv',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function rentalAgreement()
    {
        return $this->belongsTo(RentalAgreement::class, 'rental_agreement_id');
    }

    public function userProfile()
    {
        return $this->HasOne(userProfile::class);
    }
}

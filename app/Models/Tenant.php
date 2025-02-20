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
        'payment_status',
        'status',
        'next_payment_date',
        'evacuation_date',
        'move_out_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'next_payment_date' => 'date',
        'evacuation_date' => 'date',
        'move_out_date' => 'date',
    ];

    // public function room()
    // {
    //     return $this->belongsTo(Room::class);
    // }

    public function rentalAgreements()
    {
        return $this->belongsToMany(RentalAgreement::class, 'tenant_rental_agreements');
    }

    public function userProfile()
    {
        return $this->HasOne(userProfile::class);
    }
}

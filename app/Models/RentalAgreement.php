<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class RentalAgreement extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'room_id',
        'agreement_code',
        'rent_start_date',
        'rent_end_date',
        'payment_day_cycle',
        'rent_price',
        'deposit',
        'status',
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

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function tenants()
    {
        // return $this->hasMany(Tenant::class, 'rental_agreement_id');
        return $this->belongsToMany(Tenant::class, 'tenant_rental_agreements');

    }
}



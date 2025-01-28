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
        'rent_price',
        'deposit',
        'status'
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
}



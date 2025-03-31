<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        // 'room_id',
        'rental_agreement_id',
        'status',
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

    public function rentalAgreement()
    {
        return $this->belongsTo(RentalAgreement::class, 'rental_agreement_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'id', 'profile_id');
    }
    
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

  
}

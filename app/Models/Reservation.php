<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'profile_id',
        'room_id',
        'reservation_code',
        'payment_method',
        'reservation_payment_proof_url',
        'status',  
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approval_date'=> 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id'); 
    }

    
    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(user::class, 'approved_by');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable'); 
    }

    public function rentalAgreement()
    {
        return $this->hasOne(RentalAgreement::class, 'reservation_id'); // One reservation has one rental agreement
    }

}

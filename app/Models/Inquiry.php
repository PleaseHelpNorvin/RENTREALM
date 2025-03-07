<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'room_id',

        'name',
        'contact_no',
        'message',
        // 'status',
        // 'has_pets',
        // 'wifi_enabled',
        // 'has_laundry_access',
        // 'has_private_fridge',
        // 'has_tv',
        // 'accepted_at',
    ];

    /**
     * Relationships
     */


    // public function profile()
    // {
    //     return $this->belongsTo(UserProfile::class, 'profile_id');
    // }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable'); 
    }

    public function rentalAgreement()
    {
        return $this->hasOne(RentalAgreement::class);
    }
    
}

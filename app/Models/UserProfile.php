<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'profile_picture_url',
        'phone_number',
        'social_media_links',
 
        //identification part
        'driver_license_number',
        'national_id',
        'passport_number',
        'social_security_number',
        'occupation',
    ];


    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'profile_id');
    }

    public function reservations() 
    {
        return $this->hasMany(Reservation::class, 'profile_id');
    }

    
}

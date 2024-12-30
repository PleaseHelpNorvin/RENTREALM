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
        //address part
        'address',
        'line_1',
        'line_2',
        'province',
        'country',
        'postal_code',
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
}

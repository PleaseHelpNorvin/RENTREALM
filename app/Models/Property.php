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
        'line_1', 
        'line_2', 
        'province', 
        'country', 
        'postal_code', 
        'gender_allowed',
        'type', 
        'status'
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}   

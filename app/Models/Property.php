<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'address', 
        'city', 
        'state', 
        'barangay', 
        'zone', 
        'street',
        'postal_code', 
        'type', 
        'status'
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
    ];

    public function rooms()
    {
        return $this->hasMany(Rooms::class);
    }
}   

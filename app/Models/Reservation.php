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

    public function pickedRoom()
    {
        return $this->belongsTo(PickedRoom::class);
    }
    public function user_id()
    {
        return $this->belongsTo(profile::class, 'user_id');
    }
}

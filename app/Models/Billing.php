<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id', 
        'billable_id', 
        'billable_type', 
        'total_amount',
        'amount_paid',
        'remaining_balance',
        'billing_month',
        'status',
        'checkout_session_id',
    ];

    protected $casts = [
        'billing_month' => 'date',
    ];


    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }

    public function billable()
    {
        return $this->morphTo();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}



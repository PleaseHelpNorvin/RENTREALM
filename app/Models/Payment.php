<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $fillable = [
        'billing_id',
        'payable_id',
        'payable_type',
        'profile_id',
        'amount_due',
        'amount_paid',
        'remaining_balance',
        'payment_method',
        'paymongo_payment_reference',
        'payment_proof_url',
        'status',

    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable'); 
    }
}

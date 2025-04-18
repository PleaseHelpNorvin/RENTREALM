<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id', 
        'billable_id', 
        'billable_type', 
        'billing_title',
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

    protected static function booted()
    {
        static::saving(function ($billing) {
            if (!$billing->due_date) {
                $billing->due_date = now()->addMonth();
            }
        });
    }

    public function userProfile() {
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

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable'); 
    }
    public function rentalAgreement()
    {
        return $this->belongsTo(RentalAgreement::class, 'billable_id');
    }
}



<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class RentalAgreement extends Model
{
    use HasFactory;
    protected $fillable = [
        'reservation_id',
        'agreement_code',
        'rent_start_date',
        'rent_end_date',

        'person_count',
        'total_amount',

        'description',
        'signature_png_string',
        'status',
        'is_advance_payment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rent_start_date'=> 'date',
        'rent_end_date' => 'date',
        'signature_png_string' => 'array',
    ];

    protected static function booted()
    {
        static::created(function ($rentalAgreement) {
            $profileId = $rentalAgreement->reservation->profile_id ?? null;

            // Determine billing title based on advance_payment 
            $billingTitle = $rentalAgreement->is_advance_payment ? 'Initial Payment' : 'Monthly Payment';

            Log::info('Creating billing for RentalAgreement', [
                'rental_agreement_id' => $rentalAgreement->id,
                'profile_id' => $profileId,
                'billing_title' => $billingTitle,
                'total_amount' => $rentalAgreement->total_amount,
                'rent_start_date' => $rentalAgreement->rent_start_date,
            ]);

            Billing::create([
                'profile_id' => $profileId, 
                'billable_id' => $rentalAgreement->id,
                'billable_type' => RentalAgreement::class,
                'billing_title' => $billingTitle, // Set billing title dynamically
                'total_amount' => $rentalAgreement->total_amount, 
                'amount_paid' => 0.00,
                'remaining_balance' => $rentalAgreement->total_amount, 
                'billing_month' => Carbon::parse($rentalAgreement->rent_start_date)->startOfMonth(),
                'status' => 'pending',
            ]);

            Log::info('Billing created successfully for RentalAgreement ID: ' . $rentalAgreement->id);

        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id'); 
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'rental_agreement_id');
    }


    public function pivotTenants()
    {
        return $this->belongsToMany(Tenant::class, 'rental_agreement_tenant');
    }


}



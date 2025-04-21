<?php

namespace App\Http\Controllers\rest;
use Illuminate\Support\Str;
use App\Models\Billing;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Models\User;

use App\Http\Controllers\Controller;

class BillingController extends Controller
{
    //
    public function index()
    {
        $billing = Billing::with(
            'billable.reservation.room.property',
            'userProfile.user',
            'payments.payable',
        )->get();

        if ($billing->isEmpty()) {
            return $this->notFoundResponse([], 'No billing records found');
        }

        $billing->each(function ($bill) 
        {
            $agreement = $bill->billable;
            $reservation = $agreement->reservation ?? null;
            $room = $reservation->room ?? null;
            // Room Pictures
            if ($room && !empty($room->room_picture_url)) {
                $roomPictures = json_decode($room->room_picture_url, true);
                $room->room_picture_url = array_map(function ($url) {
                    return Str::startsWith($url, ['http://', 'https://'])
                        ? $url
                        : asset('storage/' . ltrim($url, '/'));
                }, $roomPictures);
            }
            // Reservation Payment Proof
            $reservation = optional(optional($bill->billable)->reservation);
            if ($reservation && !empty($reservation->reservation_payment_proof_url)) {
                $proofs = is_array($reservation->reservation_payment_proof_url)
                    ? $reservation->reservation_payment_proof_url
                    : json_decode($reservation->reservation_payment_proof_url, true);
                $reservation->reservation_payment_proof_url = array_map(fn($url) =>
                    asset('storage/' . ltrim($url, '/')),
                    $proofs
                );
            }

            // Payments Proofs
            foreach ($bill->payments as $payment) {
                if (!empty($payment->payment_proof_url)) {
                    $proofs = is_array($payment->payment_proof_url)
                        ? $payment->payment_proof_url
                        : json_decode($payment->payment_proof_url, true);
                    $payment->payment_proof_url = array_map(fn($url) =>
                        asset('storage/' . ltrim($url, '/')),
                        $proofs
                    );
                }

                // Payable Signature
                $signature = optional($payment->payable)->signature_png_string;
                if (!empty($signature) && !Str::startsWith($signature, ['http://', 'https://'])) {
                    $payment->payable->signature_png_string = asset('storage/' . ltrim($signature, '/'));
                }
            }

            // Billable Signature
            $signature = optional($bill->billable)->signature_png_string;
            if (!empty($signature) && !Str::startsWith($signature, ['http://', 'https://'])) {
                $bill->billable->signature_png_string = asset('storage/' . ltrim($signature, '/'));
            }

            // Profile Picture
            $profilePicture = optional($bill->userProfile)->profile_picture_url;
            if (!empty($profilePicture) && !Str::startsWith($profilePicture, ['http://', 'https://'])) {
                $bill->userProfile->profile_picture_url = asset(ltrim($profilePicture, '/'));
            }

            $bill->rentalAgreementBillable = $bill->billable;
            unset($bill->billable);
            
        });

        return $this->successResponse(['billings' => $billing], 'All Billing records retrieved successfully');
    }

    public function getBillingForRentalAgreement($rentalAgreementId)
    {
        // Retrieve the rental agreement including the related reservation
        $rentalAgreement = RentalAgreement::with('reservation')->find($rentalAgreementId);

        if (!$rentalAgreement) {
            return $this->notFoundResponse(null, 'Rental agreement not found');
        }

        $profileId = $rentalAgreement->reservation->profile_id;
       
        $billing = Billing::where('profile_id', $profileId)
            ->where('billable_id', $rentalAgreement->id)
            ->where('billable_type', 'App\\Models\\RentalAgreement')
            ->get();


        if ($billing->isEmpty()) {
            return $this->notFoundResponse([], 'No billing records found for this rental agreement');
        }

        return $this->successResponse(['billings' => $billing], 'Billing records retrieved succ essfully');
    }

    public function getBillingDetails($billingId)
    {
        $billing = Billing::find($billingId);
    
        if (!$billing) {
            return $this->notFoundResponse(null, "No billing details found for ID: $billingId");
        }
    
        return $this->successResponse(['billings' => [$billing]], "Billing details retrieved successfully.");
    }

    public function retrieveLatestBillingForMonthlyRent($user_id)
    {
        $user = User::with('userProfile.billings')->find($user_id);
    
        if (!$user) {
            return null; // or throw an exception
        }
    
        // Flatten all billings from all user profiles and filter them
        $latestBilling = $user->userProfile
            ->flatMap(function ($profile) {
                return $profile->billings->where('billing_title', 'Monthly Rent');
            })
            ->sortByDesc('billing_month')
            ->first();
    
        return $this->successResponse(['latest_rent_billing' => $latestBilling], 'success latest billing');
    }
}

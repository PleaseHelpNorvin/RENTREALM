<?php

namespace App\Http\Controllers\rest;

use App\Models\Billing;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Http\Controllers\Controller;

class BillingController extends Controller
{
    //
    public function index() {
        $billing = Billing::all();
    
        if ($billing->isEmpty()) {
            return $this->notFoundResponse([], 'No billing records found');
        }
    
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

        return $this->successResponse(['billings' => $billing], 'Billing records retrieved successfully');
    }

    public function getBillingDetails($billingId)
    {
        $billing = Billing::find($billingId);
    
        if (!$billing) {
            return $this->notFoundResponse(null, "No billing details found for ID: $billingId");
        }
    
        return $this->successResponse(['billings' => [$billing]], "Billing details retrieved successfully.");
    }
}

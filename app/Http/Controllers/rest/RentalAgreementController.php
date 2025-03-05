<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Models\Property;
use App\Models\Room;

class RentalAgreementController extends Controller
{
    public function index()
    {
        $rentalAgreements = RentalAgreement::all();

        if ($rentalAgreements->isEmpty()) {
            return $this->errorResponse(null, "no rental agreements found");
        }
        return $this->successResponse(['rentalAgreements' => $rentalAgreements], "rental agreements successfully fetched");
    }

    public function store(Request $request) 
    {
        $validatedData = $request->validate([
            'inquiry_id' => 'required|exists:inquiries,id', 
            'rent_start_date' => 'required|date',  
            'rent_end_date' => 'nullable|date',  
            'person_count' => 'required|integer|min:1',
            'total_monthly_due' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'signature_svg_string' => 'required|string',
        ]);
    
        // Generate agreement_code (format: agreement-XXXXXX)
        $validatedData['agreement_code'] = 'agreement-' . mt_rand(100000, 999999);
        $validatedData['status'] = 'active';
        // Create Rental Agreement
        $rentalAgreement = RentalAgreement::create($validatedData);
        

        
        return $this->successResponse(['rentalAgreement' => [$rentalAgreement]], "Rental agreement created successfully");
    }
    
}

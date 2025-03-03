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
        return $this->successResponse(['rentalAgreements' => [$rentalAgreements], "rental agreements successfully fetched"]);
    }

    public function store(Request $request) 
    {
        $validatedData = $request->validate([
            'inquiry_id' => 'required|exists:inquiries,id',
            'rent_end_date' => 'nullable|date',
            'deposit' => 'nullable|numeric',
            'description' => 'nullable|string',
            'signature_svg_string' => 'required|string',
            // 'status' => 'required|in:active,inactive',
        ]);
    
        $inquiry = Inquiry::with('room')->findOrFail($validatedData['inquiry_id']);
    
        // Ensure rent_start_date is set only when the inquiry was accepted
        if (!$inquiry->accepted_at) {
            return $this->errorResponse(null, "The inquiry has not been accepted yet");
        }
    
        $validatedData['rent_start_date'] = $inquiry->accepted_at;
        $validatedData['rent_price'] = $inquiry->room->rent_price; 
    
        // Create the Rental Agreement
        $rentalAgreement = RentalAgreement::create($validatedData);
    
        return $this->successResponse(['rentalAgreement' => $rentalAgreement], "Rental agreement created successfully");
    }
    
}

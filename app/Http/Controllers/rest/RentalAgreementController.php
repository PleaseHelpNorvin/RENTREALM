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
        $rentalAgreement = RentalAgreement::all();

        if($rentalAgreement -> isEmpty())
        {
            return $this->notFoundResponse(null, 'no rental agreement found');
        }

        return $this-> successResponse(['rental_agreements' => $rentalAgreement], 'Rental Agreements fetched Successfully');
    }

    public function store(Request $request)
    {
        \Log::info($request->all());

        $room = Room::findOrFail($request->room_id); // Fetch the room details
        $minLeaseMonths = $room->min_lease; // Get the min_lease value (e.g., 10 months)
        $minLeaseDate = Carbon::parse($request->rent_start_date)->addMonths($minLeaseMonths); // Calculate minimum lease end date
        
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id', // Ensure property exists
            'room_id' => 'required|exists:rooms,id', // Ensure room exists
            'rent_start_date' => 'required|date',
            'rent_end_date' => [
                'nullable',
                'date',
                'after_or_equal:rent_start_date',
                function ($attribute, $value, $fail) use ($minLeaseDate, $minLeaseMonths) {
                    if (Carbon::parse($value)->lt($minLeaseDate)) {
                        $fail('The rent end date must be at least ' . $minLeaseDate->format('Y-m-d') . ' to meet the minimum lease requirement of ' . $minLeaseMonths . ' month(s).');
                    }
                }
            ],
            'rent_price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0|gte:' . (0.4 * $request->rent_price), // Ensure deposit is at least 40% of rent_price
            'status' => 'required|in:active,inactive', // Ensure status is either 'active' or 'inactive'
        ], [
            'deposit.gte' => 'The deposit should be a minimum of 40% of the rent price, which is ' . (0.4 * $request->rent_price) . '.',
        ]);


        $agreementCode = 'agreement-' . Str::random(6) . rand(100, 999);
        
        $rentalAgreement = RentalAgreement::create([
            'property_id' => $validated['property_id'],
            'room_id' => $validated['room_id'],
            'agreement_code' => $agreementCode,
            'rent_start_date' => $validated['rent_start_date'],
            'rent_end_date' => $validated['rent_end_date'],
            'rent_price' => $validated['rent_price'],
            'deposit' => $validated['deposit'],
            'status' => $validated['status'],
        ]);

        return $this->successResponse(['rental_agreement' => $rentalAgreement], 'Rental Agreement successfully created.');
    }

    public function show($rentalagreement_id, Request $request)
    {
        $rentalAgreement = RentalAgreement::find($rentalagreement_id);

        if(!$rentalAgreement)
        {
            return $this->notFoundResponse(null, 'Rental Agreement not Found');
        }
        
        $agreementCode = $rentalAgreement->agreement_code;
        
        return $this->successesponse(['rental_agreement' => $rentalAgreement], "Rental Agreement $agreementCode fetched Successfully");
    }

    public function update($rentalagreement_id, Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id', // Ensure property exists
            'room_id' => 'required|exists:rooms,id', // Ensure room exists
            'rent_start_date' => 'required|date',
            'rent_end_date' => 'nullable|date|after_or_equal:rent_start_date', // Ensure rent_end_date is after start date
            'rent_price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive', // Ensure status is either 'active' or 'inactive'
        ]);

        $rentalAgreement = RentalAgreement::findOrFail($rentalagreement_id);
        if(!$rentalAgreement)
        {   
            return $this->notFoundResponse(null, 'Rental Agreement notFOund');
        }


        $agreementCode = $rentalAgreement->agreement_code;

        $rentalAgreement->update($validated);

        return $this->successResponse(['rentalAgreement' => $rentalAgreement], "Rental Agreement $agreementCode Updated Successfully");
    }

    // Public function showByRoomId($room_id, Request $request)
    // {
        
    // }



    public function destroy($rentalagreement_id, Request $request)
    {
        
    }
}

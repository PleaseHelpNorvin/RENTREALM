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
            return $this->notFoundResponse(null, 'No rental agreements found.');
        }

        return $this->successResponse(['rental_agreements' => $rentalAgreements], 'Rental Agreements fetched successfully.');
    }

    public function store(Request $request)
    {
        \Log::info($request->all());

        // Fetch the deposit percentage from settings, default to 40% if not found
        // $depositPercentage = Setting::where('key', 'deposit_percentage')->value('value') ?? 0.4;
        $depositPercentage = 0.4;

        $room = Room::findOrFail($request->room_id);
        $minLeaseMonths = $room->min_lease;
        $minLeaseDate = Carbon::parse($request->rent_start_date)->addMonths($minLeaseMonths);

        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_id' => 'required|exists:rooms,id',
            'rent_start_date' => 'required|date',
            'rent_end_date' => [
                'nullable',
                'date',
                'after_or_equal:rent_start_date',
                function ($attribute, $value, $fail) use ($minLeaseDate, $minLeaseMonths) {
                    if ($value && Carbon::parse($value)->lt($minLeaseDate)) {
                        $fail("The rent end date must be at least {$minLeaseDate->format('Y-m-d')} to meet the minimum lease requirement of $minLeaseMonths month(s).");
                    }
                }
            ],
            'payment_day_cycle' => 'required|integer|in:15,30',
            'rent_price' => 'required|numeric|min:0',
            'deposit' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:rent_price',
                function ($attribute, $value, $fail) use ($request, $depositPercentage) {
                    if ($request->rent_price) {
                        $minDeposit = $depositPercentage * $request->rent_price;
                        if ($value < $minDeposit) {
                            $fail("The deposit should be at least " . ($depositPercentage * 100) . "% of the rent price, which is $minDeposit.");
                        }
                    }
                }
            ],
            'status' => 'required|in:active,inactive',
            'has_pets' => 'boolean',
            'wifi_enabled' => 'boolean',
            'has_laundry_access' => 'boolean',
            'has_private_fridge' => 'boolean',
            'has_tv' => 'boolean',
        ]);

        $agreementCode = 'agreement-' . Str::random(6) . rand(100, 999);

        $rentalAgreement = RentalAgreement::create(array_merge($validated, ['agreement_code' => $agreementCode]));

        return $this->successResponse(['rental_agreement' => $rentalAgreement], 'Rental Agreement successfully created.');
    }

    public function show($rentalagreement_id)
    {
        $rentalAgreement = RentalAgreement::find($rentalagreement_id);

        if (!$rentalAgreement) {
            return $this->notFoundResponse(null, 'Rental Agreement not found.');
        }

        return $this->successResponse(['rental_agreement' => $rentalAgreement], "Rental Agreement {$rentalAgreement->agreement_code} fetched successfully.");
    }

    public function update($rentalagreement_id, Request $request)
    {
        $rentalAgreement = RentalAgreement::find($rentalagreement_id);

        if (!$rentalAgreement) {
            return $this->notFoundResponse(null, 'Rental Agreement not found.');
        }

        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_id' => 'required|exists:rooms,id',
            'rent_start_date' => 'required|date',
            'rent_end_date' => 'nullable|date|after_or_equal:rent_start_date',
            'rent_price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'has_pets' => 'boolean',
            'wifi_enabled' => 'boolean',
            'has_laundry_access' => 'boolean',
            'has_private_fridge' => 'boolean',
            'has_tv' => 'boolean',
        ]);

        $rentalAgreement->update($validated);

        return $this->successResponse(['rental_agreement' => $rentalAgreement], "Rental Agreement {$rentalAgreement->agreement_code} updated successfully.");
    }

    public function destroy($rentalagreement_id)
    {
        $rentalAgreement = RentalAgreement::find($rentalagreement_id);

        if (!$rentalAgreement) {
            return $this->notFoundResponse(null, 'Rental Agreement not found.');
        }

        $rentalAgreement->delete();

        return $this->successResponse(null, "Rental Agreement {$rentalAgreement->agreement_code} deleted successfully.");
    }
}

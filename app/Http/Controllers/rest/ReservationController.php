<?php

namespace App\Http\Controllers\rest;

use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;


class ReservationController extends Controller
{
    public function index() {
        $reservations = Reservation::all();

        $reservations = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'profile_id' => $reservation->profile_id,
                'room_id' => $reservation->room_id,
                'payment_method' => $reservation->payment_method,
                'reservation_payment_proof_url' => collect(json_decode($reservation->reservation_payment_proof_url))->map(function ($url) {
                    return asset("storage/{$url}");
                })->toArray(),
                'status' => $reservation->status,
                'approved_by' => $reservation->approved_by,
                'approval_date' => $reservation->approval_date,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
            ];
        });

        return $this->successResponse(['reservations' => $reservations], 'Reservations fetched successfully');
    }

    public function store(Request $request) {
        $validatedData = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'room_id' => 'required|exists:rooms,id',
            'payment_method' => 'required|String|',
            'reservation_payment_proof_url' => 'required|array',
            'reservation_payment_proof_url.*' => 'file|mimes:png,jpeg,jpg|max:2048',
        ]);

        $reservationCode = 'reservation-' . strtoupper(Str::random(6));

        // Handle file uploads
        $proofUrls = [];
        if ($request->hasFile('reservation_payment_proof_url')) {
            foreach ($request->file('reservation_payment_proof_url') as $file) {
                $proofUrls[] = $file->store('reservation_payment_proofs', 'public');
            }
        }

        // Save the reservation
        $reservation = Reservation::create([
            'profile_id' => $validatedData['profile_id'],
            'room_id' => $validatedData['room_id'],
            'reservation_code' => $reservationCode,
            'payment_method' => $validatedData['payment_method'],
            'reservation_payment_proof_url' => json_encode($proofUrls),
            'status' => 'pending',
        ]);

        return $this->createdResponse($reservation, 'Reservation created successfully');
    }

    
}

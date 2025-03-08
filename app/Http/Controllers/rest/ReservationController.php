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
                'user_id' => $reservation->user_id,
                'room_id' => $reservation->room_id,
                'amount' => $reservation->amount,
                'reservation_payment_proof_url' => collect(json_decode($reservation->reservation_payment_proof_url))->map(function ($url) {
                    return asset("storage/{$url}");
                })->toArray(),
                'status' => $reservation->status,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
            ];
        });

        return $this->successResponse(['reservations' => $reservations], 'Reservations fetched successfully');
    }

    public function store(Request $request) {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'amount' => 'required|numeric|min:0',
            'reservation_payment_proof_url' => 'required|array',
            'reservation_payment_proof_url.*' => 'file|mimes:png,jpeg,jpg|max:2048',
        ]);

        $reservationCode = 'reservation-' . strtoupper(Str::random(6));

        // Handle file uploads
        $proofUrls = [];
        if ($request->hasFile('reservation_payment_proof_url')) {
            foreach ($request->file('reservation_payment_proof_url') as $file) {
                $proofUrls[] = $file->store('payment_proofs', 'public');
            }
        }

        // Save the reservation
        $reservation = Reservation::create([
            'user_id' => $validatedData['user_id'],
            'room_id' => $validatedData['room_id'],
            'reservation_code' => $reservationCode,
            'amount' => $validatedData['amount'],
            'reservation_payment_proof_url' => json_encode($proofUrls),
            'status' => 'pending',
        ]);

        return $this->createdResponse($reservation, 'Reservation created successfully');
    }
}

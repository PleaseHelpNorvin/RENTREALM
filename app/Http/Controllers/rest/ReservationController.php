<?php

namespace App\Http\Controllers\rest;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


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

        $reservation->notifications()->create([
            'user_id' => $reservation->userProfile->user->id,
            'title' => 'Your Reservation is being reviewed by the admins',
            'message' => 'Please wait for possible call from management. Also, check your notification for your reservation updates.',
            'is_read' => false
        ]);

        return $this->createdResponse($reservation, 'Reservation created successfully');
    }

    public function updateStatus(Request $request, $id) {

        $reservation = Reservation::find($id);
    
        if (!$reservation) {
            return $this->notFoundResponse(null, "Reservation Not Found");
        }
    
        // Manually retrieve data instead of expecting JSON
        $validatedData = $request->validate([
            'status' => 'required|in:pending,approved',
            'approved_by' => 'required|exists:users,id',
        ]);
    
        $reservation->update([
            'status' => $validatedData['status'],
            'approved_by' => $validatedData['approved_by'],
            'approval_date' => now(),
        ]);

        $adminId = $reservation->approved_by;
        $adminName = User::find($adminId)->name;
        
        $clientId = $reservation->userProfile->user_id;
        $createdAt = $reservation->created_at;
        $reservation->notifications()->create([
            'user_id' => $clientId,
            'title' => "Reservation Accepted!",
            'message' => "Your Reservation on $createdAt has been accepted. Please Proceed to the commitment agreement signing alongside with the rental payment.",
            'is_read' => false
        ]);
    
        return $this->successResponse(['reservation' => $reservation], 'Reservation status updated successfully');
        // use something like for this endpoint
        // method: patch
        // http://127.0.0.1:8000/api/landlord/reservation/updateStatus/1?status=confirmed&approved_by=1
    }

    public function show($id) {
        $reservation = Reservation::find($id);
        
        if (!$reservation) {
            return $this->notFoundResponse(null, "Reservation Not Found");
        }
    
        return $this->successResponse(['reservations' => [$reservation]], 'Reservation retrieved successfully');
    }
    
                
}

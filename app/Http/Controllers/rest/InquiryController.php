<?php

namespace App\Http\Controllers\rest;

use App\Models\Room;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InquiryController extends Controller
{
    //
    public function index() {
        $inquiries = Inquiry::all();
        if($inquiries->isEmpty()) {
            return $this->notFoundResponse(null, "No Inquiries Found");
        }

        return $this->successResponse(['inquiries' => $inquiries], 'Inquries fetched successfully', 200);
    }

    public function store(Request $request) 
    {
        \Log::info($request->all());
        
        $Validated = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'room_id' => 'required|exists:rooms,id',
            // 'has_pets' => 'boolean',
            // 'wifi_enabled' => 'boolean',
            // 'has_laundry_access' => 'boolean',
            // 'has_private_fridge' => 'boolean',
            // 'has_tv' => 'boolean',
        ]);


        $inquiry = Inquiry::create($Validated);
        
        $room = $inquiry->room;
        $property = $room->property;
        $address = $property->address; 
    
        $propertyName = $property->name;
        $fullAddress = $address ? "{$address->line_1}, {$address->line_2}, {$address->province}, {$address->country}, {$address->postal_code}" : 'No address available';
    

        $inquiry->notifications()->create([
            'user_id' => $inquiry->profile->user_id,
            'title' => "Inquiry Being Reviewed!",
            'message' => "Thank you for inquiring about room {$room->room_code} at '{$propertyName}', located at {$fullAddress}. We will notify you with updates regarding your inquiry through notifications.",
            'is_read' => false,
        ]);

        return $this->createdResponse(['inquiry' => [$inquiry]], 'Your Inquiry has been reviewed now by the admins or landlord');
    }

    public function show($id)
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return $this->notFoundResponse(null, "Inquiry $id is not found");
        }

        // Fetch the room details
        $room = Room::find($inquiry->room_id);

        if (!$room) {
            return $this->notFoundResponse(null, "Room {$inquiry->room_id} is not found");
        }

        // Attach the rent_price from the room to the inquiry response
        $inquiryData = $inquiry->toArray();
        $inquiryData['rent_price'] = $room->rent_price; 

        return $this->successResponse(['inquiry' => [$inquiryData]], "Inquiry $id Found");
    }


    public function update(Request $request, $id)
    {
        $inquiry = Inquiry::find($id);
    
        if (!$inquiry) {
            return $this->notFoundResponse(null, "Inquiry $id not found");
        }
    
        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,rejected',
        ]);

        if ($validated['status'] === 'accepted') {
            $inquiry->accepted_at = now();
        }
    
        $inquiry->update($validated);

        $inquiry->notifications()->create([
            'user_id' => $inquiry->profile->user_id,
            'title' => "Inquiry Accepted!",
            'message' => "Your inquiry on {$inquiry->room->room_code} has been accepted. Admins might call you for further instructions.",
            'is_read' => false,
        ]);
    
        return $this->successResponse(['inquiry' => $inquiry], "Inquiry $id updated successfully");
    }


    public function getInquiriesByRoomCode($room_code)
    {
        $inquiries = Inquiry::whereHas('room', function ($query) use ($room_code) {
            $query->where('room_code', $room_code);
        })->get();

        if ($inquiries->isEmpty()) {
            return $this->notFoundResponse(null, "No inquiries found for room code $room_code");
        }

        return $this->successResponse(['inquiries' => $inquiries], "Inquiries for room code $room_code fetched successfully");
    }
}

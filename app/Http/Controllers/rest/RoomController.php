<?php

namespace App\Http\Controllers\rest;

use App\Models\Room;
use App\Models\Property;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class RoomController extends Controller
{
    // Fetch all rooms
    public function index()
    {
        $rooms = Room::all();

        if ($rooms->isEmpty()) {
            return $this->notFoundResponse(null, 'No Rooms found.');
        }

        return $this->successResponse(['rooms' => $rooms], 'Rooms fetched successfully.');
    }

    // Fetch a specific room by ID
    public function show($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->notFoundResponse(null, 'Room not found.');
        }

        $room->room_picture_url = json_decode($room->room_picture_url);

        return $this->successResponse(['rooms'=>$room], 'Room details.');
    }

    public function showRoomsByPropertyId($property_id) 
    {
        $rooms = Room::where("property_id", $property_id)->get();
        
        if(!$rooms) {
            return $this->notFoundResponse(null, "No rooms in property: $property_id");
        }
        $rooms->transform(function($room) {
            $room->room_picture_url = url('storage/' . $room->room_picture_url);
            return $room;
        });
    

        return $this->successResponse(['rooms' => $rooms], "Rooms in property: $property_id is fetched successfully");
    }

    // Store a new room
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_picture_url' => 'nullable|array', // Allow array of images
            'room_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate each image
            'rent_price' => 'nullable|numeric',
            'capacity' => 'required|integer',
            'current_occupants' => 'nullable|integer',
            'min_lease' => 'required|integer',
            'status' => 'required|in:available,rented,under maintenance,full',
        ]);
    
        // Check if current_occupants is greater than capacity
        if (isset($validatedData['current_occupants']) && $validatedData['current_occupants'] > $validatedData['capacity']) {
            return $this->errorResponse(
                null,
                'Current occupants cannot be greater than the room capacity.',
                400
            );
        }
    
        // Generate the room_code automatically
        $room_code = 'room-' . Str::random(6) . rand(100, 999);
    
        // Add the room_code to the validated data
        $validatedData['room_code'] = $room_code;
    
        // Handle the multiple image uploads if provided
        $imageUrls = [];
        if ($request->hasFile('room_picture_url')) {
            foreach ($request->file('room_picture_url') as $image) {
                // Store each image and generate its URL
                $imagePath = $image->store('room_pictures', 'public');
                $imageUrls[] = asset('storage/' . $imagePath); // Store URL instead of the file path
            }
        }
    
        // Convert the image URLs array to JSON format
        $validatedData['room_picture_url'] = json_encode($imageUrls);
    
        // Create a new room
        $room = Room::create($validatedData);
    
        return $this->successResponse(['rooms' => $room], 'Room created successfully.');
    }
        
    // Update an existing room
    public function update(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->notFoundResponse(null, 'Room not found.');
        }

        // Validate the request data
        $validatedData = $request->validate([
            'property_id' => 'exists:properties,id',
            'room_picture_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
            'rent_price' => 'nullable|numeric',
            'capacity' => 'required|integer',
            'current_occupants' => 'nullable|integer',
            'min_lease' => 'required|integer',
            'status' => 'required|in:available,rented,under maintenance,full',
        ]);

        // Check if current_occupants is greater than capacity
        if (isset($validatedData['current_occupants']) && $validatedData['current_occupants'] > $validatedData['capacity']) {
            return $this->errorResponse(
                null, 
                'Current occupants cannot be greater than the room capacity.',
                400
            );
        }

        // Handle the image upload if a picture is provided
        if ($request->hasFile('room_picture_url')) {
            // Delete the old image if it exists
            if ($room->room_picture_url && file_exists(storage_path('app/public/' . $room->room_picture_url))) {
                unlink(storage_path('app/public/' . $room->room_picture_url));
            }

            // Store the new image
            $imagePath = $request->file('room_picture_url')->store('room_pictures', 'public');
            $validatedData['room_picture_url'] = $imagePath;
        }

        // Update the room
        $room->update($validatedData);

        return $this->successResponse($room, 'Room updated successfully.');
    }

    // Delete a room
    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->notFoundResponse(null, 'Room not found.');
        }

        // Delete the room
        $room->delete();

        return $this->successResponse(null, 'Room deleted successfully.');
    }
}


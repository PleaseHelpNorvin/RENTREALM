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
            'room_picture_url' => 'required|array', // Ensure room_picture_url is an array of images
            'room_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg', // Validate each image file type
            'description' => 'required|string|max:255',
            'room_details' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'rent_price' => 'nullable|numeric',
            'capacity' => 'required|integer',
            'current_occupants' => 'nullable|integer',
            'min_lease' => 'required|integer',
            'size' => 'required|string|max:20',
            'status' => 'required|in:available,rented,under_maintenance,full',
            'unit_type' => 'required|in:studio_unit,triplex_unit,alcove,loft_unit,shared_unit,micro_unit',
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
                $imageUrls[] = asset('storage/' . $imagePath); // Save the URL
            }
        }
    
        // Convert the image URLs array to JSON format
        $validatedData['room_picture_url'] = json_encode($imageUrls);
    
        // Create a new room entry with the validated data
        $room = Room::create($validatedData);
    
        return $this->successResponse(['rooms' => $room], 'Room created successfully.');
    }
            
    // Update an existing room
    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_picture_url' => 'nullable|array', // Ensure room_picture_url is an array of images
            'room_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|nullable', // Validate each image file type
            // 'description'
            // 'room_details'
            // 'category'
            'rent_price' => 'nullable|numeric',
            'capacity' => 'required|integer',
            'current_occupants' => 'nullable|integer',
            'min_lease' => 'required|integer',
            // 'size'
            'status' => 'required|in:available,rented,under maintenance,full',
            // 'unit_type'
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
        if ($request->hasFile('room_picture_url') && count($request->file('room_picture_url')) > 0) {
            $imageUrls = [];
            foreach ($request->file('room_picture_url') as $image) {
                // Store each image and generate its URL
                $imagePath = $image->store('room_pictures', 'public');
                $imageUrls[] = asset('storage/' . $imagePath); // Save the URL
            }
            // Convert the image URLs array to JSON format
            $validatedData['room_picture_url'] = json_encode($imageUrls);
        } else {
            // If no new images are selected, retain the current room picture URLs
            $room = Room::findOrFail($id);
            if ($room->room_picture_url) {
                // Keep the old room picture URLs if none were selected
                $validatedData['room_picture_url'] = $room->room_picture_url;
            }
        }
    
        // Fetch the room instance by its ID
        $room = Room::findOrFail($id);
    
        // Update the room with the validated data
        $room->update($validatedData);
    
        return $this->successResponse(['rooms' => $room], 'Room updated successfully.');
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


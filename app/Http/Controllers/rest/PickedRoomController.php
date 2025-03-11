<?php

namespace App\Http\Controllers\rest;

use App\Models\User;
use App\Models\PickedRoom;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PickedRoomController extends Controller
{
    //
    public function index() 
    {
        $pickedRooms = PickedRoom::all();
        
        if($pickedRooms->isEmpty()) {
            return $this->notFoundResponse(null, "No Picked Room has Found");
        }

        return $this->successResponse(['picked_rooms' => $pickedRooms],'picked rooms fetch successfully');
    }

    // Function to get rooms picked by a specific user
    public function getRoomsByUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return $this->notFoundResponse(null, "User not found");
        }

        $pickedRooms = PickedRoom::with('room')->where('user_id', $userId)->get();

        if ($pickedRooms->isEmpty()) {
            return $this->notFoundResponse(null, "No rooms picked by this user");
        }

        return $this->successResponse(['picked_rooms' => $pickedRooms], 'User picked rooms fetched successfully');
    }

    // Function to add a room for a user
    public function addRoomForUser(Request $request)
    {
        try {
            // Validate request
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'room_id' => 'required|exists:rooms,id'
            ]);

            // Check if the user already picked this room
            $alreadyPicked = PickedRoom::where('user_id', $validatedData['user_id'])
                                        ->where('room_id', $validatedData['room_id'])
                                        ->exists();

            if ($alreadyPicked) {
                return $this->errorResponse(null, "User has already picked this room");
            }

            // Create new picked room entry
            $pickedRoom = PickedRoom::create([
                'user_id' => $validatedData['user_id'],
                'room_id' => $validatedData['room_id']
            ]);

            return $this->successResponse(['picked_room' => [$pickedRoom]], "Room successfully added for the user");
        } catch (ValidationException $e) {
            return $this->errorResponse(null, $e->errors());
        }
    }

    public function destroy($id)
    {
        // Find the picked room by ID
        $pickedRoom = PickedRoom::find($id);

        // If picked room not found, return an error response
        if (!$pickedRoom) {
            return $this->notFoundResponse(null, "Picked Room not found");
        }

        // Delete the picked room record from the database
        $pickedRoom->delete();

        return $this->successResponse((object)[], "Picked Room deleted successfully");
    }
}

<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::all();

        if($users -> isEmpty()) {
            return $this->notFoundResponse(null, 'No Users found');
        }

        return $this->successResponse(['users' => $users], 'Users Fetched Successfully');
    }
    
    public function update(Request $request, $id) 
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            return $this->notFoundResponse(null, 'User not found');
        }

        // Validate incoming data
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            // 'role' => 'nullable|in:' . implode(',', [User::LANDLORD, User::HANDYMAN, User::TENANT]),
        ]);

        // Update the user fields
        $user->update($validated);

        // Return the updated user response
        return $this->successResponse(['user' => $user], 'User updated successfully');
    }
}

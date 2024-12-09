<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserProfile;


class UserProfileController extends Controller
{
    /**
     * Display a listing of user profiles.
     */
    public function index()
    {
        $profiles = UserProfile::all();

        return $this->successResponse($profiles);
    }

    /**
     * Store a newly created user profile in storage.
     */
    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone_number' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);

        // Create the profile
        $profile = UserProfile::create($validated);

        return $this->successResponse($profile, 'Profile created successfully.');
    }

    /**
     * Display the specified user profile.
     */
    public function show($id)
    {
        $profile = UserProfile::find($id);

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }

        return $this->successResponse($profile);
    }

    /**
     * Show the form for editing the specified user profile.
     */
    public function edit($id)
    {
        $profile = UserProfile::find($id);

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }

        // Return profile for editing
        return $this->successResponse($profile, 'Ready to edit profile.');
    }

    /**
     * Update the specified user profile in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the input data
        $validated = $request->validate([
            'phone_number' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);

        // Find and update the profile
        $profile = UserProfile::find($id);

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }

        $profile->update($validated);

        return $this->successResponse($profile, 'Profile updated successfully.');
    }

    /**
     * Remove the specified user profile from storage.
     */
    public function destroy($id)
    {
        $profile = UserProfile::find($id);

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }

        $profile->delete();

        return $this->successResponse(null, 'Profile deleted successfully.');
    }
}

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
        if($profiles->isEmpty()) {
            return $this->notFoundResponse('No profiles Found');
        }
        return $this->successResponse(['profile' => $profiles], 'Profiles Fetched Successfully');
    }

    /**
     * Store a newly created user profile in storage.
     */
    public function store(Request $request)
    {
        $user_id = $request->query('user_id');
        // Validate the input data
        $validated = $request->validate([
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validates an image file
            'phone_number' => 'required|string|max:255',
            'municipality' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);
    
        // Construct the address
        $address = ($request->street ? $request->street . ', ' : '') .
                   ($request->barangay ? $request->barangay . ', ' : '') .
                   ($request->zone ? $request->zone . ', ' : '') .
                   ($request->state ? $request->state . ', ' : '') .
                   ($request->city ? $request->city . ', ' : '') .
                   ($request->country ? $request->country . ', ' : '') .
                   ($request->postal_code ? $request->postal_code : '');
    
        // Handle the file upload
        if ($request->hasFile('profile_picture_url')) {
            $imagePath = $request->file('profile_picture_url')->store('profile_pictures', 'public');
            $validated['profile_picture_url'] = '/storage/' . $imagePath; // Store public URL
        }
    
        // Create the user profile
        $profile = UserProfile::create([
            'user_id' => $user_id,
            'profile_picture_url' => $validated['profile_picture_url'] ?? null,
            'phone_number' => $validated['phone_number'],
            'municipality' => $validated['municipality'] ?? null,
            'city' => $validated['city'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'street' => $validated['street'] ?? null,
            'zone' => $validated['zone'] ?? null,
            'country' => $validated['country'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'driver_license_number' => $validated['driver_license_number'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'passport_number' => $validated['passport_number'] ?? null,
            'social_security_number' => $validated['social_security_number'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'address' => $address, // Store the constructed address
        ]);
    
        return $this->successResponse(['profile' => $profile], 'User profile created successfully.', 201);
    }

    /**
     * Display the specified user profile.
     */
    // public function show($id)
    // {
    //     $profile = UserProfile::find($id);

    //     if (!$profile) {
    //         return $this->notFoundResponse(null, 'Profile not found');
    //     }

    //     return $this->successResponse($profile);
    // }

    /**
     * Display the specified user profile.
     */
    public function showByUserId($user_id)
    {
        $profile = UserProfile::where('user_id', $user_id)->first();

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not f ound');
        }

        // Convert the `profile_picture_url` to a full URL
        if ($profile->profile_picture_url) {
            $profile->profile_picture_url = asset($profile->profile_picture_url);
        }

        return $this->successResponse(['profile' => $profile], 'User Profile Fetched Successfully');
    }

    /**
     * Show the form for editing the specified user profile.
     */
    // public function edit($id)
    // {
    //     $profile = UserProfile::find($id);

    //     if (!$profile) {
    //         return $this->notFoundResponse(null, 'Profile not found');
    //     }

    //     // Return profile for editing
    //     return $this->successResponse($profile, 'Ready to edit profile.');
    // }

    /**
     * Update the specified user profile in storage.
     */
    public function update(Request $request, $user_id)
    {
        // Validate the input data
        $validated = $request->validate([
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|url',
            'phone_number' => 'required|string|max:255',
            'social_media_links' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',  // Added municipality validation
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:255',      // Added barangay validation
            'street' => 'nullable|string|max:255',         // Added street validation
            'zone' => 'nullable|string|max:255',           // Added zone validation
            'postal_code' => 'nullable|string|max:20',
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);

        // Find the profile by user_id
        $profile = UserProfile::where('user_id', $user_id)->first();

        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }

        // If there's a new profile picture, handle the file upload
        if ($request->hasFile('profile_picture_url')) {
            // Delete the old profile picture if it exists
            if ($profile->profile_picture_url && file_exists(public_path($profile->profile_picture_url))) {
                unlink(public_path($profile->profile_picture_url));
            }

            // Upload the new profile picture
            $imagePath = $request->file('profile_picture_url')->store('profile_pictures', 'public');
            $validated['profile_picture_url'] = '/storage/' . $imagePath; // Store public URL
        }

        // Construct the updated address
        $address = ($request->street ? $request->street . ', ' : '') .
                ($request->barangay ? $request->barangay . ', ' : '') .
                ($request->zone ? $request->zone . ', ' : '') .
                // ($request->state ? $request->state . ', ' : '') .
                ($request->city ? $request->city . ', ' : '') .
                ($request->municipality ? $request->municipality . ', ' : '') .
                ($request->country ? $request->country . ', ' : '') .
                ($request->postal_code ? $request->postal_code : '');

        // Update the profile with validated data
        $profile->update(array_merge($validated, ['address' => $address]));

        return $this->successResponse(['profile' => $profile], 'Profile updated successfully.');
    }

    
}

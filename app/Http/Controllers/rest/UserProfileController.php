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

    public function storePicture(Request $request) {
        \Log::info($request->all());
    
        try {
            // Step 1: Get user_id from query string
            $user_id = $request->query('user_id');
    
            // Step 2: Validate the input
            $validated = $request->validate([
                'profile_picture_url' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
            ]);
    
            // Step 3: Handle file upload
            $profile_picture_url = null;
            if ($request->hasFile('profile_picture_url')) {
                $imagePath = $request->file('profile_picture_url')->store('profile_pictures', 'public');
                $profile_picture_url = '/storage/' . $imagePath; // Generate public URL
            } else {
                return $this->validationErrorResponse(['profile_picture_url' => 'File upload failed'], 'Validation Error');
            }
    
            // Step 4: Check if UserProfile already exists
            $profile = UserProfile::updateOrCreate(
                ['user_id' => $user_id],
                ['profile_picture_url' => $profile_picture_url]
            );
    
            // Step 5: Return a dynamic success response
            $message = $profile->wasRecentlyCreated 
                ? 'Profile picture created successfully.' 
                : 'Profile picture updated successfully.';
    
            return $this->successResponse(['profilePicture'=> $profile], $message, 200);
    
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
    
            // Step 6: Handle any unexpected errors
            return $this->internalServerErrorResponse(null, 'An error occurred while saving the profile picture');
        }
    }
    
    /**
     * Store a newly created user profile in storage.
     */
    public function store(Request $request)
    {
        \Log::info($request->all());  // Log all incoming data

        // Get user_id from the query string
        $user_id = $request->query('user_id');

        // Validate the input data
        $validated = $request->validate([
            // 'profile_picture_url' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Ensure it's an image and under 10MB
            'phone_number' => 'nullable|string|max:255',
            'social_media_links' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:100',
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

        // Construct the full address using available fields
        $address = implode(', ', array_filter([
            $request->street,
            $request->barangay,
            $request->zone,
            $request->state,
            $request->city,
            $request->country,
            $request->postal_code
        ]));

        // Handle file upload if present
        // $profile_picture_url = null;
        // if ($request->hasFile('profile_picture_url')) {
        //     $imagePath = $request->file('profile_picture_url')->store('profile_pictures', 'public');
        //     $profile_picture_url = '/storage/' . $imagePath; // Store public URL
        // }
        $profile = UserProfile::where('user_id', $user_id)->first();
        // Create the user profile with validated data and uploaded file
        if ($profile) {
            // Update the profile with the validated data
            $profile->update([
                'phone_number' => $validated['phone_number'] ?? null,
                'social_media_links' => $validated['social_media_links'] ?? null,
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
        } else {
            // If the profile doesn't exist, you can create a new one
            $profile = UserProfile::create([
                'user_id' => $user_id,
                'phone_number' => $validated['phone_number'] ?? null,
                'social_media_links' => $validated['social_media_links'] ?? null,
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
        }

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
            return $this->notFoundResponse(null, 'Profile not found');
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
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone_number' => 'required|string|max:255',
            'social_media_links' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
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
                ($request->city ? $request->city . ', ' : '') .
                ($request->municipality ? $request->municipality . ', ' : '') .
                ($request->country ? $request->country . ', ' : '') .
                ($request->postal_code ? $request->postal_code : '');

        // Update the profile with validated data
        $profile->update(array_merge($validated, ['address' => $address]));

        return $this->successResponse(['profile' => $profile], 'Profile updated successfully.');
    }


    
}

<?php

namespace App\Http\Controllers\rest;

use App\Models\Address;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;


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

    public function storePicture(Request $request, $user_id) {
        \Log::info($request->all());
    
        try {

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
    $user = auth()->user();
    Log::info('Authenticated user:', ['id' => $user->id]);  
    
    // Validate input data
    $validated = $request->validate([
        'phone_number' => 'nullable|string|max:255',
        'social_media_links' => 'nullable|string|max:255',
        'occupation' => 'nullable|string|max:255',
        'line_1' => 'nullable|string|max:100',
        'line_2' => 'nullable|string|max:100',
        'province' => 'nullable|string|max:255',
        'country' => 'nullable|string|max:255',
        'postal_code' => 'nullable|string|max:20',
        'driver_license_number' => 'nullable|string|max:255',
        'national_id' => 'nullable|string|max:255',
        'passport_number' => 'nullable|string|max:255',
        'social_security_number' => 'nullable|string|max:255',
    ]);

    // Ensure the profile belongs to the authenticated user
    $profile = UserProfile::updateOrCreate(
        ['user_id' => $user->id], // Use the authenticated user's ID
        [
            'phone_number' => $validated['phone_number'] ?? null,
            'social_media_links' => $validated['social_media_links'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'driver_license_number' => $validated['driver_license_number'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'passport_number' => $validated['passport_number'] ?? null,
            'social_security_number' => $validated['social_security_number'] ?? null,
        ]
    );

    $fullAddress = "{$validated['line_1']}, {$validated['line_2']}, {$validated['province']}, {$validated['country']}, {$validated['postal_code']}";
    $coordinates = Address::getCoordinates($fullAddress);

    $profile->address()->updateOrCreate(
        ['id' => $profile->id], // Ensure address is linked to the correct profile
        [
            'line_1' => $validated['line_1'] ?? null,
            'line_2' => $validated['line_2'] ?? null,
            'province' => $validated['province'] ?? null,
            'country' => $validated['country'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'latitude' => $coordinates['latitude'] ?? null,
            'longitude' => $coordinates['longitude'] ?? null,
        ]
    );

    return $this->successResponse(['profile' => $profile], 'User profile created successfully.', 201);
}

            

    /**
     * Display the specified user profile.
     */
    public function showByUserId($user_id) 
    {

        \Log::info('User ID received: ' . $user_id);
        // Retrieve the profile by user_id along with the related address
        $profile = UserProfile::with('address')->where('user_id', $user_id)->first();
    
        // If profile is not found, return a not found response
        if (!$profile) {
            return $this->notFoundResponse(null, 'Profile not found');
        }
    
        // Convert the `profile_picture_url` to a full URL
        if ($profile->profile_picture_url) {
            $profile->profile_picture_url = asset($profile->profile_picture_url);
        }
    
        // Format the address relation to include in the response
        $profile->address = $profile->address ? $profile->address : null;  // Ensure the address is included even if null
    
        // Return the success response with the profile and associated address
        return $this->successResponse(['profile' => $profile], 'User Profile Fetched Successfully');
    }
    

    /**
     * Update the specified user profile in storage.
     */
    public function update(Request $request, $user_id)
    {
        \Log::info($request->all());  // Log all incoming data
    
        // Validate the input data
        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:255',
            'social_media_links' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            // address part
            'line_1' => 'nullable|string|max:100',
            'line_2' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            // id parts
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
        ]);
    
        // Find the profile to update
        $profile = UserProfile::where('user_id', $user_id)->first();
    
        // If the profile doesn't exist, you can return an error or create it (based on your needs)
        if (!$profile) {
            return $this->errorResponse('User profile not found.', 404);
        }
    
        // Update the profile
        $profile->update([
            'phone_number' => $validated['phone_number'] ?? $profile->phone_number,
            'social_media_links' => $validated['social_media_links'] ?? $profile->social_media_links,
            'occupation' => $validated['occupation'] ?? $profile->occupation,
            'driver_license_number' => $validated['driver_license_number'] ?? $profile->driver_license_number,
            'national_id' => $validated['national_id'] ?? $profile->national_id,
            'passport_number' => $validated['passport_number'] ?? $profile->passport_number,
            'social_security_number' => $validated['social_security_number'] ?? $profile->social_security_number,
        ]);
    
        // Update the address (this assumes that the profile has an existing address)
        $profile->address()->update([  // Use `update()` to update the address
            'line_1' => $validated['line_1'] ?? $profile->address->line_1,
            'line_2' => $validated['line_2'] ?? $profile->address->line_2,
            'province' => $validated['province'] ?? $profile->address->province,
            'country' => $validated['country'] ?? $profile->address->country,
            'postal_code' => $validated['postal_code'] ?? $profile->address->postal_code,
        ]);
    
        return $this->successResponse(['profile' => $profile], 'User profile updated successfully.', 200);
    }
}

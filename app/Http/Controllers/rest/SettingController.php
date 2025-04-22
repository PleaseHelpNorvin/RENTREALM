<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;


class SettingController extends Controller
{
    //\
    public function show($user_id)
    {
        $user = User::with('setting')->find($user_id);

        if (!$user) {
            return $this->notFoundResponse(null, 'User not found');
        }

        if (!in_array($user->role, ['landlord', 'admin'])) {
            return $this->notFoundResponse(null, 'User not authorized');
        }
        $responseData = [
            'user' => $user, 
        ];
        return $this->successResponse($responseData, 'Settings retrieved successfully');
    }

    public function updateOrCreateSetting(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'default_min_lease' => 'nullable|integer',
            'default_reservation_fee' => 'nullable|numeric',
        ]);
    
        $user = User::find($validated['user_id']);
    
        if (!$user || !in_array($user->role, ['landlord', 'admin'])) {
            return $this->validationErrorResponse([
                'user_id' => ['User must be a landlord or admin.']
            ], 'Validation Error');
        }
    
        $setting = Setting::firstOrNew(['user_id' => $validated['user_id']]);
    
        $setting->default_min_lease = $validated['default_min_lease'] ?? $setting->default_min_lease;
        $setting->default_reservation_fee = $validated['default_reservation_fee'] ?? $setting->default_reservation_fee;
        $setting->save();
    
        return $this->successResponse(['setting'=> $setting], 'Settings updated or created successfully');
    }
//done update admin
    public function updateAdmin(Request $request, $user_id)
    {
        $user = User::find($user_id);
    
        if (!$user || !in_array($user->role, ['landlord', 'admin'])) {
            return $this->notFoundResponse(null, 'User not found or not authorized');
        }
    
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'admin_phone' => 'sometimes|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);
    
        // Only hash the password if it exists in the request
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']); // Make sure password isn't updated at all
        }
    
        $user->update($validated);
    
        return $this->successResponse($user, 'User updated successfully');
    }}

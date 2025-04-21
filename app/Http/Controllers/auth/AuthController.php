<?php

namespace App\Http\Controllers\auth;

use App\Models\User;
use App\Models\Handyman;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //

    public function login(Request $request)
    {
        \Log::info($request->all());

         // Validate login data
         $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation Error');
        }

        // Attempt to login the user
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            
            // Generate the token for the authenticated user
            $role = $user->role;
            $token = $user->createToken('RentRealmToken'. ucfirst($role))->plainTextToken;
            
            // Check user role and return appropriate response
            if ($user->isLandlord()) {
                return $this->successResponse([
                    'token' => $token,
                    'user' => $user,
                    // 'role' => 'landlord',
                ], 'Landlord Login successful');
            }   

            if ($user->isHandyman()) {
                return $this->successResponse([
                    'token' => $token,
                    'user' => $user,
                    // 'role' => 'handyman',
                ], 'Handy Man Login successful');
            }

            if ($user->isTenant()) {
                return $this->successResponse([
                    'token' => $token,
                    'user' => $user,
                    // 'role' => 'tenant',
                ], 'Tenant Login successful');
            }
            
            return $this->forbiddenResponse(null, 'User role is invalid');
        }

        // If authentication fails
        return $this->errorResponse(null, 'Unauthorized', 401);
    }

    public function create(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation Error');
        }

        // Create a new user
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'tenant',
            'steps' => '1',
        ]);

        Auth::login($newUser);

        $role = $newUser->role;
        $token = $newUser->createToken('RentRealmToken' . ucfirst($role))->plainTextToken;

        // Return the new user data with a success message
        return $this->successResponse([
            'token' => $token,
            'user' => $newUser,
        ], 'User created and logged in successfully');
    }

    public function createHandyman(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'handyman',
        ]); 

        $handyman = Handyman::create([
            'user_id' => $newUser->id,
            'status' => 'available', // Default status
        ]);
    

        return $this->successResponse([
            // 'token' => $token,
            'user' => $newUser,
        ], 'user Handyman created successfully');
    }


    public function updateHandyman(Request $request)
    {
        // dd($request->all());   
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,',
            'password' => 'sometimes|nullable|min:6',
            'status' => 'required|nullable|in:available,busy', // Or whatever statuses you use
            'user_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validatedData = $validator->validated();
        $id = $validatedData['user_id'];

        $user = User::findOrFail($id);
    
        // Update user details
        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
    
        $user->save();
    
        // Update handyman status
        $handyman = Handyman::where('user_id', $user->id)->first();
    
        if ($handyman && $request->has('status')) {
            $handyman->status = $request->status;
            $handyman->save();
        }
    
        return $this->successResponse([
            'user' => $user,
            'handyman' => $handyman,
        ], 'Handyman updated successfully');
    }
    
    public function logout(Request $request) 
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null,'Logged out successfully');
    }
}

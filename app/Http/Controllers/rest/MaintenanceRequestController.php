<?php

namespace App\Http\Controllers\rest;

use App\Models\Handyman;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MaintenanceRequestController extends Controller
{
    //

    public function index()
    {
        $maintenanceRequests = MaintenanceRequest::all()->map(function ($request) {
            $request->images = collect($request->images)->map(function ($imagePath) {
                return asset($imagePath); 
            })->toArray();
    
            return $request;
        });
        if ($maintenanceRequests->isEmpty()) {
            return $this->notFoundResponse([], 'No Maintenance Requests at the Moment Found');
        }
        return $this->successResponse(['maintenance_requests' => $maintenanceRequests], 'Maintenance Requests Fetched Successfully');
    }
   

    public function createMaintenanceRequest(Request $request)
{
    // Log the request files for debugging
    Log::info('Incoming request data:', $request->all());

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            Log::info('Processing image:', [
                'original_name' => $image->getClientOriginalName(),
                'extension' => $image->getClientOriginalExtension(),
                'path' => $image->getPathname(),
            ]);
        }
    }
    
    // Validate the request
    $validated = $request->validate([
        'tenant_id'   => 'required|exists:users,id',
        'room_id'     => 'required|exists:rooms,id',
        'handyman_id' => 'nullable|exists:handy_men,id',
        'title'       => 'required|string|max:255',
        'description' => 'required|string',
        'images' => 'nullable|array', // expecting array of images
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // validate each image
    ]);

    // Generate ticket code
    $date = now()->format('Ymd');
    $randomNumber = mt_rand(10000, 99999);
    $ticketCode = "MR-{$date}-{$randomNumber}";

    // Define storage path
    $storagePath = public_path('storage/maintenance_request_images');
    if (!file_exists($storagePath)) {
        mkdir($storagePath, 0777, true); // create directory if it doesn't exist
        Log::info('Storage path created:', ['path' => $storagePath]);
    }

    // Initialize image paths array
    $imagePaths = [];
    if ($request->hasFile('images')) {
        $counter = 1;
        foreach ($request->file('images') as $image) {
            try {
                // Validate and handle each image
                $extension = $image->getClientOriginalExtension();
                $imageName = "{$ticketCode}_{$counter}.{$extension}";

                // Move the image to the storage path
                $image->move($storagePath, $imageName);
                $imagePaths[] = 'storage/maintenance_request_images/' . $imageName; // save relative path

                Log::info('Image successfully uploaded:', [
                    'image_name' => $imageName,
                    'path' => 'storage/maintenance_request_images/' . $imageName
                ]);

                $counter++; // increment counter for each image
            } catch (\Exception $e) {
                Log::error('Error uploading image', [
                    'image_error' => $e->getMessage(),
                    'image_index' => $counter
                ]);
            }
        }
    }

    // Create maintenance request record
    $maintenanceRequest = MaintenanceRequest::create([
        'tenant_id'   => $validated['tenant_id'],
        'room_id'     => $validated['room_id'],
        'handyman_id' => $validated['handyman_id'] ?? null,
        'title'       => $validated['title'],
        'description' => $validated['description'],
        'ticket_code' => $ticketCode,
        'images'      => $imagePaths,
        'requested_at'=> now(),
    ]);

    // Return successful response
    Log::info('Maintenance request created:', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'tenant_id' => $maintenanceRequest->tenant_id,
        'ticket_code' => $maintenanceRequest->ticket_code
    ]);

    return $this->createdResponse(['maintenance_requests' => [$maintenanceRequest]], 'Maintenance request created successfully');
}


    
}

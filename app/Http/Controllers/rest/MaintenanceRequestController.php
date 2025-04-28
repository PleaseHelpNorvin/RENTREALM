<?php

namespace App\Http\Controllers\rest;

use App\Models\Handyman;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;


class MaintenanceRequestController extends Controller
{
    //

    public function index()
    {
        $maintenanceRequests = MaintenanceRequest::with(
            'tenant.userProfile.user',
            'room.property',
            'handyman.user',
            'assignedBy',
            'approvedBy'
        )->get()->each(function ($request) {
            $request->images = collect($request->images)->map(function ($imagePath) {
                return asset($imagePath); 
            })->toArray();
        });

        $handymen = Handyman::with('user', 'maintenanceRequests')->get();
        
        if ($maintenanceRequests->isEmpty()) {
            return $this->notFoundResponse([], 'No Maintenance Requests at the Moment Found');
        }
        return $this->successResponse(['maintenance_requests' => $maintenanceRequests, 'handymens' => $handymen], 'Maintenance Requests Fetched Successfully');
    }

    public function indexByTenantId($tenantId)
    {
        $maintenanceRequests = MaintenanceRequest::where('tenant_id', $tenantId)->get()->map(function ($request) {
            $request->images = collect($request->images)->map(function ($imagePath) {
                return asset($imagePath);
            })->toArray();

            return $request;
        });

        if ($maintenanceRequests->isEmpty()) {
            return $this->notFoundResponse([], 'No Maintenance Requests Found for this Profile');
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
            'tenant_id'   => 'required|exists:tenants,id',
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

        $user = $request->user();
        $maintenanceRequestNotif = $maintenanceRequest->notifications()->create([
            'user_id' => $user->id,
            'title' => 'Maintenance Request Submitted - ' . $ticketCode,
            'message' => "Your maintenance request titled '{$maintenanceRequest->title}' has been submitted successfully.\n\n"
                . "🛠 Ticket Code: {$ticketCode}\n"
                . "📅 Requested At: " . now()->format('F j, Y h:i A') . "\n"
                . "🏠 Room ID: {$maintenanceRequest->room_id}\n"
                . "📝 Description: " . Str::limit($maintenanceRequest->description, 200) . "\n\n"
                . "We will review your request and assign a handyman shortly.",
        ]);
        
        Log::info('Maintenance request created notification', [
            'user_id' => $maintenanceRequestNotif->user_id,
            'title' => $maintenanceRequestNotif->title,
            'message' => $maintenanceRequestNotif->message,
            'is_read' => $maintenanceRequestNotif->is_read,
        ]);
        // Return successful response
        Log::info('Maintenance request created:', [
            'maintenance_request_id' => $maintenanceRequest->id,
            'tenant_id' => $maintenanceRequest->tenant_id,
            'ticket_code' => $maintenanceRequest->ticket_code
        ]);

        return $this->createdResponse(['maintenance_requests' => [$maintenanceRequest]], 'Maintenance request created successfully');
    }

    public function show($maintenanceRequestId)
    {
        // Fetch the maintenance request by ID
        $maintenanceRequest = MaintenanceRequest::find($maintenanceRequestId);
    
        // If the maintenance request is not found, return a 404 response
        if (!$maintenanceRequest) {
            return $this->notFoundResponse([], 'Maintenance Request Not Found');
        }
    
        // Decode the images JSON if it's stored as a string
        if (is_string($maintenanceRequest->images)) {
            $maintenanceRequest->images = json_decode($maintenanceRequest->images, true);
        }
    
        // Ensure images is an array before mapping URLs
        if (is_array($maintenanceRequest->images)) {
            $maintenanceRequest->images = collect($maintenanceRequest->images)->map(function ($imagePath) {
                return asset($imagePath); // Generate full URL for images
            })->toArray();
        }
    
        // Return the success response with the maintenance request
        return $this->successResponse(['maintenance_requests' => $maintenanceRequest], 'Maintenance Request Fetched Successfully');
    }

    public function update(Request $request, $maintenanceRequestId)
    {
        Log::info('Request Data Before Validation:', $request->all());

        // Find the existing maintenance request
        $maintenanceRequest = MaintenanceRequest::find($maintenanceRequestId);

        if (!$maintenanceRequest) {
            return $this->notFoundResponse(null, 'Maintenance request not found');
        }

        // Validate incoming data
        $validator = \Validator::make($request->all(), [
            'tenant_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'handyman_id' => 'nullable|integer',
            'assigned_by' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'status' => 'nullable|string|max:255',
            'requested_at' => 'nullable|date',
            'assisted_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            Log::info('Validation Errors:', $validator->errors()->toArray());
            return $this->validationErrorResponse($validator->errors());
        }

        // Get the validated data
        $validatedData = $validator->validated();
        Log::info('Validated Data:', $validatedData);

        // Update the fields directly
        $maintenanceRequest->fill($validatedData);

        // Handle image uploads separately
        if ($request->hasFile('images')) {
            $imagePaths = [];
            
            // Generate ticket code (use the same format as in the create method)
            $date = now()->format('Ymd');
            $randomNumber = mt_rand(10000, 99999);
            $ticketCode = "MR-{$date}-{$randomNumber}";

            $storagePath = public_path('storage/maintenance_request_images');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0777, true); // create directory if it doesn't exist
                Log::info('Storage path created:', ['path' => $storagePath]);
            }

            $counter = 1;
            foreach ($request->file('images') as $image) {
                try {
                    // Generate the image name based on ticket code and counter
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

            // Encode the image paths as JSON
            $maintenanceRequest->images = json_encode($imagePaths); // Store paths as JSON array
        }

        // Save the updated maintenance request
        $maintenanceRequest->save();

        // Return success response
        return $this->successResponse(['maintenance_requests' => [$maintenanceRequest]], 'Maintenance request updated successfully');
    }
    
    
    public function updateStatusToCancel(Request $request, $maintenance_request_id)
    {
        // Validate the incoming request
        $request->validate([
            'status' => 'required|string|in:pending,completed,cancelled', // Define allowed status values
        ]);

        // Find the maintenance request by its ID
        $maintenanceRequest = MaintenanceRequest::find($maintenance_request_id);

        if (!$maintenanceRequest) {
            // Return a 404 if the maintenance request isn't found
            return $this->notFoundResponse(null, 'Maintenance request not found');
        }

        // Update the status
        $maintenanceRequest->status = $request->status;
        $maintenanceRequest->save();

         // Decode the images JSON if it's stored as a string
         if (is_string($maintenanceRequest->images)) {
            $maintenanceRequest->images = json_decode($maintenanceRequest->images, true);
        }
    
        // Ensure images is an array before mapping URLs
        if (is_array($maintenanceRequest->images)) {
            $maintenanceRequest->images = collect($maintenanceRequest->images)->map(function ($imagePath) {
                return asset($imagePath); // Generate full URL for images
            })->toArray();
        }
    

        // Return a success response
        // return response()->json(['message' => 'Maintenance request status updated successfully', 'data' => $maintenanceRequest], 200);
        return $this->successResponse(['maintenance_requests' => $maintenanceRequest], 'Maintenance request updated successfully');

    }

    public function getMaintenanceRequestByHandymanId($handymanId) {
        $maintenanceRequestByHandymanIdList = MaintenanceRequest::with('tenant.userProfile.user', 'room', 'handyman', 'assignedBy')
            ->where('handyman_id', $handymanId)
            ->get(); 
    
        if ($maintenanceRequestByHandymanIdList->isEmpty()) {
            return $this->notFoundResponse([], 'No maintenance request for this handyman found');
        }
    
        $maintenanceRequestByHandymanIdList->each(function ($maintenanceRequest) {
            $maintenanceRequest->images = collect(json_decode($maintenanceRequest->images, true))
                ->map(function ($imagePath) {
                    return strpos($imagePath, 'http') === 0 ? $imagePath : url($imagePath);
                });
    
            $maintenanceRequest->room->room_picture_url = collect(json_decode($maintenanceRequest->room->room_picture_url, true))
                ->map(function ($imagePath) {
                    return strpos($imagePath, 'http') === 0 ? $imagePath : url('storage/' . $imagePath);
                });
        });
    
        return $this->successResponse(
            ['maintenance_requests' => $maintenanceRequestByHandymanIdList],
            'Maintenance requests by handymanId fetched successfully'
        );
    }

    public function getMaintenanceRequestList() {
        $maintenanceRequest = MaintenanceRequest::with('tenant.userProfile.user', 'room.property.address', 'handyman', 'assignedBy', 'approvedBy')
            ->get();
    
        if ($maintenanceRequest->isEmpty()) {
            return $this->notFoundResponse([], 'No pending maintenance requests found');
        }
    
        $maintenanceRequest->each(function ($maintenanceRequest) {
            if (is_string($maintenanceRequest->images)) {
                $maintenanceRequest->images = json_decode($maintenanceRequest->images, true);
            }
    
            if (is_array($maintenanceRequest->images)) {
                $maintenanceRequest->images = collect($maintenanceRequest->images)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url($imagePath);
                    })->toArray();
            }
    
            if (is_string($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = json_decode($maintenanceRequest->room->room_picture_url, true);
            }
            
            if (is_array($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = collect($maintenanceRequest->room->room_picture_url)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url('storage/' . $imagePath);
                    })->toArray();
            }

            $maintenanceRequest->approvedBy = $maintenanceRequest->approvedBy ?? null;

        });
    
        return $this->successResponse(
            ['maintenance_requests' => $maintenanceRequest],
            'Maintenance requests fetched successfully'
        );
    }
    
    public function patchMaintenanceRequestToRequested(Request $request, $maintenanceRequestId, $handymanId)
    {

        $maintenanceRequest = MaintenanceRequest::with([
            'tenant.userProfile.user',
            'room',
            'handyman',
            'assignedBy'
        ])->findOrFail($maintenanceRequestId);
        
        
        $maintenanceRequest->status = 'requested';
        $maintenanceRequest->handyman_id = $handymanId;
        $maintenanceRequest->requested_at = now();
        $maintenanceRequest->save();

        return $this->successResponse(
            ['maintenance_requests' => [$maintenanceRequest]],
            'Maintenance request status updated successfully.'
        );
    }


    public function getMaintenanceRequestListRequested() {
        $maintenanceRequest = MaintenanceRequest::with('tenant.userProfile.user', 'room', 'handyman', 'assignedBy', 'approvedBy')
            ->where('status', 'requested')
            ->get();
    
        if ($maintenanceRequest->isEmpty()) {
            return $this->notFoundResponse([], 'No pending maintenance requests found');
        }
    
        $maintenanceRequest->each(function ($maintenanceRequest) {
            if (is_string($maintenanceRequest->images)) {
                $maintenanceRequest->images = json_decode($maintenanceRequest->images, true);
            }
    
            if (is_array($maintenanceRequest->images)) {
                $maintenanceRequest->images = collect($maintenanceRequest->images)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url($imagePath);
                    })->toArray();
            }
    
            if (is_string($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = json_decode($maintenanceRequest->room->room_picture_url, true);
            }
            
            if (is_array($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = collect($maintenanceRequest->room->room_picture_url)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url('storage/' . $imagePath);
                    })->toArray();
            }
            $maintenanceRequest->approvedBy = $maintenanceRequest->approvedBy ?? null;

        });
    
        return $this->successResponse(
            ['maintenance_requests' => $maintenanceRequest],
            'Maintenance requests with a status of requested fetched successfully'
        );
    }
    
    public function getMaintenanceRequestListRequestedByHandymanId($handymanId) {
        $maintenanceRequest = MaintenanceRequest::with('tenant.userProfile.user', 'room', 'handyman', 'assignedBy', 'approvedBy')
            ->where('status', 'requested')
            ->where('handyman_id', $handymanId)
            ->get();
    
        if ($maintenanceRequest->isEmpty()) {
            return $this->notFoundResponse([], 'No pending maintenance requests found');
        }
    
        $maintenanceRequest->each(function ($maintenanceRequest) {
            if (is_string($maintenanceRequest->images)) {
                $maintenanceRequest->images = json_decode($maintenanceRequest->images, true);
            }
    
            if (is_array($maintenanceRequest->images)) {
                $maintenanceRequest->images = collect($maintenanceRequest->images)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url($imagePath);
                    })->toArray();
            }
    
            if (is_string($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = json_decode($maintenanceRequest->room->room_picture_url, true);
            }
            
            if (is_array($maintenanceRequest->room->room_picture_url)) {
                $maintenanceRequest->room->room_picture_url = collect($maintenanceRequest->room->room_picture_url)
                    ->map(function ($imagePath) {
                        return strpos($imagePath, 'http') === 0 ? $imagePath : url('storage/' . $imagePath);
                    })->toArray();
            }
            $maintenanceRequest->approvedBy = $maintenanceRequest->approvedBy ?? null;
 
        });
    
        return $this->successResponse(
            ['maintenance_requests' => $maintenanceRequest],
            'Maintenance requests with a status of requested fetched successfully'
        );
    }

    public function patchMaintenanceRequestToAssigned(Request $request)
    {
        $requestId = $request->input('request_id');
        $handymanId = $request->input('handyman_id');
        $adminId = $request->input('admin_id');

        $maintenanceRequest = MaintenanceRequest::with([
            'tenant.userProfile.user',
            'room',
            'handyman',
            'assignedBy'
        ])->findOrFail($requestId);

        $maintenanceRequest->status = 'Assigned';
        $maintenanceRequest->handyman_id = $handymanId;
        $maintenanceRequest->assigned_by = $adminId;
        $maintenanceRequest->assigned_at = now();
        $maintenanceRequest->save();

        return $this->successResponse(
            ['maintenance_requests' => [$maintenanceRequest]],
            'Maintenance request status updated to assigned successfully.'
        );
    }


    
    public function patchMaintenanceRequestToInProgress(Request $request, $maintenanceRequestId)
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'tenant.userProfile.user',
            'room',
            'handyman',
            'assignedBy'
        ])->findOrFail($maintenanceRequestId);

        $handymanId = $maintenanceRequest->handyman_id;

        // Check if handyman has other in_progress request
        $ongoingRequest = MaintenanceRequest::where('handyman_id', $handymanId)
            ->where('status', 'in_progress')
            ->where('id', '!=', $maintenanceRequestId)
            ->first();

        if ($ongoingRequest) {
            return $this->errorResponse(
                [] ,'Handyman is still busy with another maintenance request.',
                400
            );
        }

        // Safe to proceed
        $maintenanceRequest->status = 'in_progress';
        $maintenanceRequest->assisted_at = now();
        $maintenanceRequest->save();

        // Update handyman status to busy
        $handyman = Handyman::find($handymanId);
        $handyman->status = 'busy';
        $handyman->save();

        return $this->successResponse(
            ['maintenance_requests' => [$maintenanceRequest]],
            'Maintenance request status updated to in progress successfully.'
        );
    }

    public function patchMaintenanceRequestToForApprove(Request $request, $maintenanceRequestId)
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'tenant.userProfile.user',
            'room',
            'handyman',
            'assignedBy'
        ])->findOrFail($maintenanceRequestId);

        $handymanId = $maintenanceRequest->handyman_id;

        // Check if handyman has other in_progress request
        $ongoingRequest = MaintenanceRequest::where('handyman_id', $handymanId)
            ->where('status', 'in_progress')
            ->where('id', '!=', $maintenanceRequestId)
            ->first();

        if ($ongoingRequest) {
            return $this->errorResponse(
                [] ,'Handyman is still busy with another maintenance request.',
                400
            );
        }

        // Safe to proceed
        $maintenanceRequest->status = 'forApprove';
        $maintenanceRequest->completed_at = now();
        $maintenanceRequest->save();

        // Update handyman status to busy
        $handyman = Handyman::find($handymanId);
        $handyman->status = 'available';
        $handyman->save();

        return $this->successResponse(
            ['maintenance_requests' => [$maintenanceRequest]],
            'Maintenance request status updated to for approve successfully.'
        );
    }

    public function patchMaintenanceRequestToComplete(Request $request)
{
    $maintenanceRequestId = $request->input('request_id');
    $adminId = $request->input('admin_id');

    $maintenanceRequest = MaintenanceRequest::with([
        'tenant.userProfile.user',
        'room',
        'handyman',
        'assignedBy'
    ])->findOrFail($maintenanceRequestId);

    $handymanId = $maintenanceRequest->handyman_id;

    // Check if handyman has other in_progress request
    $ongoingRequest = MaintenanceRequest::where('handyman_id', $handymanId)
        ->where('status', 'forApprove')
        ->where('id', '!=', $maintenanceRequestId)
        ->first();

    if ($ongoingRequest) {
        return $this->errorResponse(
            [], 'Handyman is still busy with another maintenance request.',
            400
        );
    }

    // Safe to proceed
    $maintenanceRequest->status = 'completed';
    $maintenanceRequest->approved_by = $adminId;
    $maintenanceRequest->approved_at = now();
    $maintenanceRequest->save();

    return $this->successResponse(
        ['maintenance_requests' => [$maintenanceRequest]],
        'Maintenance request status updated to completed successfully.'
    );
}

    
}

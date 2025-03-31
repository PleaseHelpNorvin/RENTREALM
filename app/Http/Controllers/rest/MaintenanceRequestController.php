<?php

namespace App\Http\Controllers\rest;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use App\Http\Controllers\Controller;

class MaintenanceRequestController extends Controller
{
    //

    public function index()
    {
        $maintenanceRequests = MaintenanceRequest::all();

        if ($maintenanceRequests->isEmpty()) {
            return $this->notFoundResponse([], 'No Maintenance Requests at the Moment Found');
        }

        return $this->successResponse(['maintenance_requests' => $maintenanceRequests], 'Maintenance Requests Fetched Successfully');
    }

    public function createMaintenanceRequest(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'tenant_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'handyman_id' => 'nullable|exists:handy_men,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'nullable|json',
            // 'status' => 'nullable|in:pending,in_progress,completed,cancelled'
        ]);
    
        // Create the maintenance request
        $maintenanceRequest = MaintenanceRequest::create([
            'tenant_id' => $validated['tenant_id'],
            'room_id' => $validated['room_id'],
            'handyman_id' => $validated['handyman_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'images' => $validated['images'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'requested_at' => now(),
        ]);
    
        // If a handyman is assigned, update the status to busy
        if ($maintenanceRequest->handyman_id) {
            $handyman = Handyman::find($maintenanceRequest->handyman_id);
            if ($handyman) {
                $handyman->update(['status' => 'busy']);
            }
        }
    
        return response()->json([
            'message' => 'Maintenance request created successfully',
            'data' => $maintenanceRequest
        ], 201);
    }
    
}

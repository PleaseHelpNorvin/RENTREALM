<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
 

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::all();

        if($tenants -> isEmpty())
        {
            return $this->notFoundResponse(null, 'No Tenant Found');
        }

        return $this->successResponse(['tenant' => $tenants], 'Tenants Fetched Successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id', 
            'room_id' => 'required|exists:rooms,id',
            'rental_agreement_id' => 'required|exists:rental_agreements,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'rent_price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,due,overdue',
            'status' => 'required|in:active,inactive,evicted,moved_out',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:255',
            'has_pets' => 'required|boolean',
            'wifi_enabled' => 'required|boolean',
            'has_laundry_access' => 'required|boolean',
            'has_private_fridge' => 'required|boolean',
            'has_tv' => 'required|boolean',
        ]);
        
        $tenant = Tenant::create($validated);

        return $this->successResponse(['tenant' => $tenant], 'Tenant Added Successfully');
    }

    public function show($id, Request $request)
    {
        
    }

    public function update($id, Request $request)
    {

    }

    public function destroy($id, Request $request)
    {
        
    }
}

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

    public function show($tenant_id, Request $request)
    {
        $tenant = Tenant::findOrFail($tenant_id);

        if(!$tenant)
        {
            return $this->notFoundResponse(null,"Tenant $tenant_id notFound");
        }

        return $this->successResponse(['tenant' => $tenant], 'Tenant Found Successfully');
    }

    public function showByProfileId($profile_id)
    {
        $tenantByProfileId = Tenant::where('profile_id', $profile_id)->first();

        if (!$tenantByProfileId) {
            return $this->notFoundResponse(null, "Tenant $profile_id is not found");
        }

        return $this->successResponse(['tenant' => $tenantByProfileId], "Tenant $profile_id Found Successfully");
    }

    public function update($tenant_id, Request $request)
    {

    }

    public function destroy($tenant_id, Request $request)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with tenant_id $tenant_id not found");
        }

        $tenant->delete();
        return $this->successResponse(null, "Tenant with tenant_id $tenant_id deleted successfully ");
    }


    public function destroyByProfileId($profile_id)
    {
        $tenantByProfileId = Tenant::where('profile_id', $profile_id)->first();

        if (!$tenantByProfileId) {
            return $this->notFoundResponse(null, "Tenant with profile_id $profile_id not found");
        }

        $tenantByProfileId->delete(); // Delete the tenant

        return $this->successResponse(null, "Tenant with profile_id $profile_id deleted successfully");
    }
}

<?php

namespace App\Http\Controllers\rest;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('rentalAgreement')->get();

        if ($tenants->isEmpty()) {
            return $this->notFoundResponse(null, 'No tenants found.');
        }

        return $this->successResponse(['tenants' => $tenants], 'Tenants fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'room_id' => 'required|exists:rooms,id',
            'rental_agreement_id' => 'required|exists:rental_agreements,id',
            'payment_status' => 'required|in:paid,due,overdue',
            'status' => 'required|in:active,inactive,evicted,moved_out',
        ]);

        $tenant = Tenant::create($validated);

        return $this->successResponse(['tenant' => $tenant], 'Tenant added successfully.');
    }

    public function show($tenant_id)
    {
        $tenant = Tenant::with('rentalAgreement')->find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with ID $tenant_id not found.");
        }

        return $this->successResponse(['tenant' => $tenant], 'Tenant fetched successfully.');
    }

    public function showByProfileId($profile_id)
    {
        $tenant = Tenant::with(['rentalAgreement', 'userProfile'])
            ->where('profile_id', $profile_id)
            ->first();

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with profile ID $profile_id not found.");
        }

        Artisan::call('invoices:generate');


        return $this->successResponse(['tenant' => $tenant], 'Tenant fetched successfully.');
    }


    public function update(Request $request, $tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with ID $tenant_id not found.");
        }

        $validated = $request->validate([
            'payment_status' => 'sometimes|in:paid,due,overdue',
            'status' => 'sometimes|in:active,inactive,evicted,moved_out',
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_phone' => 'sometimes|string|max:255',
        ]);

        $tenant->update($validated);

        return $this->successResponse(['tenant' => $tenant], 'Tenant updated successfully.');
    }

    public function destroy($tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with ID $tenant_id not found.");
        }

        $tenant->delete();
        return $this->successResponse(null, "Tenant with ID $tenant_id deleted successfully.");
    }

    public function destroyByProfileId($profile_id)
    {
        $tenant = Tenant::where('profile_id', $profile_id)->first();

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with profile ID $profile_id not found.");
        }

        $tenant->delete();
        return $this->successResponse(null, "Tenant with profile ID $profile_id deleted successfully.");
    }

    public function startEvacuation($tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with ID $tenant_id not found.");
        }

        $tenant->update([
            'status' => 'evicted',
            'evacuation_date' => now(),
        ]);

        return $this->successResponse(['tenant' => $tenant], 'Tenant evacuation started.');
    }

    public function completeMoveOut($tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with ID $tenant_id not found.");
        }

        $tenant->update([
            'status' => 'moved_out',
            'move_out_date' => now(),
        ]);

        return $this->successResponse(['tenant' => $tenant], 'Tenant move-out completed.');
    }
}

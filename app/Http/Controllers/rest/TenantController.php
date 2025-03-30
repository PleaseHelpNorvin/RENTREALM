<?php

namespace App\Http\Controllers\rest;

use Carbon\Carbon;
use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
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

        // Fetch latest billing for the tenant
        $billing = Billing::where('profile_id', $profile_id)
            ->orderBy('billing_month', 'desc')
            ->first();

        // Calculate the next billing month (if a billing record exists)
        $nextBillingMonth = $billing
            ? Carbon::parse($billing->billing_month)->addMonth()->format('Y-m-d')
            : null;

        // Hardcoded maintenance requests
        $maintenanceRequests = [
            [
                'id' => 1,
                'request_type' => 'Plumbing',
                'description' => 'Leaking faucet in kitchen',
                'status' => 'pending',
                'requested_at' => '2025-03-20 14:30:00'
            ],
            [
                'id' => 2,
                'request_type' => 'Electrical1',
                'description' => 'Power outlet not working in bedroom1',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
            [
                'id' => 3,
                'request_type' => 'Electrical2',
                'description' => 'Power outlet not working in bedroom2',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
            [
                'id' => 4,
                'request_type' => 'Electrical3',
                'description' => 'Power outlet not working in bedroom3',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
            [
                'id' => 5,
                'request_type' => 'Electrical4',
                'description' => 'Power outlet not working in bedroom4',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
            [
                'id' => 6,
                'request_type' => 'Electrical5',
                'description' => 'Power outlet not working in bedroom6',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
            [
                'id' => 7,
                'request_type' => 'Electrical6',
                'description' => 'Power outlet not working in bedroom7',
                'status' => 'in_progress',
                'requested_at' => '2025-03-22 10:15:00'
            ],
        ];

        return $this->successResponse([
            'tenant' => $tenant,
            'latest_billing' => $billing ? [
                'billing_month' => $billing->billing_month,
                'status' => $billing->status,
                'total_amount' => $billing->total_amount,
                'amount_paid' => $billing->amount_paid,
                'remaining_balance' => $billing->remaining_balance
            ] : null,
            'next_billing_month' => $nextBillingMonth,
            'maintenance_requests' => $maintenanceRequests, // Add hardcoded data here
        ], 'Tenant fetched successfully.');
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

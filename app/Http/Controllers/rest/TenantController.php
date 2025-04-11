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

        $user = $tenant->userProfile->user;
        $user -> steps = '6';
        $user->save();

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
    //getTenantViaRoomId is used in landlord
    public function getTenantViaRoomId($room_id)
    {
        $tenants = Tenant::whereHas('rentalAgreement.reservation.room', function($query) use ($room_id) {
            $query->where('id', $room_id);
        })
        ->with('rentalAgreement.reservation.room', 'userProfile.user', 'userProfile.address')
        ->get();

        if ($tenants->isEmpty()) {
            return $this->notFoundResponse([], 'Tenant not found');
        }

        // Manually modify the signature_png_string to full URL
        $tenants->map(function($tenant) {
            if ($tenant->rentalAgreement && $tenant->rentalAgreement->signature_png_string) {
                $tenant->rentalAgreement->signature_png_string = asset('storage/' . $tenant->rentalAgreement->signature_png_string);
            }

            if ($tenant->rentalAgreement && $tenant->rentalAgreement->reservation && $tenant->rentalAgreement->reservation->reservation_payment_proof_url) {
                $proofUrls = json_decode($tenant->rentalAgreement->reservation->reservation_payment_proof_url, true); // decode JSON string
                if (is_array($proofUrls)) {
                    // Convert each URL to a full URL
                    $proofUrls = array_map(function($url) {
                        return asset('storage/' . $url);
                    }, $proofUrls);
                    // Update the field with full URLs
                    $tenant->rentalAgreement->reservation->reservation_payment_proof_url = $proofUrls;
                }
            }

            if ($tenant->rentalAgreement && $tenant->rentalAgreement->reservation && $tenant->rentalAgreement->reservation->room && $tenant->rentalAgreement->reservation->room->room_picture_url) {
                $roomPictureUrls = json_decode($tenant->rentalAgreement->reservation->room->room_picture_url, true); // decode JSON string
                if (is_array($roomPictureUrls)) {
                    // Convert each room picture URL to a full URL
                    $roomPictureUrls = array_map(function($url) {
                        return asset($url);
                    }, $roomPictureUrls);
                    // Update the field with full URLs
                    $tenant->rentalAgreement->reservation->room->room_picture_url = $roomPictureUrls;
                }
            }

            if ($tenant->userProfile && $tenant->userProfile->profile_picture_url) {
                $tenant->userProfile->profile_picture_url = asset( $tenant->userProfile->profile_picture_url);
            }
            return $tenant;
        });

        
        return $this->successResponse(['tenant' => $tenants], 'Tenant retrieved successfully');
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

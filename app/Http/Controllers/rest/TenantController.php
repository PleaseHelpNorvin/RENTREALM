<?php

namespace App\Http\Controllers\rest;

use Carbon\Carbon;
use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Reservation;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('rentalAgreements')->get();

        if ($tenants->isEmpty()) {
            return $this->notFoundResponse(null, 'No tenants found.');
        }

        return $this->successResponse(['tenants' => $tenants], 'Tenants fetched successfully.');
    }

    // public function store(Request $request) not used
    // {
    //     $validated = $request->validate([
    //         'profile_id' => 'required|exists:user_profiles,id',
    //         'room_id' => 'required|exists:rooms,id',
    //         'rental_agreement_id' => 'required|exists:rental_agreements,id',
    //         'payment_status' => 'required|in:paid,due,overdue',
    //         'status' => 'required|in:active,inactive,evicted,moved_out',
    //     ]);

    //     $tenant = Tenant::create($validated);

    //     $user = $tenant->userProfile->user;
    //     $user -> steps = '6';
    //     $user->save();

    //     return $this->successResponse(['tenant' => $tenant], 'Tenant added successfully.');
    // }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'room_id' => 'required|exists:rooms,id',
            'rental_agreement_id' => 'required|exists:rental_agreements,id',
            'payment_status' => 'required|in:paid,due,overdue',
            'status' => 'required|in:active,inactive,evicted,moved_out',
        ]);
    
        Log::info('Validated tenant data:', $validated);
    
        $tenant = Tenant::create($validated);
    
        Log::info('Created tenant:', ['tenant_id' => $tenant->id]);
    
        try {
            $tenant->rentalAgreements()->attach($validated['rental_agreement_id']);
            Log::info('Attached rental agreement to tenant.', [
                'tenant_id' => $tenant->id,
                'rental_agreement_id' => $validated['rental_agreement_id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach rental agreement:', [
                'error' => $e->getMessage()
            ]);
        }
    
        if ($tenant->userProfile && $tenant->userProfile->user) {
            $user = $tenant->userProfile->user;
            $user->steps = '6';
            $user->save();
    
            Log::info('Updated user steps to 6', ['user_id' => $user->id]);
        } else {
            Log::warning('User profile or user not found for tenant:', ['tenant_id' => $tenant->id]);
        }
    
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
        $tenants = Tenant::with(['userProfile.user', 'rentalAgreement.reservation.room'])
        ->whereHas('rentalAgreement.reservation', function ($query) use ($room_id) {
            $query->where('room_id', $room_id);
        })->get();


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
                $roomPictureUrls = json_decode($tenant->rentalAgreement->reservation->room->room_picture_url, true);
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


    public function adminShowTenantByProfileId($profile_id)
    {
        $tenant = Tenant::with([ 'userProfile.user','userProfile.address'])
            ->where('profile_id', $profile_id)
            ->first();

        if (!$tenant) {
            return $this->notFoundResponse(null, "Tenant with profile ID $profile_id not found.");
        }

        if ($tenant->user_profile && $tenant->user_profile->profile_picture_url) {
            // Generate the full URL for the profile picture
            $tenant->user_profile->profile_picture_url = url('storage/profile_pictures/' . $tenant->user_profile->profile_picture_url);
        }
    
    
        // Fetch latest billing for the tenant
        $billing = Billing::where('profile_id', $profile_id)
        ->orderBy('billing_month', 'desc')
        ->first();

        $rentalAgreement = RentalAgreement::with(['reservation.room.property'])
        ->whereHas('reservation', function ($query) use ($profile_id) {
            $query->where('profile_id', $profile_id);
        })
        ->get()
        ->map(function ($rental) use ($billing) {
            // Fetch the latest billing for each rental agreement
            $latestBilling = Billing::where('billable_type', RentalAgreement::class)
                ->where('billable_id', $rental->id)
                ->orderBy('billing_month', 'desc')
                ->first();

            // Calculate the next billing month based on the latest billing
            $nextBillingMonth = $latestBilling
                ? Carbon::parse($latestBilling->billing_month)->addMonth()->format('Y-m-d')
                : null;

            // Add the latest billing and next billing information to the rental agreement
            $rental->latest_billing = $latestBilling ? [
                'billing_month' => $latestBilling->billing_month,
                'status' => $latestBilling->status,
                'total_amount' => $latestBilling->total_amount,
                'amount_paid' => $latestBilling->amount_paid,
                'remaining_balance' => $latestBilling->remaining_balance
            ] : null;

            $rental->next_billing_month = $nextBillingMonth;

            return $rental;
        });
        
                // Calculate the next billing month (if a billing record exists)
        $nextBillingMonth = $billing
            ? Carbon::parse($billing->billing_month)->addMonth()->format('Y-m-d')
            : null;

        // $paymentHistory = Billing::with('payments')->where('profile_id', $profile_id)
        //     ->orderBy('billing_month', 'desc')
        //     ->get();
        $paymentHistory = Billing::with(['payments', 'rentalAgreement'])
        ->where('profile_id', $profile_id)
        ->where('billable_type', \App\Models\RentalAgreement::class)
        ->orderBy('billing_month', 'desc')
        ->get();

        $notification = Notification::where('user_id', $tenant->userProfile->user->id)->get();

        return $this->successResponse([
            'tenant' => $tenant,
            'payment_history' => $paymentHistory,
            'rental_agreements' => $rentalAgreement, // Include rental agreements
            'notifications' => $notification,
        ], 'Tenant fetched successfully.');
    }
}

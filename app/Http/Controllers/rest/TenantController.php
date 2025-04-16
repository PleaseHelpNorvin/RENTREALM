<?php

namespace App\Http\Controllers\rest;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Reservation;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Builder;

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
        $tenant = Tenant::with([
                'userProfile.user',
                'rentalAgreements' => function($query) use ($room_id) {
                    $query->whereHas('reservation', function($query) use ($room_id) {
                        $query->where('room_id', $room_id); 
                    });
                },
                'rentalAgreements.reservation.room', 
                // 'rentalAgreements.reservation.userProfile',
                'rentalAgreements.reservation.approvedBy'
            ])
            ->whereHas('rentalAgreements.reservation', function($query) use ($room_id) {
                $query->where('room_id', $room_id); 
            })
            ->first();

        if (!$tenant) {
            return $this->errorResponse('Tenant not found for the given room.', 404);
        }

        // Convert profile picture URL using asset()
        if ($tenant->userProfile && $tenant->userProfile->profile_picture_url) {
            $tenant->userProfile->profile_picture_url = asset(ltrim($tenant->userProfile->profile_picture_url, '/'));
        }

        // Go through each rental agreement and update room picture and payment proof URLs
        if ($tenant->rentalAgreements && $tenant->rentalAgreements->isNotEmpty()) {
            foreach ($tenant->rentalAgreements as $agreement) {
                $room = $agreement->reservation->room ?? null;

                // Room Pictures
                if ($room && !empty($room->room_picture_url)) {
                    $roomPictures = json_decode($room->room_picture_url, true);
                    $room->room_picture_url = array_map(function ($url) {
                        if (Str::startsWith($url, ['http://', 'https://'])) {
                            return $url;
                        }
                    
                        // Otherwise, wrap it with asset()
                        return asset('storage/' . ltrim($url, '/'));
                    }, $roomPictures);
                }

                // Reservation Payment Proof
                $proofUrls = json_decode($agreement->reservation->reservation_payment_proof_url ?? '[]', true);
                $agreement->reservation->reservation_payment_proof_url = array_map(function ($url) {
                    return asset('storage/' . ltrim($url, '/'));
                }, $proofUrls);
            }
        }

        return $this->successResponse(['tenant' => $tenant], 'Tenant retrieved successfully');
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

        $latestRentNotice = Notification::with([
            'notifiable.billable.rentalAgreement.reservation.room.property'
        ])
        ->whereHasMorph('notifiable', [\App\Models\Billing::class], function (Builder $query) use ($profile_id) {
            $query->where('profile_id', $profile_id)
            ->where('status','pending')
            ->where('title', 'like', 'Monthly Rent Billing for%');
        })
            ->orderBy('created_at', 'desc')
            ->get();


        $paymentHistory = Billing::with(['payments', 'rentalAgreement'])
        ->where('profile_id', $profile_id)
        ->where('billable_type', RentalAgreement::class)
        ->orderBy('billing_month', 'desc')
        ->get();

        $notification = Notification::where('user_id', $tenant->userProfile->user->id)->get();

        return $this->successResponse([
            'tenant' => $tenant,
            'payment_history' => $paymentHistory,
            'rental_agreements' => $rentalAgreement, 
            'latest_rent_notice' => $latestRentNotice, 
            'notifications' => $notification,
        ], 'Tenant fetched successfully.');
    }

    public function updateUserData(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFoundResponse(null, 'User not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (isset($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        return $this->successResponse($user, 'User updated successfully');
    }

    public function updateTenantProfile(Request $request, $tenant_id)
    {
        $tenant = Tenant::with('userProfile.address')->find($tenant_id);

        if (!$tenant) {
            return $this->notFoundResponse(null, 'Tenant not found');
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|max:15',
            'social_media_links' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'driver_license_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'social_security_number' => 'nullable|string|max:255',
            'address' => 'required|array',
            'address.line_1' => 'required|string|max:255',
            'address.line_2' => 'nullable|string|max:255',
            'address.province' => 'nullable|string|max:255',
            'address.country' => 'nullable|string|max:255',
            'address.postal_code' => 'nullable|string|max:10',
        ]);

        $profile = $tenant->userProfile;

        if (!$profile) {
            return $this->notFoundResponse(null, 'User profile not found');
        }

        $profile->update([
            'phone_number' => $validated['phone_number'],
            'social_media_links' => $validated['social_media_links'],
            'occupation' => $validated['occupation'],
            'driver_license_number' => $validated['driver_license_number'],
            'national_id' => $validated['national_id'],
            'passport_number' => $validated['passport_number'],
            'social_security_number' => $validated['social_security_number'],
        ]);

        $profile->address()->updateOrCreate([], $validated['address']);

        // Re-load just to reflect fresh data if needed
        $profile->load('address');

        return $this->successResponse(['tenant' => $profile], 'Tenant profile updated successfully');
    }

    
}

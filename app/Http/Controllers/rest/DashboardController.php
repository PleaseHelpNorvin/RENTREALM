<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Payment;
use App\Models\Billing;
use App\Models\Room;
use App\Models\Handyman;
use App\Models\Tenant;
use App\Models\Reservation;
use App\Models\RentalAgreement;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    //
    public function index() {
        // $apartments = Property::with('rooms')->get();
        $propertyCount = Property::count();
        $roomCount = Room::count(); 
        $totalPaid = Payment::where('status', 'paid')->sum('amount_paid');
        $overduePayments = Billing::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', Carbon::now())
            ->sum('remaining_balance');
        $handymanCount = Handyman::count();
        $availableHandymanCount = Handyman::where('status', 'available')->count();
        $tenantCount = Tenant::count();
        $pendingReservationCount = Reservation::where('status', 'pending')->count();
        $agreementCount = RentalAgreement::count();
        $usersCount = User::where('role', 'tenant')->count();
        $paidPaymentCount = Payment::where('status', 'paid')->count();
        $partialPaymentCount = Payment::where('status', 'partial')->count();
        $pendingPaymentCount = Payment::where('status', 'pending')->count(); // optional
        
        $totalPaymentCount = Payment::count();

        $occupiedRoomCount = Room::where('status', 'occupied')->count();
        $vacantRoomCount = Room::where('status', 'vacant')->count();
        $oneWeekAgo = Carbon::now()->subDays(7);
        $newTenantCount = Tenant::whereBetween('created_at', [$oneWeekAgo, Carbon::now()])->count();
        

        return $this->successResponse([
            // 'rooms' => $apartments,
            'property_count' => $propertyCount,
            'room_count' => $roomCount,
            'total_paid_count' => $totalPaid,
            'overdue_payments_sum' => $overduePayments,
            'handyman_count' => $handymanCount,
            'available_handyman_count' => $availableHandymanCount,
            'tenant_count' => $tenantCount,
            'pending_reservation_count' => $pendingReservationCount,
            'total_agreements_count' => $agreementCount,
            'total_users_count' => $usersCount,

                //Payments breakdown
            'total_payment_count' => $totalPaymentCount,
            'paid_payment_count' => $paidPaymentCount,
            'partial_payment_count' => $partialPaymentCount,
            'pending_payment_count' => $pendingPaymentCount, // optional

            'occupied_room_count' => $occupiedRoomCount,
            'vacant_room_count' => $vacantRoomCount,
            'new_tenant_count' => $newTenantCount,
        ], 'Index fetch success');
    }
}

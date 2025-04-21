<?php

namespace App\Http\Controllers\rest;

use App\Models\Room;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    public function index() {
        //tenant reports

        $tenants = Tenant::with(['rentalAgreements.reservation.room.property','userProfile.user'])->get();
        $tenantReports = $tenants->map(function ($tenant, $index) {
            $rentalAgreement = $tenant->rentalAgreements->first();
    
            return [
                'code' => 'TNT-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'name' => optional($tenant->userProfile->user)->name ?? 'N/A',
                'apartment' => optional($rentalAgreement?->reservation?->room?->property)->name 
                    && optional($rentalAgreement?->reservation?->room)->room_code
                    ? $rentalAgreement->reservation->room->property->name . ' - ' . $rentalAgreement->reservation->room->room_code 
                    : 'No Rental Agreement',
                'joined_at' => $tenant->created_at->format('M d, Y'),
                'status' => ucfirst($tenant->status),
            ];
        });
// ================================================================
        //Rent collection reports
        
        $billings = Billing::with([
            'billable' => function ($morphTo) {
                $morphTo->morphWith([
                    RentalAgreement::class => ['reservation.room.property'],
                    Tenant::class => ['userProfile.user', 'rentalAgreements.reservation.room.property'],
                ]);
            }
        ])->get();

        $rentCollectionReports = $billings->map(function ($billing, $index) {
            $code = 'RC-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $amount = '₱' . number_format($billing->total_amount, 2);
        
            $tenantName = 'N/A';
            $apartment = 'N/A';
        
            if ($billing->billable instanceof Tenant) {
                $userProfile = $billing->billable->userProfile;
                $tenantName = optional($userProfile?->user)->name ?? 'N/A';
            
                $rentalAgreement = $billing->billable->rentalAgreements->first();
                $apartment = optional($rentalAgreement?->reservation?->room?->property)->name . ' - ' .
                             optional($rentalAgreement?->reservation?->room)->room_code ?? 'No Info';
            } elseif ($billing->billable instanceof RentalAgreement) {
                $rentalAgreement = $billing->billable;
            
                $tenant = $rentalAgreement->tenants->first();
                $tenantName = optional($tenant?->userProfile?->user)->name ?? 'N/A';
            
                $apartment = optional($rentalAgreement?->reservation?->room?->property)->name . ' - ' .
                             optional($rentalAgreement?->reservation?->room)->room_code ?? 'No Info';
            }
            $month = optional($billing->billing_month)->format('m-d-Y') ?? 'N/A';
            $status = ucfirst($billing->status); 
        
            return [
                'code' => $code,
                'tenant' => $tenantName,
                'apartment' => $apartment,
                'month' => $month,
                'amount' => $amount,
                'status' => $status,
            ];
        });
// ================================================================
        // unit occupancy reports

        $unitOccupancyReports = Room::with('property')->get();

        $groupedOccupancy = $unitOccupancyReports->groupBy('property.name')->map(function ($rooms, $propertyName) {
            $total = $rooms->count();
            $occupied = $rooms->where('status', 'occupied')->count();
            $vacant = $rooms->where('status', 'vacant')->count();
        
            return [
                'apartment' => $propertyName,
                'total_units' => $total,
                'occupied' => $occupied,
                'vacant' => $vacant,
            ];
        })->values();
// ================================================================
        //maintenance requests reports
        
        // $maintenanceRequestsReports;

// ================================================================
        $notifications = Notification::with('notifiable')->get();

        $mappedNotifications = $notifications->map(function($notification) {
            // Dynamically load the notifiable model based on the type
            $notifiable = $notification->notifiable;
            $action = '';

            // Check the type of notifiable model and get the appropriate action message
            if ($notification->notifiable_type == 'App\\Models\\Reservation') {
                $action = 'Reservation ' . $notifiable->status . ' - ' . $notifiable->reservation_code;
            } elseif ($notification->notifiable_type == 'App\\Models\\Payment') {
                $action = 'Payment ' . $notifiable->status . ' - ' . $notifiable->payment_method;
            } elseif ($notification->notifiable_type == 'App\\Models\\Billing') {
                $action = 'Billing for ' . $notifiable->created_at->format('F Y');
            }

            // Format the date/time
            $dateTime = $notification->created_at->format('F j, Y - g:i A');

            return [
                'id' => $notification->id,
                'user' => User::find($notification->user_id)->name,
                'action' => $action,
                'date_time' => $dateTime
            ];
    });

        return $this->successResponse([
            'tenant_reports' => $tenantReports,
            'rent_collection_reports' => $rentCollectionReports,
            'unit_occupancy_reports' => $groupedOccupancy,
            // 'maintenance_requests_reports'
            'audits_logs_activity_reports' => $mappedNotifications,
        ],'success');
    }
}

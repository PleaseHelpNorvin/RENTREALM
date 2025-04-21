<?php

namespace App\Http\Controllers\rest;
use Illuminate\Support\Facades\Log;
use App\Models\Room;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    public function index() {
        //tenant reports

        $tenants = Tenant::with(['rentalAgreements.reservation.room.property','tenant'])->get();
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

    $payments = Payment::with('billing.userProfile.user')->get();
    $rentCollectionReports = $payments->map(function ($payment) {
        return [
            'code' => 'RC-' . str_pad($payment->id, 3, '0', STR_PAD_LEFT), // Generate a unique code for each payment
            'tenant' => $payment->billing->userProfile->user->name, // Assuming the tenant name is in the related profile
            'title' => $payment->billing->billing_title,
            'month' => \Carbon\Carbon::parse($payment->billing->billing_month)->format('m-d-Y'), // Format month to desired format
            'amount' => '₱' . number_format($payment->amount_paid, 2), // Format amount with currency symbol
            'status' => ucfirst($payment->status), // Capitalize the status
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
        $maintenanceRequests = MaintenanceRequest::with('tenant.userProfile.user', 'room.property')->get();

        // Map maintenance requests to match the table format
        $maintenanceRequestsReports = $maintenanceRequests->map(function ($request, $index) {
            $tenantName = optional($request->tenant->userProfile->user)->name ?? 'N/A';
            $apartment = optional($request->room->property)->name . ' - ' . optional($request->room)->room_code ?? 'No Info';
            $issue = $request->title ?? 'No Issue';
            $status = $request->status ?? 'pending';
    
            return [
                'ticket_code' => 'MR-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'tenant' => $tenantName,
                'apartment' => $apartment,
                'issue' => $issue,
                'status' => $status, // Keep status as a string
            ];
        });
        // $maintenanceRequestsReports;

// ================================================================
        $notifications = Notification::with('notifiable')->get();

        $mappedNotifications = $notifications->map(function($notification, $index) {
            $notifiable = $notification->notifiable;
            $action = '';
        
            if ($notification->notifiable_type == 'App\\Models\\Reservation') {
                $action = $notification->message;
            } elseif ($notification->notifiable_type == 'App\\Models\\Payment') {
                $action = $notification->message;
            } elseif ($notification->notifiable_type == 'App\\Models\\Billing') {
                $action = $notification->message;
            } elseif($notification->notifiable_type == 'App\Models\MaintenanceRequest') {
                $action = $notification->message;
            }
        
            $dateTime = $notification->created_at->format('F j, Y - g:i A');
        
            return [
                'id' => $notification->id,
                'aud_code' => 'LOG-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'user' => User::find($notification->user_id)->name,
                'action' => $action,
                'date_time' => $dateTime
            ];
        });

        return $this->successResponse([
            'tenant_reports' => $tenantReports,
            'rent_collection_reports' => $rentCollectionReports,
            'unit_occupancy_reports' => $groupedOccupancy,
            'maintenance_requests_reports' => $maintenanceRequestsReports,
            'audits_logs_activity_reports' => $mappedNotifications,
        ],'success');
    }
}

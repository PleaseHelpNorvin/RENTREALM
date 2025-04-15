<?php

namespace App\Http\Controllers\rest;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;


class NotificationController extends Controller
{
    //

    public function index($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)
            ->with('notifiable')
            ->get();

        if ($notifications->isEmpty()) {
            return $this->notFoundResponse(null, "No notifications found for User $user_id.");
        }

        return $this->successResponse(['notifications' => $notifications], "Notifications found for User $user_id.");
    }

    public function indexUnread($user_id)
    {
        $unreadNotifications = Notification::where('user_id', $user_id)
            ->where('is_read', false)
            ->get();

        if ($unreadNotifications->isEmpty()) {
            return $this->notFoundResponse(null, "No unread notifications found for User $user_id.");
        }

        return $this->successResponse(['notifications' => [$unreadNotifications]], "Unread notifications found for User $user_id.");
    }

    public function sendOverdueWarningToTenant(Request $request) {
        $validatedData = $request->validate([
            'tenant_user_id' => 'required|exists:users,id',
            'admin_id' => 'required|exists:users,id',
            'notification_id' => 'required|exists:notifications,id',
        ]);
    
        // Correct logging
        Log::info('Sending overdue warning with validated data:', $validatedData);
    
        $latestRentNotice = Notification::with('notifiable.billable.rentalAgreement.reservation.room.property')->find($validatedData['notification_id']);

        $billing = $latestRentNotice->notifiable;
        $tenant = User::find($validatedData['tenant_user_id']);
        $rentalAgreement = $billing->billable->rentalAgreement;
        $room = $rentalAgreement->reservation->room;
        $property = $room->property;
        $admin = User::find($validatedData['admin_id']);

        // Format dates using Carbon
        $billingMonth = \Carbon\Carbon::parse($billing->billing_month);
        $dueDate = $billingMonth->format('F d, Y');
        $overdueReference = $billingMonth->copy()->subMonth()->format('F d, Y');
        $formattedCreatedAt = \Carbon\Carbon::parse($billing->created_at)->format('F d, Y');

        // Compose message
        $warningMessage = <<<EOT
            Overdue Payment on {$formattedCreatedAt} 
            Tenant: {$tenant->name}

            Billing Details:
            Billing Title: {$billing->billing_title}
            Billing Month: {$dueDate}
            Total Amount: {$billing->total_amount}
            Amount Paid: {$billing->amount_paid}
            Remaining Balance: {$billing->remaining_balance}

            Rental Agreement:
            Agreement ID: {$rentalAgreement->agreement_code}

            Room Code: {$room->room_code}
            Property Name: {$property->name}

            Warning: Your payment for the billing due on {$overdueReference} is overdue. Please make the payment immediately to avoid further penalties.
            sencerely yours, 
            {$admin->name} 
            {$admin->role}
        EOT;

        $overdueWarning = $billing->notifications()->create([
            'user_id' => $tenant->id, // Assuming you are sending this to the tenant
            'title' => 'Overdue Payment Warning',
            'message' => $warningMessage,
            'is_read' => false,
        ]);

        return $this->successResponse([
            'over_due_warning' => $overdueWarning
        ], 'over_due_warning created successfully');
    }

    public function show($id)
    {
        $showNotification = Notification::find($id);

        if (!$showNotification) {
            return $this->notFoundResponse(null, "No notification with id $id found");
        }

        return $this->successResponse(['notification' => [$showNotification]], "Notification $id Found");
    }

    public function updateIsRead($id) {
        $notification = Notification::where('id', $id)
            ->first();
    
        if (!$notification) {
            return $this->notFoundResponse(null, "Notification $id not found.");
        }
    
        $notification->update(['is_read' => 1]);
        // $notification->update(['is_read' => true]);

        return $this->successResponse(['notifications' => [$notification]], "Notification $id marked as read.");
    }

}

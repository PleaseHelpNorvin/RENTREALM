<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Billing;
use Illuminate\Bus\Queueable;
use App\Models\RentalAgreement;
use Illuminate\Support\Facades\Log;  // Add this line to import Log
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckFailedPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // public $tries = 1;  
    public $timeout = 120; // 2 minutes

    protected $userId;
    protected $billingId;

    public function __construct($userId, $billingId)
    {
        $this->userId = $userId;
        $this->billingId = $billingId;
    }

    public function handle()
    {
        Log::info("STARTING CHECK FAILED PAYMENT JOB");

        $billing = Billing::find($this->billingId);
        Log::info('Billing Record:', ['billing' => $billing]);

        $latestRentalAgreement = RentalAgreement::whereHas('reservation.userProfile', function($query) {
            $query->where('user_id', $this->userId);
        })->latest('created_at')->first();

        Log::info('Latest Rental Agreement:', ['latestRentalAgreement' => $latestRentalAgreement]);


        if ($billing && $billing->status == 'pending') {
            $existNotifs = $billing->notifications()->where('title', 'Payment Failed')
                ->where('message', "Please Complete your payment on the Rental Agreement Code: $latestRentalAgreement->agreement_code, you still have balance of $billing->remaining_balance PHP.")
                ->exists();

            if ($existNotifs) {
                Log::info('Duplicate Notification Triggered', [
                    'user_id' => $billing->userProfile->user->id,
                    'agreement_code' => $latestRentalAgreement->agreement_code,
                    'billing_id' => $billing->id,
                    'existing_notification_message' => "Payment Failed notification already exists for the Rental Agreement Code: $latestRentalAgreement->agreement_code"
                ]);
            }

            if (!$existNotifs) {
                Log::info('failed payment detected, Triggering notification cretion for payment failed');
                $billing->notifications()->create([
                    'user_id' => $billing->userProfile->user->id,
                    'title' => 'Payment Failed',
                    'message' => "Please Complete your payment on the Rental Agreement Code: $latestRentalAgreement->agreement_code, you still have balance of $billing->remaining_balance PHP.",
                    'is_read' => false
                ]);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\rest;

use App\Models\Tenant;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    //
    public function index() {
        $payments = Payments::get();
        return $this->successResponse(['payments' => $payments], "fetched payments");
    }

    public function storePayment(Request $request)
    {
        // ✅ Validate request
        $validated = $request->validate([
            'billing_id' => 'required|exists:billings,id',
            'amount_paid' => 'required|numeric|min:1', // Ensure it's a number and at least 1
            'payment_method' => 'required|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'proof_url' => 'nullable|array', // Ensure it's an array (for multiple images)
            'proof_url.*' => 'url', // Each proof must be a valid URL
        ]);
    
        // 🔎 Find the billing record by ID (already validated)
        $billing = Billing::find($validated['billing_id']);
    
        // 💰 Create the payment
        $payment = Payment::create([
            'billing_id' => $billing->id,
            'payable_id' => $billing->billable_id,
            'payable_type' => $billing->billable_type,
            'profile_id' => $billing->profile_id,
            'amount_paid' => $validated['amount_paid'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'],
            'proof_url' => json_encode($validated['proof_url']),
            'status' => 'paid',
        ]);
    
        // 🏦 Update the Billing Record
        $billing->amount_paid += $validated['amount_paid'];
        $billing->remaining_balance = $billing->total_amount - $billing->amount_paid;
    
        if ($billing->remaining_balance <= 0) {
            $billing->status = 'paid';
        } elseif ($billing->amount_paid > 0) {
            $billing->status = 'partial';
        }
    
        $billing->save();


        // 🏠 Create or Find Tenant
        $status = ($billing->status === 'paid') ? 'active' : 'pending';

        $tenant = Tenant::firstOrCreate(
            ['profile_id' => $billing->profile_id], // Find condition
            [
                'rental_agreement_id' => $billing->billable_id,
                'status' => "$status", // ✅ Ensure it's a string
            ]
        );

        // 📢 Notify Tenant if Payment is Partial
        if ($billing->status === 'partial') {
            $payment->notifications()->create([
                'user_id' => $billing->userProfile->user_id,
                'title' => 'payment_reminder',
                'message' => 'Your payment is incomplete. Please complete your payment to avoid issues.',
                'is_read' => 0,
            ]);
        }

    
        return $this->successResponse(['payment' => $payment], 'Payment stored successfully');
    }
        


}

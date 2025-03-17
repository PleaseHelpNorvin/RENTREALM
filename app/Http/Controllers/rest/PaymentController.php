<?php

namespace App\Http\Controllers\rest;

use App\Models\Tenant;
use App\Models\Billing;
use GuzzleHttp\Client;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    //
    public function index() {
        $payments = Payments::get();
        return $this->successResponse(['payments' => $payments], "fetched payments");
    }
    
    

    public function processPayment(Request $request)
    {
        $validatedData = $request->validate([
            'billing_id' => 'required|string',
            'amount' => 'required|numeric',
            'payment_description' => 'required|string',
        ]);

        $billing = Billing::where('id', $validatedData['billing_id'])->first();

        if (!$billing) {
            return response()->json(['error' => 'Billing record not found'], 404);
        }
    
        $userProfile = $billing->userProfile;
    
        if (!$userProfile) {
            return response()->json(['error' => 'User profile not found'], 404);
        }
    
        $userName = $userProfile->user->name ?? 'Unknown';
    
        
        $data = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'currency' => 'PHP',
                            'amount' => (int) ($validatedData['amount'] * 100),
                            'description' =>  $validatedData['payment_description'],
                            'name' => $userName,
                            'quantity' => 1,
                        ]
                    ],
                    'payment_method_types' => [ // Correct placement of this field
                        'card', 'gcash'
                    ],
                    'success_url' => 'http://localhost:8000/payment-success',
                    'cancel_url' => 'http://localhost:8000/payment-failed',
                    'description' => $validatedData['payment_description'],
                ],
            ],
        ];
    
        // Make the API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            // 'Authorization' => 'Basic ' . base64_encode(env('PAYMONGO_SECRET_KEY') . ':'),
            'Authorization' => 'Basic ' . base64_encode('sk_test_ApQQerjP8y1vNkvXUYwwf4P7' . ':'),
            ])
        ->withoutVerifying()
        ->post('https://api.paymongo.com/v1/checkout_sessions', $data);
    
        // Check for a successful response
        if ($response->successful()) {
            $checkoutSession = $response->json();

            // Extract checkout URL
            $checkoutUrl = $checkoutSession['data']['attributes']['checkout_url'] ?? null;
            $checkoutSessionId = $checkoutSession['data']['id'] ?? null;
            // Extract Reference Number (if available)
            $billing->update(['checkout_session_id' => $checkoutSessionId]);

            return $this->successResponse([
                'checkout_url' => $checkoutUrl,
            ]);
        } else {
            // Log the error response for debugging
            return $this->errorResponse(
                $response->json()['errors'] ?? [],
                'Payment processing failed',
                $response->status()
            );

        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        if (isset($payload['data']['attributes'])) {
            $attributes = $payload['data']['attributes'];

            // Check if this is a successful payment event
            if ($payload['data']['type'] === 'payment.paid') {
                $referenceNumber = $attributes['reference_number'] ?? null;
                $paymentId = $attributes['id'] ?? null;
                $amount = $attributes['amount'] / 100; // Convert cents to PHP
                $paymentMethod = $attributes['payment_method_type'] ?? 'unknown';

                // Find the related billing record in your database
                // $billing = Billing::where('payment_id', $paymentId)->first();

                if ($billing) {
                    // Call the storePaymentAfterPayMongo function
                    $this->storePaymentAfterPayMongo(
                        $billing->id,
                        $amount,
                        $paymentMethod,
                        $referenceNumber,
                        null // You can handle proof file separately if needed
                    );

                    return response()->json(['message' => 'Payment processed successfully', 'reference_number' => $referenceNumber], 200);
                }
            }
        }

        return response()->json(['message' => 'Unhandled event'], 400);
    }
            
        // return $this->storePaymentAfterPayMongo($billingId, $amount, 'GCash', $paymentReference, null);

    private function storePaymentAfterPayMongo($billingId, $amountPaid, $paymentMethod, $paymentReference, $paymentProofFiles)
    {
        // // 🔎 Find the billing record
        // $billing = Billing::find($billingId);
        // if (!$billing) {
        //     return response()->json(['message' => 'Billing not found'], 404);
        // }

        // // ✅ Fix: Prevent error if no files are uploaded
        // $paymentProofsUrls = [];
        // if ($paymentProofFiles && is_array($paymentProofFiles)) {
        //     foreach ($paymentProofFiles as $file) {
        //         $paymentProofsUrls[] = $file->store('payment_proofs', 'public');
        //     }
        // }

        // // 💰 Create Payment Record
        // $payment = Payment::create([
        //     'billing_id' => $billing->id,
        //     'payable_id' => $billing->billable_id,
        //     'payable_type' => $billing->billable_type,
        //     'profile_id' => $billing->profile_id,
        //     'amount_paid' => $amountPaid,
        //     'payment_method' => $paymentMethod,
        //     'payment_reference' => $paymentReference,
        //     'payment_proof_url' => json_encode($paymentProofsUrls),
        //     'status' => 'paid',
        // ]);

        // // 🏦 Update Billing Record
        // $billing->amount_paid += $amountPaid;
        // $billing->remaining_balance = $billing->total_amount - $billing->amount_paid;
        
        // if ($billing->remaining_balance <= 0) {
        //     $billing->status = 'paid';
        // } elseif ($billing->amount_paid > 0) {
        //     $billing->status = 'partial';
        // }

        // $billing->save();

        // // 🏠 Create or Update Tenant
        // $status = ($billing->status === 'paid') ? 'active' : 'pending';

        // // ✅ Fix: Check if Tenant already exists before creating a new one
        // $tenant = Tenant::where('profile_id', $billing->profile_id)->first();
        // if ($tenant) {
        //     $tenant->status = $status;
        //     $tenant->save();
        // } else {
        //     Tenant::create([
        //         'profile_id' => $billing->profile_id,
        //         'rental_agreement_id' => $billing->billable_id,
        //         'status' => $status,
        //     ]);
        // }

        // // 📢 Notify Tenant if Payment is Partial
        // if ($billing->status === 'partial') {
        //     Notification::create([
        //         'user_id' => $billing->userProfile->user_id,
        //         'title' => 'Payment Reminder',
        //         'message' => 'Your payment is incomplete. Please complete your payment to avoid issues.',
        //         'is_read' => 0,
        //     ]);
        // }

        // return response()->json(['payment' => $payment, 'message' => 'Payment stored successfully']);
    }

}

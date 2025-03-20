<?php

namespace App\Http\Controllers\rest;

use App\Models\Tenant;
use GuzzleHttp\Client;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    //
    public function index() {
        $payments = Payment::get();
        return $this->successResponse(['payments' => $payments], "fetched payments");
    }
    
    

    public function processPayment(Request $request)
    {
        $validatedData = $request->validate([
            'billing_id' => 'required|numeric',
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
            // $this->retrievePayment($billing->id);

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

    public function retrievePayment($billingId)
    {
        Log::info("Retrieving payment for billing ID: {$billingId}");
    
        // Find the billing record with the stored checkout_session_id
        $billing = Billing::where('id', $billingId)->first();
    
        if (!$billing || !$billing->checkout_session_id) {
            Log::error("Billing record not found or checkout_session_id missing for billing ID: {$billingId}");
            return response()->json(['error' => 'Billing record or checkout session ID not found'], 404);
        }
    
        $checkoutSessionId = $billing->checkout_session_id;
        Log::info("Found checkout_session_id: {$checkoutSessionId} for billing ID: {$billingId}");
    
        // Make a request to PayMongo API to get the session details
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('sk_test_ApQQerjP8y1vNkvXUYwwf4P7' . ':'),
            ])->withoutVerifying()
            ->get("https://api.paymongo.com/v1/checkout_sessions/$checkoutSessionId");
    
            Log::info("PayMongo API request sent. Response status: " . $response->status());
    
            if ($response->successful()) {
                $sessionData = $response->json();
                Log::info("PayMongo API response received", ['response' => $sessionData]);
    
                // Extract payment details
                $payments = $sessionData['data']['attributes']['payments'] ?? [];
    
                if (empty($payments)) {
                    Log::warning("No payments found for checkout session ID: {$checkoutSessionId}");
                    return response()->json(['message' => 'Payment is still pending'], 200);
                }
    
                // Assuming there's only one payment associated
                $paymentId = $payments[0]['id'] ?? null;
                $paymentStatus = $payments[0]['attributes']['status'] ?? null;
                $paymentReference = $payments[0]['attributes']['payment_od'] ?? null;
                $amountPaid = $payments[0]['attributes']['amount'] ?? null;
                // $paymentMethod = $payments[0]['attributes']['payment_method_type'] ?? null;
                $paymentMethod = $response['data']['attributes']['payment_method_used'] ?? null;

                Log::info("Payment retrieved successfully", [
                    'payment_id' => $paymentId,
                    'status' => $paymentStatus,
                    'reference_number' => $paymentId,
                    'payment_method' => $paymentMethod
                ]);

                Log::info("======================================================");
                  // Call storePaymentAfterPayMongo() on success
                if ($paymentStatus === 'paid') {
                    $this->storePaymentAfterPayMongo($billingId, $amountPaid, $paymentMethod, $paymentId, []);
                }
    
                return response()->json([
                    'payment_id' => $paymentId,
                    'status' => $paymentStatus,
                    'paymongo_payment_number' => $paymentId,
                    ''
                ]);
            } else {
                Log::error("PayMongo API request failed", [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving payment: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    
        return response()->json(['error' => 'Failed to retrieve payment details'], 500);
    }
            
        // return $this->storePaymentAfterPayMongo($billingId, $amount, 'GCash', $paymentReference, null);

    private function storePaymentAfterPayMongo($billingId, $amountPaid, $paymentMethod, $paymongoPaymentRef, $paymentProofFiles)
    {

        try {
            Log::info('storePaymentAfterPayMongo called with:', [
                'billingId' => $billingId,
                'amountPaid' => $amountPaid,
                'paymentMethod' => $paymentMethod,
                'paymentReference' => $paymongoPaymentRef,
                'paymentProofFiles' => $paymentProofFiles
            ]);
        } catch (\Exception $e) {
            Log::error('Logging failed: ' . $e->getMessage());
        }
        // 🔎 Find the billing record
        $billing = Billing::find($billingId);
        if (!$billing) {
            return response()->json(['message' => 'Billing not found'], 404);
        }

        // ✅ Fix: Prevent error if no files are uploaded
        $paymentProofsUrls = [];
        if ($paymentProofFiles && is_array($paymentProofFiles)) {
            foreach ($paymentProofFiles as $file) {
                $paymentProofsUrls[] = $file->store('payment_proofs', 'public');
            }
        }

        // 💰 Create Payment Record
        $payment = Payment::create([
            'billing_id' => $billing->id,
            'payable_id' => $billing->billable_id,
            'payable_type' => $billing->billable_type,
            'profile_id' => $billing->profile_id,
            'amount_paid' => $amountPaid,
            'payment_method' => $paymentMethod,
            'paymongo_payment_reference' => $paymongoPaymentRef,
            'payment_proof_url' => json_encode($paymentProofsUrls),
            'status' => 'paid',
        ]);

        // 🏦 Update Billing Record
        $billing->amount_paid += $amountPaid;
        $billing->remaining_balance = $billing->total_amount - $billing->amount_paid;
        
        if ($billing->remaining_balance <= 0) {
            $billing->status = 'paid';
        } elseif ($billing->amount_paid > 0) {
            $billing->status = 'partial';
        }

        $billing->save();

        // 🏠 Create or Update Tenant
        $status = ($billing->status === 'paid') ? 'active' : 'pending';

        // ✅ Fix: Check if Tenant already exists before creating a new one
        $tenant = Tenant::where('profile_id', $billing->profile_id)->first();
        if ($tenant) {
            $tenant->status = $status;
            $tenant->save();
        } else {
            Tenant::create([
                'profile_id' => $billing->profile_id,
                'rental_agreement_id' => $billing->billable_id,
                'status' => $status,
            ]);
        }

        // 📢 Notify Tenant if Payment is Partial
        if ($billing->status === 'partial') {
            $payment->notifications()->create([
                'user_id' => $billing->userProfile->user_id,
                'title' => 'Payment Reminder',
                'message' => 'Your payment is incomplete. Please complete your payment to avoid issues.',
                'is_read' => 0,
            ]);
        } elseif ($billing->status === 'paid') {
            $payment->notifications()->create([
                'user_id' => $billing->userProfile->user_id,
                'title' => 'Payment Successful',
                'message' => 'Your payment has been fully received. Thank you!',
                'is_read' => 0,
            ]);

            $this->generatePdfReceipt($payment);
        }
        
        return response()->json(['payment' => $payment, 'message' => 'Payment stored successfully']);
    }

    public function generatePdfReceipt(Payment $payment)
{
    // 🔍 Ensure `payable` relationship is loaded
    $payment->load('payable');

    if (!$payment->payable) {
        Log::error("Payable relationship not found for Payment ID: {$payment->id}");
        return response()->json(['error' => 'Invalid payment association'], 400);
    }

    // 📁 Ensure storage directory exists
    $directory = storage_path("app/public/payment_receipts");
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        Log::info("Created directory: " . $directory);
    } else {
        Log::info("Directory already exists: " . $directory);
    }

    // 📄 Define PDF storage path
    $pdfPath = storage_path("app/public/payment_receipts/receipt_{$payment->id}.pdf");
    Log::info("Attempting to save PDF to: " . $pdfPath);

    // 🖨 Generate PDF
    try {
        // Ensure `payable` model has a user relationship before accessing it
        $user = null;
        if ($payment->payable instanceof Billing) {
            $user = optional($payment->payable->userProfile)->user;
        }

        $pdf = Pdf::loadView('payments.receipt_pdf', [
            'payment' => $payment, 
            'billing' => $payment->payable, 
            'user' => $user,
        ]);

        $pdf->save($pdfPath);

        // ✅ Ensure PDF was created
        if (!file_exists($pdfPath)) {
            Log::error("PDF file was not created at: " . $pdfPath);
            return response()->json(['error' => 'Failed to generate PDF'], 500);
        }

        Log::info("PDF successfully created at: " . $pdfPath);

        return response()->json([
            'pdf_path' => asset("storage/payment_receipts/receipt_{$payment->id}.pdf")
        ], 200);
    } catch (\Exception $e) {
        Log::error("PDF generation error: " . $e->getMessage());
        return response()->json(['error' => 'PDF generation failed'], 500);
    }
}


    


// ============================================================================================================================


    // public function RetrieveReceiptsByProfileId($profileId) {
    //     $receipts = Payment::where('profile_id', $profileId)->get();
    
    //     if ($receipts->isEmpty()) {
    //         return $this->NotFoundResponse(null, 'No receipt found');
    //     }
    
    //     return $this->successResponse(['receipts' => $receipts], "Receipts fetched successfully");
    // }

}

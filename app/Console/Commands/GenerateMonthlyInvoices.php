<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Billing;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for active tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d'); // Get today's date
        Log::info("Invoice generation started for date: {$today}");
    
        $tenants = Tenant::with(['rentalAgreement', 'userProfile'])
            ->where('status', 'active')
            ->whereHas('rentalAgreement', function ($query) {
                $query->whereNull('rent_end_date'); // Only get active rentals
            })
            ->get();
    
        Log::info("Total active tenants found: " . $tenants->count());
    
        foreach ($tenants as $tenant) {
            Log::info("Checking invoices for tenant ID: {$tenant->id}");
    
            // 🔹 Step 1: Find the latest billing record (Initial Payment or Monthly Rent)
            $latestBilling = Billing::where('billable_id', $tenant->id)
                ->orderBy('billing_month', 'desc') // Get the most recent billing
                ->first();
    
            if (!$latestBilling) {
                Log::warning("No billing history found for tenant ID: {$tenant->id}. Skipping...");
                continue;
            }
    
            Log::info("Latest billing found for tenant ID: {$tenant->id}, Billing Month: {$latestBilling->billing_month}");
    
            // 🔹 Step 2: Determine the next billing date
            $nextBillingDate = Carbon::parse($latestBilling->billing_month)->addMonth()->format('Y-m-d');
            Log::info("Next billing date for tenant ID: {$tenant->id} is {$nextBillingDate}");
    
            // 🔹 Step 3: Ensure we only generate invoices **on the correct billing date**
            if ($today !== $nextBillingDate) {
                Log::info("Skipping tenant ID: {$tenant->id}, today ({$today}) is NOT the next billing date ({$nextBillingDate})");
                continue;
            }
    
            // 🔹 Step 4: Check if an invoice already exists for this billing month
            $existingInvoice = Billing::where('billable_id', $tenant->id)
                ->where('billing_month', $nextBillingDate)
                ->exists();
    
            if ($existingInvoice) {
                Log::info("Invoice already exists for tenant ID: {$tenant->id}, Billing Month: {$nextBillingDate}. Skipping...");
                continue;
            }
    
            // 🔹 Step 5: Calculate the billing amounts
            $monthlyRent = ($tenant->rentalAgreement->total_amount / 2) ?? 0; 
            $amount_paid = 0;
    
            Log::info("Generating new invoice for tenant ID: {$tenant->id}, Amount: PHP {$monthlyRent}");
    
            // 🔹 Step 6: Create the next month's invoice
            $billing = Billing::create([
                'profile_id' => $tenant->profile_id,
                'billable_id' => $tenant->id,
                'billable_type' => Tenant::class,
                'billing_title' => 'Monthly Rent',
                'billing_month' => $nextBillingDate,
                'status' => 'pending',
                'total_amount' => $monthlyRent,
                'amount_paid' => $amount_paid,
                'remaining_balance' => $monthlyRent - $amount_paid,
            ]);
    
            // 🔹 Step 7: Send notification to the tenant
            $billing->notifications()->create([
                'user_id' => $tenant->userProfile->user_id,
                'title' => 'New Rent Invoice Available',
                'message' => "Your rent for " . Carbon::parse($nextBillingDate)->format('F Y') . 
                            " is due on " . Carbon::parse($nextBillingDate)->format('M d, Y') . 
                            ". Amount: PHP {$billing->total_amount}.",
                'is_read' => false,
            ]);
    
            Log::info("Invoice generated for tenant ID: {$tenant->id}, Billing Month: {$nextBillingDate}");
        }
    
        Log::info("Invoice generation process completed on: {$today}");
        $this->info("Monthly invoices checked and generated if necessary on: {$today}");
    }
}

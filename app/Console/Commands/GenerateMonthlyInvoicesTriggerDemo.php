<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Billing;
use Carbon\Carbon;
use Log;

class GenerateMonthlyInvoicesTriggerDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly-invoices-trigger-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually trigger the generation of monthly invoices for demo purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');
        Log::info("Demo Invoice generation started for date: {$today}");

        // Set demo mode to true to bypass the date check
        $demoMode = true;

        // Get all active tenants who have an open rental agreement
        $tenants = Tenant::with(['rentalAgreement', 'userProfile'])
            ->where('status', 'active')
            ->whereHas('rentalAgreement', function ($query) {
                $query->whereNull('rent_end_date'); // Only get active rentals
            })
            ->get();

        Log::info("Total active tenants found: " . $tenants->count());

        // Loop through all tenants to generate invoices
        foreach ($tenants as $tenant) {
            Log::info("Checking invoices for tenant ID: {$tenant->id}");

            // Find the latest billing record (either Initial Payment or Monthly Rent)
            $latestBilling = Billing::where('billable_id', $tenant->id)
                ->orderBy('billing_month', 'desc') // Get the most recent billing
                ->first();

            if (!$latestBilling) {
                Log::warning("No billing history found for tenant ID: {$tenant->id}. Skipping...");
                continue;
            }

            Log::info("Latest billing found for tenant ID: {$tenant->id}, Billing Month: {$latestBilling->created_at}");

            // Calculate next billing date
            $nextBillingDate = Carbon::parse($latestBilling->created_at)->addMonth()->format('Y-m-d');
            Log::info("Next billing date for tenant ID: {$tenant->id} is {$nextBillingDate}");

            // Check if we are in demo mode or it's the correct billing date
            if ($demoMode || $today === $nextBillingDate) {
                Log::info("Generating new invoice for tenant ID: {$tenant->id}, Amount: PHP {$tenant->rentalAgreement->total_amount}");

                // Check if an invoice already exists for this billing month
                $existingInvoice = Billing::where('billable_id', $tenant->id)
                    ->where('billing_month', $nextBillingDate)
                    ->exists();

                if ($existingInvoice) {
                    Log::info("Invoice already exists for tenant ID: {$tenant->id}, Billing Month: {$nextBillingDate}. Skipping...");
                    continue;
                }

                // Calculate the billing amounts
                $monthlyRent = ($tenant->rentalAgreement->total_amount / 2) ?? 0;
                $amount_paid = 0;

                Log::info("Generating new invoice for tenant ID: {$tenant->id}, Amount: PHP {$monthlyRent}");

                // Create the next month's invoice
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

                // Send notification to the tenant
                $billing->notifications()->create([
                    'user_id' => $tenant->userProfile->user_id,
                    'title' => 'Monthly Rent billing for ' . Carbon::now()->format('F'),
                                        'message' => "Your rent for " . Carbon::parse($nextBillingDate)->format('F Y') .
                                " is due on " . Carbon::parse($nextBillingDate)->format('M d, Y') .
                                ". Amount: PHP {$billing->total_amount}.",
                    'is_read' => false,
                ]);

                Log::info("Invoice generated for tenant ID: {$tenant->id}, Billing Month: {$nextBillingDate}");
            } else {
                Log::info("Skipping tenant ID: {$tenant->id}, today ({$today}) is NOT the correct date for invoice generation");
            }
        }

        Log::info("Demo Invoice generation process completed on: {$today}");
        $this->info("Monthly invoices triggered for demo purposes on: {$today}");
    }
}

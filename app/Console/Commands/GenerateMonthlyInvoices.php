<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Billing;
use Illuminate\Console\Command;

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
        $tenants = Tenant::with(['rentalAgreement', 'userProfile'])
            ->where('status', 'active')
            ->whereHas('rentalAgreement', function ($query) {
                $query->whereNull('rent_end_date'); // Only get active rentals
            })
            ->get();
    
        foreach ($tenants as $tenant) {
            $nextBillingDate = now()->addMonth(0); // Get the first day of the current month
            // $monthlyBillNow = now()->addMonth();

            // Prevent duplicate invoices for the same month
            $existingInvoice = Billing::where('billable_id', $tenant->id) // Match tenant ID
                ->where('billing_month', $nextBillingDate->format('Y-m'))
                ->exists();
    
            if ($existingInvoice) {
                continue; // Skip if invoice already exists
            }
    
            // Calculate amounts
            $divided_two_total_amount = ($tenant->rentalAgreement->total_amount / 2) ?? 0;
            $amount_paid = 0; // No payment initially
    
            // Create invoice
            $billing = Billing::create([
                'profile_id' => $tenant->profile_id, // Ensure this exists
                'billable_id' => $tenant->id, 
                'billable_type' => Tenant::class,
                'billing_title' => 'Monthly Rent',
                'billing_month' => $monthlyBillNow->format('Y-m-d'), // Ensure correct date format
                'status' => 'pending',
                'total_amount' => $divided_two_total_amount, // Prevent null issues
                'amount_paid' => $amount_paid, // Initially no payment made
                'remaining_balance' => $divided_two_total_amount - $amount_paid // Corrected: total - paid
            ]);
                    
            // Create notification
            $billing->notifications()->create([
                'user_id' => $tenant->userProfile->user_id,
                'title' => 'New Rent Invoice Available',
                'message' => "Your rent for {$nextBillingDate->format('F Y')} is due on {$nextBillingDate->format('M d, Y')}. Amount: PHP {$billing->total_amount}.",
                'is_read' => false,
            ]);
        }
    
        $this->info('Monthly invoices have been generated successfully.');
    }
}

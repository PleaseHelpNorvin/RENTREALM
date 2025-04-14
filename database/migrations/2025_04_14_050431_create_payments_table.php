<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Links to the bill being paid (optional if not all payments are linked to bills)
            $table->foreignId('billing_id')->nullable()->constrained('billings')->onDelete('cascade'); 
            
            // Polymorphic relationship (for different types of payments)
            $table->morphs('payable'); // Links to reservations, rental agreements, etc.
            // Tenant making the payment
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');

            // Payment details
            $table->decimal('amount_paid', 10, 2); // Amount received
            $table->string('payment_method'); // e.g., GCash, Bank Transfer, Cash
            $table->string('paymongo_payment_reference')->nullable(); // Transaction ID
            $table->json('payment_proof_url')->nullable(); // Store proof of payment
            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

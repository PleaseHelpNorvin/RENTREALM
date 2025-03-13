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
        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            // this column will get the value from reservation table
            // $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');
            // this column will get the value from reservation table
            // $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            
            $table->string('agreement_code');
            
            $table->date('rent_start_date');
            $table->date('rent_end_date')->nullable();
            
            $table->integer('person_count')->nullable();
            $table->decimal('total_amount', 10, 2);
            
            // $table->boolean('advance_payment');

            $table->longText('description')->nullable();
            $table->string('signature_png_string');
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
        // for the next step: 
        // this table is a polymorphic payment table which is best to handle different type of payments 
        // (EX): reservation fees, security deposits, monthly rent, late fees, etc.) under one table.
        
        // Schema::create('payments', function (Blueprint $table) {
        //     $table->id();
            
        //     // Polymorphic relationship
        //     $table->morphs('payable'); // This allows linking to reservations, rental agreements, etc.
            
        //     $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');  // Who is paying?
        //     $table->decimal('amount_due', 10, 2);                                                // Amount expected
        //     $table->decimal('amount_paid', 10, 2)->default(0.00);                                // Amount received
        //     $table->decimal('remaining_balance', 10, 2)->default(0.00);                          // Balance left
        //     $table->string('payment_method');                                                    // e.g., GCash, Bank Transfer, Cash
        //     $table->string('payment_reference')->nullable();                                     // Transaction ID
        //     $table->json('proof_url')->nullable();                                               // Store proof of payment
        //     $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
        //     $table->date('billing_month')->nullable();                                           // For rent payments
        //     $table->timestamps();
        // });

        //    Column Name    |        Type       | Purpose  
        // ------------------|-------------------|-----------------------------------------------
        // id                | bigint            | Primary key  
        // payable_id        | bigint            | ID of the related record (e.g., reservations.id, rental_agreements.id)  
        // payable_type      | string            | Model name (App\Models\Reservation, App\Models\RentalAgreement)  
        // profile_id        | foreignId         | Tenant making the payment  
        // amount_due        | decimal(10,2)     | Expected amount  
        // amount_paid       | decimal(10,2)     | Received amount  
        // remaining_balance | decimal(10,2)     | Amount left unpaid  
        // payment_method    | string            | Payment type (e.g., GCash, Bank Transfer, Cash)  
        // payment_reference | string (nullable) | Transaction ID  
        // proof_url         | json (nullable)   | Proof of payment (image URL)  
        // status            | enum              | pending, partial, paid  
        // billing_month     | date (nullable)   | for recurring or open contract payment  
};


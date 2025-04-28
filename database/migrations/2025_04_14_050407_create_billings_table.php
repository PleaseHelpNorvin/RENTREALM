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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade'); // Tenant being billed
            $table->morphs('billable'); // Links to reservation, rental agreement, etc.
            $table->string('billing_title');
            $table->decimal('total_amount', 10, 2); // Total amount due
            $table->decimal('amount_paid', 10, 2)->default(0.00); // Amount paid
            $table->decimal('remaining_balance', 10, 2)->default(0.00); // Amount left unpaid
            $table->date('billing_month'); // Billing date (e.g., monthly rent)\
            $table->dateTime('due_date');

            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending'); // Billing status
            $table->string('checkout_session_id')->nullable();
            $table->boolean('is_advance_rent_payment')->default(false); // Track if it's an advance payment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};

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
    {   //this a pivot
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');

            // $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            // $table->foreignId('rental_agreement_id')->constrained('rental_agreements')->onDelete('cascade');

            // status info
            // $table->enum('payment_status', ['paid', 'due', 'overdue'])->default('paid');
            $table->enum('status', ['active', 'inactive', 'evicted', 'moved_out'])->default('active');
            // $table->date('next_payment_date')->nullable();

            // Evacuation status info             
            $table->date('evacuation_date')->nullable(); // Stores when the tenant starts evacuation
            $table->date('move_out_date')->nullable(); // Stores final move-out date

            // Contact info
            // $table->string('emergency_contact_name')->nullable();
            // $table->string('emergency_contact_phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
// how to use rental agreement data  

// $tenant = Tenant::with('rentalAgreement')->find($tenantId);
// $rentStartDate = $tenant->rentalAgreement->rent_start_date;
// $rentEndDate = $tenant->rentalAgreement->rent_end_date;
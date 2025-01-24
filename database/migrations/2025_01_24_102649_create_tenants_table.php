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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('rental_agreement_id')->constrained('rental_agreements')->onDelete('restrict');
            // Leasing info
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('rent_price', 10, 2);
            $table->decimal('deposit', 10, 2)->nullable();
            $table->enum('payment_status', ['paid', 'due', 'overdue'])->default('paid');
            $table->enum('status', ['active', 'inactive', 'evicted', 'moved_out'])->default('active');
            // Contact info
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            // Tenant features
            $table->boolean('has_pets')->default(false);
            $table->boolean('wifi_enabled')->default(false);
            $table->boolean('has_laundry_access')->default(false);
            $table->boolean('has_private_fridge')->default(false);
            $table->boolean('has_tv')->default(false);
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

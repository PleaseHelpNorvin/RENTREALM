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
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('agreement_code');

            $table->date('rent_start_date');
            $table->date('rent_end_date')->nullable();

            $table->unsignedTinyInteger('payment_day_cycle')->default(30); // 15th or 30th

            $table->decimal('rent_price', 10, 2);
            $table->decimal('deposit', 10, 2)->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            // Tenant features (contract level)
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
        Schema::dropIfExists('rental_agreements');
    }
};

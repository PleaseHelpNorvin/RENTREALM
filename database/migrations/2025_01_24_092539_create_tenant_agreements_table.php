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
        Schema::create('tenant_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Link to tenant
            $table->foreignId('rental_agreement_id')->constrained('rental_agreements')->onDelete('cascade'); // Link to rental agreement
            $table->date('agreement_start_date');
            $table->date('agreement_end_date')->nullable();
            $table->decimal('rent_price', 10, 2);
            $table->decimal('deposit', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_agreements');
    }
};

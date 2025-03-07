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
        Schema::create('tenant_agreement_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_agreement_id')->constrained('rental_agreements')->onDelete('cascade'); // Link to rental agreement
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Link to tenant
            $table->string('penalty_type'); // Type of penalty (e.g., Late Rent Payment)
            $table->decimal('amount', 10, 2); // Penalty amount
            $table->text('description')->nullable(); // Optional description of penalty
            $table->date('penalty_date')->nullable(); // Optional date of penalty imposition
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_agreement_penalties');
    }
};

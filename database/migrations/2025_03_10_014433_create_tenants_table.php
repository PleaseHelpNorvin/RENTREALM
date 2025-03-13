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
            $table->foreignId('rental_agreement_id')->constrained('rental_agreements')->onDelete('cascade');
            $table->enum('status', ['pending','active','moved_out'])->default('pending');

            // Evacuation status info             
            $table->date('evacuation_date')->nullable();
            $table->date('move_out_date')->nullable();

            // Contact info
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

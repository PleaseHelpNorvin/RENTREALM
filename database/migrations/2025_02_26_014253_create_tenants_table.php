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
            $table->enum('status', ['active', 'inactive', 'evicted', 'moved_out'])->default('active');

            // Evacuation status info             
            $table->date('evacuation_date')->nullable(); // Stores when the tenant starts evacuation
            $table->date('move_out_date')->nullable(); // Stores final move-out date

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

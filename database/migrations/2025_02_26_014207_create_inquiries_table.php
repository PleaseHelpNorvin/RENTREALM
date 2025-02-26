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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted','rejected',])->default('pending');
    

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
        Schema::dropIfExists('inquiries');
    }   
};

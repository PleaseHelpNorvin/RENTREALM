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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('municipality')->nullable();
            $table->string('city')->nullable();
            $table->string('barangay')->nullable();
            $table->string('zone')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('type', ['apartment','house','boarding-house'])->default('apartment');
            $table->enum('status', ['available','rented','full'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

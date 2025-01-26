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
            $table->text('property_picture_url')->nullable();
            $table->enum('gender_allowed', ['boys-only', 'girls-only'])->default('boys-only');
            $table->boolean('pets_allowed')->default(false);
            $table->enum('type', ['apartment', 'house', 'boarding-house'])->default('apartment');
            $table->enum('status', ['available', 'rented', 'full'])->default('available');
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

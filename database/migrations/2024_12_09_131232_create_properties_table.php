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
            $table->string('line_1')->nullable(); // This will now store the full address
            $table->string('line_2')->nullable(); // Make line_2 nullable in case it's optional
            $table->string('province')->nullable(); // Make optional if not always provided
            $table->string('country')->nullable(); // Make optional if not always provided
            $table->string('postal_code')->nullable(); // Make postal_code nullable
            $table->enum('allowed', ['boys only', 'girls only'])->default('boys only');
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

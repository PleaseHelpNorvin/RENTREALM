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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Tenant who requested maintenance
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade'); // Room associated with the request
            $table->foreignId('handyman_id')->nullable()->constrained('handy_men')->onDelete('set null'); // Assigned handyman
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null'); // Admin/Staff who assigned the handyman
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin/Staff who assigned the handyman
            $table->string('title'); // Short title of the request
            $table->text('description'); // Detailed description
            $table->json('images')->nullable(); // JSON column to store image URLs
            $table->enum('status', ['pending','requested','assigned','in_progress','forApprove', 'completed', 'cancelled'])->default('pending'); // Status tracking
            $table->dateTime('requested_at')->useCurrent(); // When the request was created
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('assisted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->dateTime('completed_at')->nullable(); // When the request was completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};

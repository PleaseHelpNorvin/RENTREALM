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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // i am planning to put a reservation-code
            $table->foreignId('picked_room_id')->constrained('picked_room')->onDelete('cascade');
            $table->string('reservation_code');
            $table->decimal('amount', 10,2);
            $table->integer('person_count')->default(1);
            $table->json('reservation_payment_proof_url')->nullable();
            $table->enum('status', ['pending','confirmed'])->default('pending');
            $table->enum('payment_status', ['unpaid','paid'])->default('unpaid');
            $table->string('approved_by')->nullable();
            $table->timestamp('approval_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

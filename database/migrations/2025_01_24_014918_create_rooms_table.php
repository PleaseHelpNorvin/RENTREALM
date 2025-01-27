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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->text('room_picture_url')->nullable();
            $table->string('room_code');
            $table->string('description');
            $table->string('room_details');
            $table->string('category');
            $table->decimal('rent_price', 10, 2);
            $table->integer('capacity');
            $table->integer('current_occupants')->nullable();
            $table->integer('min_lease');
            $table->string('size'); // ex: 12ft x 12ft 
            $table->enum('status', ['available','rented','under_maintenance','full'])->default('available');
            $table->enum('unit_type', ['studio_unit','triplex_unit','alcove','loft_unit', 'shared_unit', 'micro_unit'])->default('studio_unit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

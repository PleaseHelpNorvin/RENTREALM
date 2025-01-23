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
            $table->string('room_code');
            $table->text('room_picture_url')->nullable();
            $table->string('description');
            $table->string('room_details');
            $table->string('category');
            $table->decimal('rent_price', 10, 2);
            $table->integer('capacity');
            $table->integer('current_occupants')->nullable();
            $table->integer('min_lease');
            $table->integer('size'); // ex: 12ft x 12ft 
            $table->enum('status', ['available','rented','under maintenance','full'])->default('available');
            $table->enum('unit type', ['studio unit','triplex unit','alcove','Loft unit', 'shared unit', 'micro-unit'])->default('available');
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

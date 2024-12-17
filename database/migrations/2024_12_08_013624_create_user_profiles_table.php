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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('profile_picture_url')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('social_media_links')->nullable();

            //address related column
            $table->text('address')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('barangay')->nullable();
            $table->string('zone')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            //id related column
            $table->string('driver_license_number')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('social_security_number')->nullable();

            $table->string('occupation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};

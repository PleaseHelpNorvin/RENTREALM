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
        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->onDelete('cascade');

            $table->string('agreement_code');
            
            $table->date('rent_start_date');
            $table->date('rent_end_date')->nullable();
            
            $table->integer('person_count')->nullable();
            $table->decimal('total_monthly_due', 10, 2);

            $table->longText('description')->nullable();
            $table->string('signature_png_string');
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};

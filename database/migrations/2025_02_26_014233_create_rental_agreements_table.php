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
            
            $table->decimal('rent_price', 10, 2);
            $table->integer('persons count', 10, 2)->nullable();

            $table->longText('description')->nullable();
            $table->longText('signature_svg_string');
            
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

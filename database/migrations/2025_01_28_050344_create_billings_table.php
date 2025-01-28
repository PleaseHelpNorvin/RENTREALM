<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    //  CREATE TABLE billings (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     tenant_id INT NOT NULL,
    //     due_date DATE NOT NULL,
    //     amount DECIMAL(10, 2) NOT NULL,
    //     status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    //     paid_date DATE NULL,
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    //     FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    // );
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};

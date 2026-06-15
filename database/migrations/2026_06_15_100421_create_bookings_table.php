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
        Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->string('booking_code')->unique();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
        $table->date('check_in');
        $table->date('check_out');
        $table->integer('total_nights');
        $table->integer('total_price');
        $table->string('payment_status')->default('pending'); // pending, paid, cancelled
        $table->string('status')->default('pending'); // pending, completed, cancelled
        $table->string('qr_code')->nullable();
        $table->string('pdf_receipt')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

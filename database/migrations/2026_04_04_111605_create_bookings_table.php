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
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('duration_days');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('daily_rate', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->json('pickup_location')->nullable();
            $table->json('dropoff_location')->nullable();
            $table->text('special_requests')->nullable();
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

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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color');
            $table->string('license_plate')->unique();
            $table->string('vin')->unique();
            $table->enum('type', ['sedan', 'suv', 'hatchback', 'sports', 'luxury', 'truck', 'van']);
            $table->enum('transmission', ['manual', 'automatic']);
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid']);
            $table->integer('seats');
            $table->integer('doors');
            $table->text('description');
            $table->decimal('daily_price', 10, 2);
            $table->decimal('weekly_price', 10, 2)->nullable();
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->string('location_address');
            $table->string('location_city');
            $table->decimal('location_latitude', 10, 8)->nullable();
            $table->decimal('location_longitude', 11, 8)->nullable();
            $table->json('features')->nullable();
            $table->json('availability')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'inactive'])->default('pending');
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->boolean('is_available')->default(true);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};

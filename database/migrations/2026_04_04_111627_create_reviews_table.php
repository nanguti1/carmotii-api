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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('moderation_note')->nullable();
            $table->timestamps();
            
            $table->unique(['car_id', 'user_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'cancelled', 'expired'])->default('active');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->integer('listings_used')->default(0);
            $table->integer('bookings_this_month')->default(0);
            $table->json('features_used')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};

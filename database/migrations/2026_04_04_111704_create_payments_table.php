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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('type', ['booking_payment', 'listing_fee', 'security_deposit', 'refund']);
            $table->enum('method', ['mpesa', 'card', 'bank_transfer']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('phone_number')->nullable();
            $table->string('mpesa_receipt')->nullable();
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

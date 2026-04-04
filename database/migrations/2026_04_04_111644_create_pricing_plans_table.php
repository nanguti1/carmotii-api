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
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['one_time', 'monthly', 'yearly'])->default('one_time');
            $table->json('features');
            $table->json('limitations')->nullable();
            $table->integer('max_listings')->default(1);
            $table->integer('max_bookings_per_month')->nullable();
            $table->boolean('priority_listing')->default(false);
            $table->boolean('featured_search')->default(false);
            $table->boolean('advanced_analytics')->default(false);
            $table->boolean('dedicated_support')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};

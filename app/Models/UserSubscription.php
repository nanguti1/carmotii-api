<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['status', 'starts_at', 'ends_at', 'cancelled_at', 'auto_renew', 'listings_used', 'bookings_this_month', 'features_used'])]
class UserSubscription extends Model
{
    use HasFactory;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'listings_used' => 'integer',
        'bookings_this_month' => 'integer',
        'features_used' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at->greaterThan(now()));
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->ends_at && $this->ends_at->lessThan(now()));
    }

    public function canListMoreCars(): bool
    {
        $plan = $this->pricingPlan;
        return $this->isActive() && 
               (!$plan->max_listings || $this->listings_used < $plan->max_listings);
    }

    public function canAcceptMoreBookings(): bool
    {
        $plan = $this->pricingPlan;
        return $this->isActive() && 
               (!$plan->max_bookings_per_month || $this->bookings_this_month < $plan->max_bookings_per_month);
    }

    public function hasFeature(string $feature): bool
    {
        $plan = $this->pricingPlan;
        return $this->isActive() && $plan->hasFeature($feature);
    }
}

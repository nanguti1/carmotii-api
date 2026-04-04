<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'price', 'billing_cycle', 'features', 'limitations', 'max_listings', 'max_bookings_per_month', 'priority_listing', 'featured_search', 'advanced_analytics', 'dedicated_support', 'api_access', 'custom_branding', 'is_active', 'sort_order'])]
class PricingPlan extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limitations' => 'array',
        'max_listings' => 'integer',
        'max_bookings_per_month' => 'integer',
        'priority_listing' => 'boolean',
        'featured_search' => 'boolean',
        'advanced_analytics' => 'boolean',
        'dedicated_support' => 'boolean',
        'api_access' => 'boolean',
        'custom_branding' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'KES ' . number_format($this->price, 2);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}

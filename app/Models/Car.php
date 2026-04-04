<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['make', 'model', 'year', 'color', 'license_plate', 'vin', 'type', 'transmission', 'fuel_type', 'seats', 'doors', 'description', 'daily_price', 'weekly_price', 'monthly_price', 'location_address', 'location_city', 'location_latitude', 'location_longitude', 'features', 'availability', 'status', 'rating_average', 'rating_count', 'booking_count', 'is_available', 'rejection_reason'])]
class Car extends Model
{
    use HasFactory;

    protected $casts = [
        'year' => 'integer',
        'seats' => 'integer',
        'doors' => 'integer',
        'daily_price' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'location_latitude' => 'decimal:8',
        'location_longitude' => 'decimal:8',
        'features' => 'array',
        'availability' => 'array',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'booking_count' => 'integer',
        'is_available' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(CarImage::class)->where('is_primary', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        $primaryImage = $this->primaryImage->first();
        return $primaryImage ? asset('storage/' . $primaryImage->image_path) : '/images/placeholder-car.jpg';
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('status', 'approved');
    }

    public function scopeInLocation($query, $location)
    {
        return $query->where('location_city', 'like', "%{$location}%");
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByTransmission($query, $transmission)
    {
        return $query->where('transmission', $transmission);
    }

    public function scopeByFuelType($query, $fuelType)
    {
        return $query->where('fuel_type', $fuelType);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('daily_price', [$min, $max]);
    }

    public function updateRating()
    {
        $this->rating_average = $this->reviews()->where('status', 'approved')->avg('rating') ?? 0;
        $this->rating_count = $this->reviews()->where('status', 'approved')->count();
        $this->save();
    }

    public function isAvailableForDates($startDate, $endDate): bool
    {
        return !$this->bookings()
            ->whereIn('status', ['confirmed', 'active'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();
    }
}

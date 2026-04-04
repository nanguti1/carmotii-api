<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['start_date', 'end_date', 'duration_days', 'total_amount', 'daily_rate', 'status', 'cancellation_reason', 'cancelled_at', 'confirmed_at', 'completed_at', 'security_deposit', 'pickup_location', 'dropoff_location', 'special_requests'])]
class Booking extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_days' => 'integer',
        'total_amount' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'pickup_location' => 'array',
        'dropoff_location' => 'array',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): BelongsTo
    {
        return $this->hasOne(Review::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['confirmed', 'active']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && 
               $this->start_date->greaterThan(now()->addHours(24));
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'completed' && 
               !$this->review && 
               $this->end_date->lessThan(now());
    }
}

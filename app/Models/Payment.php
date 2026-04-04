<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'type', 'method', 'status', 'amount', 'currency', 'phone_number', 'mpesa_receipt', 'metadata', 'failure_reason', 'processed_at', 'refunded_at'])]
class Payment extends Model
{
    use HasFactory;

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->hasOne(UserSubscription::class, 'payment_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }
}

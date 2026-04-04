<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rating', 'comment', 'status', 'moderation_note'])]
class Review extends Model
{
    use HasFactory;

    protected $casts = [
        'rating' => 'integer',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    protected static function booted()
    {
        static::updated(function ($review) {
            if ($review->wasChanged('status') && $review->status === 'approved') {
                $review->car->updateRating();
            }
        });
    }
}

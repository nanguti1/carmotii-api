<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['image_path', 'image_url', 'sort_order', 'is_primary'])]
class CarImage extends Model
{
    use HasFactory;

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}

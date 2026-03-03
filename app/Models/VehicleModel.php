<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = ['brand_id', 'name', 'description','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleModelImage::class, 'model_id');
    }

    public function types(): HasMany
    {
        return $this->hasMany(VehicleType::class, 'model_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(VehicleDetail::class, 'model_id');
    }
}
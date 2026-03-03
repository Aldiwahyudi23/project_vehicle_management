<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'country','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Scope active brands
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(VehicleDetail::class, 'brand_id');
    }

      /**
     * Get active types only
     */
    public function activeTypes(): HasMany
    {
        return $this->types()->where('is_active', true);
    }

    /**
     * Get primary image
     */
    public function primaryImage()
    {
        return $this->images()->where('is_primary', true)->first();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleFeature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Scope active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function details(): BelongsToMany
    {
        return $this->belongsToMany(VehicleDetail::class, 'vehicle_detail_features', 'feature_id', 'vehicle_detail_id');
    }
}
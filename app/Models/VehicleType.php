<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $table = 'vehicle_types';

    protected $fillable = [
        'model_id',
        'type_body_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: hanya type aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke VehicleModel
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(
            VehicleModel::class,
            'model_id'
        );
    }

    /**
     * Relasi ke Type Body (SUV, Pickup, Box, dll)
     */
    public function typeBody(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\VehicleTypeBody::class,
            'type_body_id'
        );
    }

    /**
     * Relasi ke Vehicle Detail
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            VehicleDetail::class,
            'type_id'
        );
    }

}

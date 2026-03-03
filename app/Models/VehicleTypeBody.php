<?php

namespace App\Models;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleTypeBody extends Model
{
    use HasFactory;

    // protected $table = 'vehicle_type_bodies';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: hanya data aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke VehicleType
     * (kalau VehicleType punya type_body_id)
     */
    public function vehicleTypes()
    {
        return $this->hasMany(
            VehicleType::class,
            'type_body_id'
        );
    }
}

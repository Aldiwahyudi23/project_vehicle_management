<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDetailFeature extends Model
{
    use HasFactory;

    protected $table = 'vehicle_detail_features';

    protected $fillable = [
        'vehicle_detail_id',
        'feature_id',
    ];

    /**
     * Get the vehicle detail
     */
    public function vehicleDetail(): BelongsTo
    {
        return $this->belongsTo(VehicleDetail::class);
    }

    /**
     * Get the feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VehicleFeature::class);
    }
}
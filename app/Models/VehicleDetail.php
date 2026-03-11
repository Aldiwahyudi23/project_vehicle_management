<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_details';

    
    protected $fillable = [
        'brand_id', 'model_id', 'type_id', 'year', 'cc', 'fuel_type',
        'transmission_id', 'engine_type', 'origin_id', 'generation',
        'market_period', 'description', 'image_path','is_active',
        'specifications',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'specifications' => 'array',
    ];

    /**
     * Scope active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(TransmissionType::class, 'transmission_id');
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(VehicleOrigin::class, 'origin_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(VehicleFeature::class, 'vehicle_detail_features', 'vehicle_detail_id', 'feature_id');
    }

    /**
 * Get formatted CC (example: 1496 -> 1.5L)
 */
public function getFormatCcAttribute(): ?string
{
    if (!$this->cc) {
        return null;
    }

    return number_format($this->cc / 1000, 1) . 'L';
}

/**
 * Get full vehicle name
 */
public function getFullNameAttribute(): string
{
    return collect([
        $this->brand->name ?? null,
        $this->model->name ?? null,
        $this->type->name ?? null,
        $this->format_cc, // pakai accessor
        $this->transmission->name ?? null,
        $this->year ?? null,
        $this->fuel->name ?? null,
    ])
    ->filter()
    ->implode(' ');
}

        /**
     * Get specification summary
     */
    public function getSpecificationSummary(): string
    {
        $specs = [];
        
        if ($this->cc) {
            $specs[] = $this->cc . 'cc';
        }
        
        if ($this->fuel_type) {
            $specs[] = ucfirst($this->fuel_type);
        }
        
        if ($this->transmission) {
            $specs[] = $this->transmission->name;
        }
        
        return implode(' • ', $specs);
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        
        return asset('storage/' . $this->image_path);
    }

    /**
     * Scope for filtering
     */
    public function scopeFilter($query, array $filters)
    {
        return $query->when($filters['brand'] ?? false, function ($query, $brand) {
            $query->whereHas('brand', function ($q) use ($brand) {
                $q->where('id', $brand)->orWhere('slug', $brand);
            });
        })
        ->when($filters['model'] ?? false, function ($query, $model) {
            $query->whereHas('model', function ($q) use ($model) {
                $q->where('id', $model)->orWhere('slug', $model);
            });
        })
        ->when($filters['year_from'] ?? false, function ($query, $year) {
            $query->where('year', '>=', $year);
        })
        ->when($filters['year_to'] ?? false, function ($query, $year) {
            $query->where('year', '<=', $year);
        })
        ->when($filters['fuel_type'] ?? false, function ($query, $fuelType) {
            $query->where('fuel_type', $fuelType);
        })
        ->when($filters['min_cc'] ?? false, function ($query, $cc) {
            $query->where('cc', '>=', $cc);
        })
        ->when($filters['max_cc'] ?? false, function ($query, $cc) {
            $query->where('cc', '<=', $cc);
        });
    }

    public function getSpecificationSummaryAttribute(): string
    {
        return collect([
            $this->engine_type,
            $this->cc ? $this->cc . ' cc' : null,
            ucfirst(str_replace('_', ' ', $this->fuel_type)),
            optional($this->transmission)->name,
        ])->filter()->implode(' • ');
    }

    /**
     * Helper: ambil attribute tertentu (aman)
     */
    public function getCustomSpecificationsValue(string $key, $default = null)
    {
        return data_get($this->specifications, $key, $default);
    }

}
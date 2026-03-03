<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'model_id' => $this->model_id,
            'type_id' => $this->type_id,
            'year' => $this->year,
            'cc' => $this->cc,
            'fuel_type' => $this->fuel_type,
            'transmission_id' => $this->transmission_id,
            'engine_type' => $this->engine_type,
            'origin_id' => $this->origin_id,
            'generation' => $this->generation,
            'market_period' => $this->market_period,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'full_name' => $this->full_name,
            'specification_summary' => $this->specification_summary,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships (conditionally loaded)
            'brand' => new VehicleBrandResource($this->whenLoaded('brand')),
            'model' => new VehicleModelResource($this->whenLoaded('model')),
            'type' => new VehicleTypeResource($this->whenLoaded('type')),
            'transmission' => new TransmissionTypeResource($this->whenLoaded('transmission')),
            'origin' => new VehicleOriginResource($this->whenLoaded('origin')),
            'features' => VehicleFeatureResource::collection($this->whenLoaded('features')),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDetailFeatureResource extends JsonResource
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
            'vehicle_detail_id' => $this->vehicle_detail_id,
            'feature_id' => $this->feature_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships (conditionally loaded)
            'vehicle_detail' => new VehicleDetailResource($this->whenLoaded('vehicleDetail')),
            'feature' => new VehicleFeatureResource($this->whenLoaded('feature')),
        ];
    }
}
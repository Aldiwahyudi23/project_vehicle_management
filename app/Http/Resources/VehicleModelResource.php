<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleModelResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'types_count' => $this->whenLoaded('types', function () {
                return $this->types->count();
            }, 0),
            'details_count' => $this->whenLoaded('details', function () {
                return $this->details->count();
            }, 0),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships (conditionally loaded)
            'brand' => new VehicleBrandResource($this->whenLoaded('brand')),
            'types' => VehicleTypeResource::collection($this->whenLoaded('types')),
            'details' => VehicleDetailResource::collection($this->whenLoaded('details')),
            'images' => VehicleModelImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTypeResource extends JsonResource
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
            'model_id' => $this->model_id,
            'name' => $this->name,
            'year_start' => $this->year_start,
            'year_end' => $this->year_end,
            'body_type' => $this->body_type,
            'is_active' => $this->is_active,
            'details_count' => $this->whenLoaded('details', function () {
                return $this->details->count();
            }, 0),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships (conditionally loaded)
            'model' => new VehicleModelResource($this->whenLoaded('model')),
            'details' => VehicleDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
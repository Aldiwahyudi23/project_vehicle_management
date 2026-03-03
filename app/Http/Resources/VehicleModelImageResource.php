<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleModelImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fullUrl = Storage::disk('public')->url($this->image_path);
        $thumbnailUrl = Storage::disk('public')->exists(dirname($this->image_path) . '/thumbs/' . basename($this->image_path))
            ? Storage::disk('public')->url(dirname($this->image_path) . '/thumbs/' . basename($this->image_path))
            : null;

        return [
            'id' => $this->id,
            'model_id' => $this->model_id,
            'image_path' => $this->image_path,
            'image_url' => $fullUrl,
            'thumbnail_url' => $thumbnailUrl,
            'is_primary' => $this->is_primary,
            'angle' => $this->angle,
            'caption' => $this->caption,
            'order' => $this->order,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships (conditionally loaded)
            'model' => new VehicleModelResource($this->whenLoaded('model')),
        ];
    }
}
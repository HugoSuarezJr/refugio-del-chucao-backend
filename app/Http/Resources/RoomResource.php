<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'room_id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'bedType' => $this->bed_type,
            'capacity' => $this->capacity,
            'size' => $this->size,
            'pricePerNight' => $this->base_nightly_rate,
            'currency' => $this->currency,
            'amenities' => $this->amenities ?? [],
            'kitchenAmenities' => $this->kitchen_amenities ?? [],
            'bathroomAmenities' => $this->bathroom_amenities ?? [],
            'otherAmenities' => $this->other_amenities ?? [],
            'policies' => $this->policies ?? [],
            'highlights' => $this->highlights ?? [],
            'mainImage' => $this->main_image_url,
            'images' => $this->gallery_images ?? [],
            'isActive' => $this->is_active,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Storage;
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
            'mainImage' => $this->resolveRoomImageUrl($this->main_image_url),
            'images' => collect($this->gallery_images ?? [])
                ->map(fn ($path) => $this->resolveRoomImageUrl($path))
                ->all(),
            'isActive' => $this->is_active,
        ];
    }

    protected function resolveRoomImageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return $path;
        }

        if (
            str_starts_with($path, '/images/')
            || str_starts_with($path, '/storage/')
            || preg_match('/^(https?:)?\/\//', $path)
            || str_starts_with($path, 'data:')
            || str_starts_with($path, 'blob:')
        ) {
            return $path;
        }

        $disk = (string) config('booking.room_image_disk', config('filesystems.default', 'public'));

        return Storage::disk($disk)->url($path);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room?->slug ?? $this->room()->value('slug'),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'source' => $this->source?->value,
            'external_reference' => $this->external_reference,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

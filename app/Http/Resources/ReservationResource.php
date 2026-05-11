<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roomSlug = $this->resource->room?->slug ?? $this->resource->room()->value('slug');

        return [
            'id' => $this->id,
            'reservation_code' => $this->reservation_code,
            'room_id' => $roomSlug,
            'guest_name' => $this->guest_name,
            'guest_email' => $this->guest_email,
            'guest_phone' => $this->guest_phone,
            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),
            'number_of_guests' => $this->number_of_guests,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'subtotal' => $this->subtotal,
            'fees_total' => $this->fees_total,
            'total' => $this->total,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'source' => $this->source,
            'mercado_pago_preference_id' => $this->mercado_pago_preference_id,
            'mercado_pago_payment_id' => $this->mercado_pago_payment_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'pricing' => $this->pricing_breakdown,
            'room' => $this->whenLoaded('room', fn () => new RoomResource($this->room)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

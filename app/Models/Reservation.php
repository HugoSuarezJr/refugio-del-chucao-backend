<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'reservation_code',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in',
        'check_out',
        'number_of_guests',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'fees_total',
        'total',
        'pricing_breakdown',
        'notes',
        'source',
        'mercado_pago_preference_id',
        'mercado_pago_payment_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'cancelled_at' => 'datetime',
            'pricing_breakdown' => 'array',
            'status' => ReservationStatus::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        $pendingCutoff = CarbonImmutable::now()->subMinutes((int) config('booking.pending_payment_hold_minutes', 30));

        return $query->where(function (Builder $query) use ($pendingCutoff) {
            $query->where('status', ReservationStatus::Confirmed->value)
                ->orWhere(function (Builder $query) use ($pendingCutoff) {
                    $query->where('status', ReservationStatus::Pending->value)
                        ->where('created_at', '>=', $pendingCutoff);
                });
        });
    }
}

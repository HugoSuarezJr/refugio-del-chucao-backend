<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'bed_type',
        'capacity',
        'size',
        'base_nightly_rate',
        'currency',
        'amenities',
        'kitchen_amenities',
        'bathroom_amenities',
        'other_amenities',
        'policies',
        'highlights',
        'main_image_url',
        'gallery_images',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'kitchen_amenities' => 'array',
            'bathroom_amenities' => 'array',
            'other_amenities' => 'array',
            'policies' => 'array',
            'highlights' => 'array',
            'gallery_images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(RoomBlock::class);
    }

    public function seasonalRates(): HasMany
    {
        return $this->hasMany(SeasonalRate::class);
    }
}

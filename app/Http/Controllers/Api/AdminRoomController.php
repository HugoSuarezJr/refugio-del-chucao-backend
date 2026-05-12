<?php

namespace App\Http\Controllers\Api;

use App\Enums\BlockSource;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomBlockRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\RoomBlockResource;
use App\Http\Resources\RoomResource;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminRoomController extends Controller
{
    public function update(UpdateRoomRequest $request, Room $room): RoomResource
    {
        $validated = $request->validated();
        $mainImagePath = $request->hasFile('mainImageFile')
            ? $this->storeRoomImage($request->file('mainImageFile'))
            : $this->normalizeStoredImagePath($validated['mainImage']);

        $galleryImages = collect($validated['images'] ?? [])
            ->map(fn ($path) => $this->normalizeStoredImagePath($path))
            ->filter()
            ->values()
            ->all();

        if ($request->hasFile('imagesFiles')) {
            $galleryImages = [
                ...$galleryImages,
                ...collect($request->file('imagesFiles'))
                    ->map(fn ($file) => $this->storeRoomImage($file))
                    ->all(),
            ];
        }

        if (count($galleryImages) === 0) {
            $galleryImages = [$mainImagePath];
        }

        $room->fill([
            'name' => $validated['name'],
            'subtitle' => $validated['subtitle'] ?? '',
            'description' => $validated['description'] ?? '',
            'bed_type' => $validated['bedType'],
            'capacity' => $validated['capacity'],
            'size' => $validated['size'] ?? null,
            'base_nightly_rate' => $validated['pricePerNight'],
            'currency' => strtoupper($validated['currency'] ?? $room->currency ?? config('booking.currency')),
            'amenities' => $validated['amenities'] ?? [],
            'kitchen_amenities' => $validated['kitchenAmenities'] ?? [],
            'bathroom_amenities' => $validated['bathroomAmenities'] ?? [],
            'other_amenities' => $validated['otherAmenities'] ?? [],
            'policies' => $validated['policies'] ?? [],
            'highlights' => $validated['highlights'] ?? [],
            'main_image_url' => $mainImagePath,
            'gallery_images' => $galleryImages,
            'is_active' => $validated['isActive'],
            'sort_order' => $validated['sortOrder'] ?? $room->sort_order,
        ]);

        $room->save();

        return new RoomResource($room->fresh());
    }

    protected function storeRoomImage(\Illuminate\Http\UploadedFile $file): string
    {
        $disk = (string) config('booking.room_image_disk', config('filesystems.default', 'public'));
        $storedPath = $file->store('rooms', $disk);

        return Storage::disk($disk)->url($storedPath);
    }

    protected function normalizeStoredImagePath(?string $path): ?string
    {
        if (! $path) {
            return $path;
        }

        if (str_starts_with($path, '/storage/') || str_starts_with($path, '/images/')) {
            return $path;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);

        if (is_string($parsedPath) && ($parsedPath !== '')) {
            if (str_starts_with($parsedPath, '/storage/') || str_starts_with($parsedPath, '/images/')) {
                return $parsedPath;
            }
        }

        return $path;
    }

    public function schedule(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $startsAt = isset($validated['starts_at'])
            ? Carbon::parse($validated['starts_at'])->startOfDay()
            : Carbon::today()->startOfDay();

        $endsAt = isset($validated['ends_at'])
            ? Carbon::parse($validated['ends_at'])->startOfDay()
            : $startsAt->copy()->addMonths((int) ($validated['months'] ?? 12));

        $reservations = Reservation::query()
            ->where('room_id', $room->id)
            ->whereIn('status', ReservationStatus::blocking())
            ->whereDate('check_in', '<', $endsAt->toDateString())
            ->whereDate('check_out', '>', $startsAt->toDateString())
            ->orderBy('check_in')
            ->get();

        $blocks = RoomBlock::query()
            ->where('room_id', $room->id)
            ->whereDate('start_date', '<', $endsAt->toDateString())
            ->whereDate('end_date', '>', $startsAt->toDateString())
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'room' => new RoomResource($room),
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'reservations' => ReservationResource::collection($reservations),
            'blocks' => RoomBlockResource::collection($blocks),
        ]);
    }

    public function storeBlock(StoreRoomBlockRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();

        $block = RoomBlock::query()->create([
            'room_id' => $room->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => $validated['source'] ?? BlockSource::Manual,
        ]);

        return (new RoomBlockResource($block->load('room')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateBlock(StoreRoomBlockRequest $request, RoomBlock $roomBlock): RoomBlockResource
    {
        $validated = $request->validated();

        $roomBlock->fill([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => $validated['source'] ?? $roomBlock->source ?? BlockSource::Manual,
        ]);

        $roomBlock->save();

        return new RoomBlockResource($roomBlock->fresh('room'));
    }

    public function destroyBlock(RoomBlock $roomBlock): JsonResponse
    {
        $roomBlock->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }
}

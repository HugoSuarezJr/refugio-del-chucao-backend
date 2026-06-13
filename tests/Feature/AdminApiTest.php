<?php

namespace Tests\Feature;

use App\Enums\BlockSource;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\AdminApiToken;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\SeasonalRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;
    protected array $adminHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoomSeeder::class);
        $this->room = Room::query()->where('slug', 'martin-pescador')->firstOrFail();
        $this->adminHeaders = $this->authenticateAdmin();
    }

    protected function authenticateAdmin(): array
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $plainTextToken = str_repeat('a', 64);

        AdminApiToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        return [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    public function test_it_updates_a_room_from_the_admin_api(): void
    {
        $response = $this->withHeaders($this->adminHeaders)->patchJson("/api/admin/rooms/{$this->room->slug}", [
            'name' => 'Martín Pescador Superior',
            'subtitle' => 'Vista abierta al lago',
            'description' => 'Descripción actualizada',
            'bedType' => '1 cama king',
            'capacity' => 3,
            'size' => '430 pies²',
            'pricePerNight' => 125000,
            'currency' => 'CLP',
            'mainImage' => '/images/rooms/martin_pescador_1.jpeg',
            'images' => ['/images/rooms/martin_pescador_1.jpeg', '/images/rooms/martin_pescador_2.jpeg'],
            'amenities' => [
                ['icon' => 'Bed', 'label' => 'Cama king'],
            ],
            'kitchenAmenities' => ['Refrigerador'],
            'bathroomAmenities' => ['Ducha'],
            'otherAmenities' => ['Balcón'],
            'policies' => ['No fumar'],
            'highlights' => ['Vista al lago'],
            'isActive' => true,
            'sortOrder' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Martín Pescador Superior')
            ->assertJsonPath('pricePerNight', 125000)
            ->assertJsonPath('capacity', 3);

        $this->assertDatabaseHas('rooms', [
            'id' => $this->room->id,
            'name' => 'Martín Pescador Superior',
            'base_nightly_rate' => 125000,
            'capacity' => 3,
        ]);
    }

    public function test_it_appends_uploaded_gallery_images_instead_of_replacing_existing_ones(): void
    {
        Storage::fake('public');

        $response = $this->withHeaders($this->adminHeaders)->patch("/api/admin/rooms/{$this->room->slug}", [
            'name' => $this->room->name,
            'subtitle' => $this->room->subtitle,
            'description' => $this->room->description,
            'bedType' => $this->room->bed_type,
            'capacity' => $this->room->capacity,
            'size' => $this->room->size,
            'pricePerNight' => $this->room->base_nightly_rate,
            'currency' => $this->room->currency,
            'mainImage' => $this->room->main_image_url,
            'images' => json_encode([$this->room->gallery_images[0]]),
            'amenities' => json_encode($this->room->amenities ?? []),
            'kitchenAmenities' => json_encode($this->room->kitchen_amenities ?? []),
            'bathroomAmenities' => json_encode($this->room->bathroom_amenities ?? []),
            'otherAmenities' => json_encode($this->room->other_amenities ?? []),
            'policies' => json_encode($this->room->policies ?? []),
            'highlights' => json_encode($this->room->highlights ?? []),
            'isActive' => $this->room->is_active ? 'true' : 'false',
            'imagesFiles' => [
                UploadedFile::fake()->image('gallery-new.jpg'),
            ],
        ]);

        $response->assertOk();

        $galleryImages = $response->json('images');

        $this->assertCount(2, $galleryImages);
        $this->assertSame($this->room->gallery_images[0], $galleryImages[0]);
        $this->assertStringStartsWith('/storage/rooms/', $galleryImages[1]);
    }

    public function test_it_can_create_and_delete_a_room_block_from_the_admin_api(): void
    {
        $createResponse = $this->withHeaders($this->adminHeaders)->postJson("/api/admin/rooms/{$this->room->slug}/blocks", [
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-13',
            'reason' => 'Mantenimiento',
            'notes' => 'Cambio de ventanas',
            'source' => BlockSource::Maintenance->value,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('reason', 'Mantenimiento')
            ->assertJsonPath('source', BlockSource::Maintenance->value);

        $blockId = $createResponse->json('id');

        $updateResponse = $this->withHeaders($this->adminHeaders)->patchJson("/api/admin/blocks/{$blockId}", [
            'start_date' => '2026-11-11',
            'end_date' => '2026-11-14',
            'reason' => 'Mantenimiento mayor',
            'notes' => 'Cambio de ventanas y pintura',
            'source' => BlockSource::Owner->value,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('reason', 'Mantenimiento mayor')
            ->assertJsonPath('source', BlockSource::Owner->value);

        $this->assertDatabaseHas('room_blocks', [
            'id' => $blockId,
            'room_id' => $this->room->id,
            'reason' => 'Mantenimiento mayor',
        ]);

        $deleteResponse = $this->withHeaders($this->adminHeaders)->deleteJson("/api/admin/blocks/{$blockId}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('room_blocks', [
            'id' => $blockId,
        ]);
    }

    public function test_it_returns_room_schedule_with_reservations_and_blocks(): void
    {
        Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-ADMIN01',
            'guest_name' => 'Panel Admin',
            'guest_email' => 'admin@example.com',
            'check_in' => '2026-10-05',
            'check_out' => '2026-10-08',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'currency' => 'CLP',
            'subtotal' => 300000,
            'fees_total' => 0,
            'total' => 300000,
            'pricing_breakdown' => ['nights' => 3],
            'source' => 'website',
        ]);

        RoomBlock::query()->create([
            'room_id' => $this->room->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'reason' => 'Uso interno',
            'source' => BlockSource::Owner,
        ]);

        $response = $this->withHeaders($this->adminHeaders)->getJson("/api/admin/rooms/{$this->room->slug}/schedule?starts_at=2026-10-01&ends_at=2026-10-31");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonCount(1, 'blocks')
            ->assertJsonPath('reservations.0.reservation_code', 'RDC-ADMIN01')
            ->assertJsonPath('blocks.0.reason', 'Uso interno');
    }

    public function test_it_can_create_update_and_delete_reservations_from_the_admin_api(): void
    {
        $createResponse = $this->withHeaders($this->adminHeaders)->postJson("/api/admin/rooms/{$this->room->slug}/reservations", [
            'guest_name' => 'Reserva Admin',
            'guest_email' => 'admin-reserva@example.com',
            'guest_phone' => '+56911111111',
            'check_in' => '2026-12-05',
            'check_out' => '2026-12-08',
            'number_of_guests' => 2,
            'notes' => 'Check-in tardío',
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Pending->value,
            'source' => 'admin',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('guest_name', 'Reserva Admin')
            ->assertJsonPath('status', ReservationStatus::Confirmed->value)
            ->assertJsonPath('total', 357000)
            ->assertJsonPath('pricing.fee_total', 57000);

        $reservationId = $createResponse->json('id');

        $updateResponse = $this->withHeaders($this->adminHeaders)->patchJson("/api/admin/reservations/{$reservationId}", [
            'guest_name' => 'Reserva Admin Editada',
            'guest_email' => 'admin-reserva@example.com',
            'guest_phone' => '+56922222222',
            'check_in' => '2026-12-06',
            'check_out' => '2026-12-09',
            'number_of_guests' => 2,
            'notes' => 'Actualizada',
            'status' => ReservationStatus::Pending->value,
            'payment_status' => PaymentStatus::Paid->value,
            'source' => 'admin',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('guest_name', 'Reserva Admin Editada')
            ->assertJsonPath('payment_status', PaymentStatus::Paid->value)
            ->assertJsonPath('total', 357000)
            ->assertJsonPath('pricing.fee_total', 57000);

        $deleteResponse = $this->withHeaders($this->adminHeaders)->deleteJson("/api/admin/reservations/{$reservationId}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('reservations', [
            'id' => $reservationId,
        ]);
    }

    public function test_it_logs_in_and_returns_the_authenticated_admin_user(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse = $this->postJson('/api/admin/login', [
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $token = $loginResponse->json('token');

        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/admin/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('user.email', 'owner@example.com');
    }

    public function test_it_rejects_unauthenticated_admin_requests(): void
    {
        $response = $this->getJson("/api/admin/rooms/{$this->room->slug}/schedule");

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_it_can_manage_room_seasonal_rates_from_the_admin_api(): void
    {
        $createResponse = $this->withHeaders($this->adminHeaders)->postJson("/api/admin/rooms/{$this->room->slug}/seasonal-rates", [
            'name' => 'Temporada verano',
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-31',
            'nightly_rate' => 145000,
            'currency' => 'CLP',
            'priority' => 10,
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Temporada verano')
            ->assertJsonPath('nightly_rate', 145000);

        $rateId = $createResponse->json('id');

        $listResponse = $this->withHeaders($this->adminHeaders)->getJson("/api/admin/rooms/{$this->room->slug}/seasonal-rates");

        $listResponse
            ->assertOk()
            ->assertJsonFragment([
                'id' => $rateId,
                'name' => 'Temporada verano',
            ]);

        $updateResponse = $this->withHeaders($this->adminHeaders)->patchJson("/api/admin/seasonal-rates/{$rateId}", [
            'name' => 'Temporada verano premium',
            'start_date' => '2026-12-15',
            'end_date' => '2027-03-15',
            'nightly_rate' => 155000,
            'currency' => 'CLP',
            'priority' => 20,
            'is_active' => false,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('name', 'Temporada verano premium')
            ->assertJsonPath('is_active', false);

        $deleteResponse = $this->withHeaders($this->adminHeaders)->deleteJson("/api/admin/seasonal-rates/{$rateId}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('seasonal_rates', [
            'id' => $rateId,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Mail\AdminReservationPaidMail;
use App\Mail\GuestReservationConfirmedMail;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DebugIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoomSeeder::class);
        $this->room = Room::query()->where('slug', 'martin-pescador')->firstOrFail();
        config(['app.url' => 'https://api.refugiodelchucao.cl']);
        config(['mail.default' => 'smtp']);
        config(['mail.from.address' => 'reservas@refugiodelchucao.cl']);
        config(['booking.admin_notification_email' => 'admin@example.com']);
        config(['services.stripe.secret_key' => 'sk_test_123']);
        config(['services.stripe.publishable_key' => 'pk_test_123']);
        config(['services.stripe.webhook_secret' => 'whsec_123']);
        $_ENV['DEBUG_INTEGRATION_TOKEN'] = 'debug-token';
        $_SERVER['DEBUG_INTEGRATION_TOKEN'] = 'debug-token';
    }

    public function test_debug_status_requires_token(): void
    {
        $this->getJson('/api/debug/integrations')
            ->assertNotFound();
    }

    public function test_debug_status_returns_runtime_configuration(): void
    {
        $this->getJson('/api/debug/integrations?token=debug-token')
            ->assertOk()
            ->assertJsonPath('app_url', 'https://api.refugiodelchucao.cl')
            ->assertJsonPath('mail_mailer', 'smtp')
            ->assertJsonPath('stripe_webhook_secret_configured', true);
    }

    public function test_debug_mark_paid_sends_emails_once(): void
    {
        Mail::fake();

        $reservation = Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-DEBUG01',
            'guest_name' => 'Debug Guest',
            'guest_email' => 'debug@example.com',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'CLP',
            'subtotal' => 200000,
            'fees_total' => 0,
            'total' => 200000,
            'pricing_breakdown' => ['nights' => 2],
            'source' => 'stripe_checkout',
        ]);

        $this->postJson('/api/debug/reservations/RDC-DEBUG01/mark-paid?token=debug-token')
            ->assertOk()
            ->assertJsonPath('payment_status', 'paid')
            ->assertJsonPath('emails_attempted', true);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        Mail::assertSent(GuestReservationConfirmedMail::class, 1);
        Mail::assertSent(AdminReservationPaidMail::class, 1);
    }

    public function test_debug_recent_reservations_returns_latest_codes(): void
    {
        Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-LIST01',
            'guest_name' => 'Debug Guest',
            'guest_email' => 'debug@example.com',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'CLP',
            'subtotal' => 200000,
            'fees_total' => 0,
            'total' => 200000,
            'pricing_breakdown' => ['nights' => 2],
            'source' => 'stripe_checkout',
        ]);

        $this->getJson('/api/debug/reservations?token=debug-token')
            ->assertOk()
            ->assertJsonPath('reservations.0.reservation_code', 'RDC-LIST01')
            ->assertJsonPath('reservations.0.payment_status', 'pending');
    }
}

<?php

namespace Tests\Feature;

use App\Mail\AdminReservationPaidMail;
use App\Mail\GuestReservationConfirmedMail;
use App\Enums\BlockSource;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\SeasonalRate;
use App\Services\StripeCheckoutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Event;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoomSeeder::class);
        $this->room = Room::query()->where('slug', 'martin-pescador')->firstOrFail();
    }

    public function test_it_returns_room_availability_with_conflicts(): void
    {
        Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-TEST01',
            'guest_name' => 'Reserva Demo',
            'guest_email' => 'demo@example.com',
            'check_in' => '2026-05-10',
            'check_out' => '2026-05-13',
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

        $response = $this->getJson("/api/rooms/{$this->room->slug}/availability?check_in=2026-05-11&check_out=2026-05-14");

        $response
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonCount(1, 'conflicts')
            ->assertJsonPath('conflicts.0.type', 'reservation');
    }

    public function test_it_returns_room_calendar_conflicts_for_a_window(): void
    {
        Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-CAL01',
            'guest_name' => 'Reserva Calendario',
            'guest_email' => 'calendar@example.com',
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-13',
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
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-22',
            'reason' => 'Deep cleaning',
            'source' => BlockSource::Maintenance,
        ]);

        $response = $this->getJson("/api/rooms/{$this->room->slug}/calendar?starts_at=2026-06-01&ends_at=2026-07-01");

        $response
            ->assertOk()
            ->assertJsonPath('starts_at', '2026-06-01')
            ->assertJsonPath('ends_at', '2026-07-01')
            ->assertJsonCount(2, 'conflicts')
            ->assertJsonPath('conflicts.0.type', 'reservation')
            ->assertJsonPath('conflicts.1.type', 'block');
    }

    public function test_it_calculates_seasonal_pricing_night_by_night(): void
    {
        SeasonalRate::query()->create([
            'room_id' => $this->room->id,
            'name' => 'Temporada alta diciembre-marzo',
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-31',
            'nightly_rate' => 120000,
            'currency' => 'CLP',
            'priority' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/pricing/calculate', [
            'room_id' => $this->room->slug,
            'check_in' => '2026-11-30',
            'check_out' => '2026-12-03',
            'number_of_guests' => 2,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('pricing.nights', 3)
            ->assertJsonPath('pricing.nightly_rates.0.amount', 100000)
            ->assertJsonPath('pricing.nightly_rates.1.amount', 120000)
            ->assertJsonPath('pricing.total', 340000);
    }

    public function test_it_creates_a_pending_reservation_when_available(): void
    {
        $response = $this->postJson('/api/reservations', [
            'room_id' => $this->room->slug,
            'guest_name' => 'Hugo Suarez',
            'guest_email' => 'hugo@example.com',
            'guest_phone' => '+56912345678',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'notes' => 'Late arrival',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('room_id', $this->room->slug)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('payment_status', 'unpaid')
            ->assertJsonPath('total', 300000);
    }

    public function test_it_creates_a_checkout_session_for_a_reservation(): void
    {
        $stripeCheckoutService = Mockery::mock(StripeCheckoutService::class);
        $stripeCheckoutService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.test/session/cs_test_123',
            ]));

        $this->app->instance(StripeCheckoutService::class, $stripeCheckoutService);

        $response = $this->postJson('/api/stripe/checkout-session', [
            'room_id' => $this->room->slug,
            'guest_name' => 'Hugo Suarez',
            'guest_email' => 'hugo@example.com',
            'guest_phone' => '+56912345678',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'notes' => 'Late arrival',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session/cs_test_123')
            ->assertJsonPath('checkout_session_id', 'cs_test_123')
            ->assertJsonPath('reservation.payment_status', 'pending')
            ->assertJsonPath('reservation.source', 'stripe_checkout');

        $this->assertDatabaseHas('reservations', [
            'reservation_code' => $response->json('reservation.reservation_code'),
            'payment_status' => PaymentStatus::Pending->value,
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);
    }

    public function test_recent_pending_reservation_blocks_availability(): void
    {
        Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-PENDING01',
            'guest_name' => 'Reserva Pendiente',
            'guest_email' => 'pending@example.com',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'CLP',
            'subtotal' => 300000,
            'fees_total' => 0,
            'total' => 300000,
            'pricing_breakdown' => ['nights' => 3],
            'source' => 'stripe_checkout',
        ]);

        $this->getJson("/api/rooms/{$this->room->slug}/availability?check_in=2026-08-05&check_out=2026-08-08")
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('conflicts.0.reference', 'RDC-PENDING01');
    }

    public function test_expired_pending_reservation_no_longer_blocks_availability(): void
    {
        $reservation = Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-PENDING02',
            'guest_name' => 'Reserva Expirada',
            'guest_email' => 'expired@example.com',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'CLP',
            'subtotal' => 300000,
            'fees_total' => 0,
            'total' => 300000,
            'pricing_breakdown' => ['nights' => 3],
            'source' => 'stripe_checkout',
        ]);

        $reservation->timestamps = false;
        $reservation->forceFill([
            'created_at' => Carbon::now()->subMinutes(31),
            'updated_at' => Carbon::now()->subMinutes(31),
        ])->save();

        $this->getJson("/api/rooms/{$this->room->slug}/availability?check_in=2026-08-05&check_out=2026-08-08")
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonCount(0, 'conflicts');
    }

    public function test_stripe_webhook_marks_reservation_as_paid(): void
    {
        Mail::fake();
        Config::set('booking.admin_notification_email', 'admin@refugiodelchucao.test');

        $reservation = Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-STRIPE01',
            'guest_name' => 'Reserva Stripe',
            'guest_email' => 'stripe@example.com',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'CLP',
            'subtotal' => 300000,
            'fees_total' => 0,
            'total' => 300000,
            'pricing_breakdown' => ['nights' => 3],
            'source' => 'stripe_checkout',
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);

        $stripeCheckoutService = Mockery::mock(StripeCheckoutService::class);
        $stripeCheckoutService
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn(Event::constructFrom([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_123',
                        'payment_intent' => 'pi_test_123',
                        'metadata' => [
                            'reservation_code' => $reservation->reservation_code,
                        ],
                    ],
                ],
            ]));

        $this->app->instance(StripeCheckoutService::class, $stripeCheckoutService);

        $this->postJson('/api/stripe/webhook', [])
            ->assertOk()
            ->assertJsonPath('received', true);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        Mail::assertSent(GuestReservationConfirmedMail::class, function (GuestReservationConfirmedMail $mail) use ($reservation) {
            return $mail->hasTo($reservation->guest_email)
                && $mail->reservation->is($reservation->fresh());
        });

        Mail::assertSent(AdminReservationPaidMail::class, function (AdminReservationPaidMail $mail) use ($reservation) {
            return $mail->hasTo('admin@refugiodelchucao.test')
                && $mail->reservation->is($reservation->fresh());
        });
    }

    public function test_duplicate_paid_webhook_does_not_resend_emails(): void
    {
        Mail::fake();
        Config::set('booking.admin_notification_email', 'admin@refugiodelchucao.test');

        $reservation = Reservation::query()->create([
            'room_id' => $this->room->id,
            'reservation_code' => 'RDC-STRIPE02',
            'guest_name' => 'Reserva Stripe',
            'guest_email' => 'stripe@example.com',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'number_of_guests' => 2,
            'status' => ReservationStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'currency' => 'CLP',
            'subtotal' => 300000,
            'fees_total' => 0,
            'total' => 300000,
            'pricing_breakdown' => ['nights' => 3],
            'source' => 'stripe_checkout',
            'stripe_checkout_session_id' => 'cs_test_paid',
            'paid_at' => now(),
        ]);

        $stripeCheckoutService = Mockery::mock(StripeCheckoutService::class);
        $stripeCheckoutService
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn(Event::constructFrom([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_paid',
                        'payment_intent' => 'pi_test_paid',
                        'metadata' => [
                            'reservation_code' => $reservation->reservation_code,
                        ],
                    ],
                ],
            ]));

        $this->app->instance(StripeCheckoutService::class, $stripeCheckoutService);

        $this->postJson('/api/stripe/webhook', [])
            ->assertOk()
            ->assertJsonPath('received', true);

        Mail::assertNothingSent();
    }

    public function test_room_block_makes_room_unavailable(): void
    {
        RoomBlock::query()->create([
            'room_id' => $this->room->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'reason' => 'Maintenance',
            'source' => BlockSource::Maintenance,
        ]);

        $response = $this->postJson('/api/availability/check', [
            'room_id' => $this->room->slug,
            'check_in' => '2026-09-11',
            'check_out' => '2026-09-14',
            'number_of_guests' => 2,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('conflicts.0.type', 'block');
    }
}

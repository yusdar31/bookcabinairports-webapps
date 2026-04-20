<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $resepsionis;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resepsionis = User::factory()->create([
            'role' => 'resepsionis',
            'is_active' => true,
        ]);

        $this->room = Room::create([
            'room_number' => 'C-001',
            'type' => 'standard',
            'floor' => '1',
            'price_per_hour' => 35000,
            'price_per_night' => 150000,
            'status' => 'available',
            'amenities' => json_encode(['wifi', 'ac']),
        ]);
    }

    public function test_can_check_room_availability(): void
    {
        $response = $this->actingAs($this->resepsionis)
            ->getJson('/api/rooms/availability?check_in=2026-05-01T14:00&check_out=2026-05-02T12:00');

        $response->assertOk()
            ->assertJsonStructure(['available_rooms', 'total']);
    }

    public function test_can_create_booking(): void
    {
        $response = $this->actingAs($this->resepsionis)
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'guest_name' => 'John Doe',
                'guest_email' => 'john@test.com',
                'guest_phone' => '08123456789',
                'check_in' => now()->addHour()->toISOString(),
                'check_out' => now()->addHours(6)->toISOString(),
                'payment_method' => 'cash',
                'source' => 'direct',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Booking berhasil dibuat.')
            ->assertJsonStructure(['data' => ['booking_code', 'pin_code', 'qr_token']]);
    }

    public function test_prevents_double_booking(): void
    {
        $checkIn = now()->addHour()->toISOString();
        $checkOut = now()->addHours(6)->toISOString();

        // First booking
        $this->actingAs($this->resepsionis)
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'guest_name' => 'Guest 1',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]);

        // Second booking same room same time
        $response = $this->actingAs($this->resepsionis)
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'guest_name' => 'Guest 2',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]);

        $response->assertStatus(409);
    }

    public function test_check_in_with_pin(): void
    {
        // Create booking first
        $bookingResponse = $this->actingAs($this->resepsionis)
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'guest_name' => 'PIN Test',
                'check_in' => now()->addMinute()->toISOString(),
                'check_out' => now()->addHours(6)->toISOString(),
            ]);

        $booking = $bookingResponse->json('data');

        // Update status to confirmed for check-in
        \App\Models\Booking::find($booking['id'])->update(['status' => 'confirmed']);

        // Check-in with PIN
        $response = $this->actingAs($this->resepsionis)
            ->postJson("/api/bookings/{$booking['id']}/check-in", [
                'pin_code' => $booking['pin_code'],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Check-in berhasil!');
    }

    public function test_unauthorized_role_cannot_access_bookings(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);

        $response = $this->actingAs($kasir)
            ->getJson('/api/bookings');

        $response->assertStatus(403);
    }
}

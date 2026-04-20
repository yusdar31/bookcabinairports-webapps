<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;
    private Outlet $outlet;
    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
        ]);

        $this->outlet = Outlet::create([
            'name' => 'Test Outlet',
            'location' => 'Terminal 1',
            'type' => 'cafe',
            'open_time' => '06:00',
            'close_time' => '22:00',
        ]);

        $this->menu = Menu::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Kopi Test',
            'price' => 25000,
            'category' => 'minuman',
        ]);
    }

    public function test_can_create_transaction(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson('/api/transactions', [
                'outlet_id' => $this->outlet->id,
                'items' => [
                    ['menu_id' => $this->menu->id, 'quantity' => 2],
                ],
                'payment_method' => 'cash',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Transaksi berhasil dicatat.');

        // Verify calculation: subtotal 50000, tax 5500 (11%), total 55500
        $data = $response->json('data');
        $this->assertEquals(50000, $data['subtotal']);
        $this->assertEquals(5500, $data['tax']);
        $this->assertEquals(55500, $data['total']);
    }

    public function test_offline_sync_prevents_duplicates(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'items' => [['menu_id' => $this->menu->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'offline_id' => 'offline-test-001',
        ];

        // First call
        $this->actingAs($this->kasir)->postJson('/api/transactions', $payload)->assertCreated();

        // Duplicate (same offline_id)
        $response = $this->actingAs($this->kasir)->postJson('/api/transactions', $payload);

        $response->assertOk()
            ->assertJsonPath('message', 'Transaksi sudah pernah disinkronkan.');
    }

    public function test_batch_sync(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson('/api/transactions/sync', [
                'transactions' => [
                    [
                        'outlet_id' => $this->outlet->id,
                        'items' => [['menu_id' => $this->menu->id, 'quantity' => 1]],
                        'payment_method' => 'cash',
                        'offline_id' => 'batch-001',
                    ],
                    [
                        'outlet_id' => $this->outlet->id,
                        'items' => [['menu_id' => $this->menu->id, 'quantity' => 3]],
                        'payment_method' => 'qris',
                        'offline_id' => 'batch-002',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['results' => ['synced', 'skipped', 'errors']]);
    }

    public function test_resepsionis_cannot_access_pos(): void
    {
        $resepsionis = User::factory()->create(['role' => 'resepsionis', 'is_active' => true]);

        $response = $this->actingAs($resepsionis)
            ->getJson('/api/transactions');

        $response->assertStatus(403);
    }
}

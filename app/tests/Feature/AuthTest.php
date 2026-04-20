<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'kasir@test.com',
            'password' => bcrypt('password123'),
            'role' => 'kasir',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'kasir@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'kasir');
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'disabled@test.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'disabled@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_middleware_blocks_inactive_user(): void
    {
        $user = User::factory()->create([
            'role' => 'kasir',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda telah dinonaktifkan.');
    }

    public function test_super_admin_bypasses_role_check(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Super admin can access kasir routes
        $response = $this->actingAs($admin)->getJson('/api/transactions');
        $response->assertOk();

        // Super admin can access manajer routes
        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');
        $response->assertOk();
    }
}

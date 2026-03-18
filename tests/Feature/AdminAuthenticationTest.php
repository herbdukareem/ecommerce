<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_admin_login_with_seeded_demo_credentials_succeeds(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $userId = PersonalAccessToken::findToken($token)?->tokenable_id;
        $this->assertNotNull($userId);

        $user = User::findOrFail($userId);
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_seeded_demo_admin_has_expected_role_and_password(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->hasRole('Admin'));
    }

    public function test_admin_user_can_access_protected_admin_endpoint(): void
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard')
            ->assertOk();
    }

    public function test_non_admin_authenticated_user_is_denied_admin_endpoint(): void
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Customer Test',
            'email' => 'customer@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated()->assertJsonStructure(['token', 'user']);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'customer@test.com',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);
    }
}

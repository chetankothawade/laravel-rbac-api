<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'active.user@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['user', 'token'],
            ]);

        $this->assertTrue((bool) $response->json('status'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive.user@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::INACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403);
        $this->assertFalse((bool) $response->json('status'));
    }

    public function test_login_with_invalid_credentials_returns_unauthorized(): void
    {
        User::factory()->create([
            'email' => 'known.user@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'known.user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $this->assertFalse((bool) $response->json('status'));
    }

    public function test_public_registration_does_not_return_token_for_inactive_account(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Registered User',
            'email' => 'registered.user@example.com',
            'phone' => '9999999999',
            'password' => 'Password@123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.token', null);

        $this->assertDatabaseHas('users', [
            'email' => 'registered.user@example.com',
            'status' => UserStatus::INACTIVE->value,
        ]);
    }
}

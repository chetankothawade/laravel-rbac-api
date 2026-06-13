<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('burst:127.0.0.1');
        RateLimiter::clear('rate_limit:ip:127.0.0.1');

        parent::tearDown();
    }

    public function test_reset_password_fails_when_token_is_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'expired.reset@example.com',
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        $rawToken = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($rawToken),
            'created_at' => now()->subMinutes(((int) config('auth.passwords.users.expire', 60)) + 1),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $rawToken,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', false);
    }

    public function test_forgot_password_is_rate_limited_after_burst_threshold(): void
    {
        $user = User::factory()->create([
            'email' => 'throttle.reset@example.com',
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        for ($i = 1; $i <= 30; $i++) {
            $this->postJson('/api/forgot-password', [
                'email' => $user->email,
            ])->assertStatus(200);
        }

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit');
    }

    public function test_forgot_password_response_is_generic_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'missing.reset@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        Mail::assertNothingSent();
    }
}

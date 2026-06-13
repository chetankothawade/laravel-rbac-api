<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_authenticated_user_is_denied_protected_routes(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::INACTIVE->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_normal_user_is_denied_user_management(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/users')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_normal_user_is_denied_module_management(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/modules')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_normal_user_is_denied_role_management(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/role-modules/matrix')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_access_admin_only_management_routes(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/users')->assertOk();
        $this->getJson('/api/modules')->assertOk();
        $this->getJson('/api/role-modules/matrix')->assertOk();
    }
}

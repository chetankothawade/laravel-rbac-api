<?php

namespace Tests\Feature\Api\User;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_missing_user_returns_not_found_instead_of_server_error(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/users/not-a-real-uuid', [
            'name' => 'Missing User',
            'phone' => '9999999999',
            'role' => UserRole::USER->value,
        ])->assertStatus(404);
    }

    public function test_admin_cannot_promote_user_to_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$user->uuid}", [
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => UserRole::SUPER_ADMIN->value,
        ])->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::USER->value,
        ]);
    }

    public function test_hidden_super_admin_cannot_be_accessed_directly(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/users/{$superAdmin->uuid}")
            ->assertStatus(404);
    }

    public function test_update_password_requires_password_confirmation_field(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$user->uuid}", [
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => UserRole::USER->value,
            'password' => 'NewPassword@123',
            'cpassword' => 'NewPassword@123',
        ])->assertStatus(422);
    }
}

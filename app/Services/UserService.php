<?php

declare(strict_types=1);

/**
 * User module — Service
 * @author Chetan Kothawade
 */


namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Logging\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get paginated users with optional search and sorting.
     */
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        $search      = $filters['search'] ?? null;
        $status      = $filters['status'] ?? null;
        $sortedField = $filters['sortedField'] ?? 'id';
        $sortedBy    = $filters['sortedBy'] ?? 'asc';
        $perPage     = $filters['perPage'] ?? 10;

        $query = User::select('*')
            ->whereNull('deleted_at')
            ->where('id', '!=', Auth::id())
            ->where('role', '!=', UserRole::SUPER_ADMIN->value);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortedField, $sortedBy);

        return $query->paginate($perPage);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data, string $ipAddress): User
    {
        return DB::transaction(function () use ($data, $ipAddress) {
            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'password'   => Hash::make($data['password']),
                'last_login_ip' => $ipAddress,
                'status'     => $data['status'] ?? UserStatus::ACTIVE->value,
            ]);

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $updateData = [
                'name'      => $data['name'] ?? $user->name,
                'email'     => $data['email'] ?? $user->email,
                'phone'     => $data['phone'] ?? $user->phone,
                'role'      => $data['role'] ?? $user->role,
            ];

            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            return $user->fresh();
        });
    }



    /**
     * Soft delete user and mark status as deleted.
     */
    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update(['status' => UserStatus::DELETED->value]);
            $user->delete(); // assumes SoftDeletes on User model
        });
    }

    /**
     * Toggle user status between active and inactive.
     */
    public function toggleStatus(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $newStatus = $user->status === UserStatus::ACTIVE->value
                ? UserStatus::INACTIVE->value
                : UserStatus::ACTIVE->value;
            activity()->withoutLogs(function () use ($user, $newStatus) {
                $user->update(['status' => $newStatus]);
            });

            $user = $user->fresh();
            $event = $newStatus === UserStatus::ACTIVE->value ? 'activated' : 'deactivated';
            $this->activityLogger()->log('user', $user, $event, [
                'status' => $newStatus,
            ]);

            return $user;
        });
    }
    /**
     * Fetch using UUID
     */
    public function getByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Fetch a user that can be managed through the user-management module.
     */
    public function getManageableByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->where('id', '!=', Auth::id())
            ->where('role', '!=', UserRole::SUPER_ADMIN->value)
            ->first();
    }



    public function getUserList(): Collection
    {
        return User::select(['id', 'uuid', 'name'])
            ->whereNull('deleted_at')
            ->where('id', '!=', Auth::id())
            ->where('role', '!=', UserRole::SUPER_ADMIN->value)
            ->get();
    }

    private function activityLogger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}

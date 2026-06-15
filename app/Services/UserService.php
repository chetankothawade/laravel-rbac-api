<?php

declare(strict_types=1);

/**
 * User module — Service
 * @author Chetan Kothawade
 */


namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Logging\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    protected UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? app(UserRepository::class);
    }

    /**
     * Get paginated users with optional search and sorting.
     */
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedManageable($filters, Auth::id());
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data, string $ipAddress): User
    {
        return DB::transaction(function () use ($data, $ipAddress) {
            return $this->userRepository->create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'avatar'     => $this->storeAvatar($data['avatar'] ?? null),
                'password'   => Hash::make($data['password']),
                'last_login_ip' => $ipAddress,
                'status'     => $data['status'] ?? UserStatus::ACTIVE->value,
            ]);
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

            if (($data['avatar'] ?? null) instanceof UploadedFile) {
                $this->deleteAvatar($user->avatar);
                $updateData['avatar'] = $this->storeAvatar($data['avatar']);
            }

            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $this->userRepository->update($user, $updateData);

            return $user->fresh();
        });
    }



    /**
     * Soft delete user and mark status as deleted.
     */
    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->userRepository->update($user, ['status' => UserStatus::DELETED->value]);
            $this->userRepository->delete($user); // assumes SoftDeletes on User model
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
                $this->userRepository->update($user, ['status' => $newStatus]);
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
        return $this->userRepository->findByUuid($uuid);
    }

    /**
     * Fetch a user that can be managed through the user-management module.
     */
    public function getManageableByUuid(string $uuid): ?User
    {
        return $this->userRepository->findManageableByUuid($uuid, Auth::id());
    }



    public function getUserList(): Collection
    {
        return $this->userRepository->getManageableList(Auth::id());
    }

    private function activityLogger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }

    private function storeAvatar(?UploadedFile $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }

        return $avatar->storeAs(
            'uploads/users/avatars',
            Str::uuid() . '.' . $avatar->getClientOriginalExtension(),
            'public'
        );
    }

    private function deleteAvatar(?string $avatar): void
    {
        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }
    }
}

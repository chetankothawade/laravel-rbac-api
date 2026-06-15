<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    /**
     * Get paginated manageable users with optional search and sorting.
     */
    public function getPaginatedManageable(array $filters, ?int $currentUserId): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $sortedField = $filters['sortedField'] ?? 'id';
        $sortedBy = $filters['sortedBy'] ?? 'asc';
        $perPage = $filters['perPage'] ?? 10;

        $query = $this->manageableQuery($currentUserId);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sortedField, $sortedBy)->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    public function findManageableByUuid(string $uuid, ?int $currentUserId): ?User
    {
        return $this->manageableQuery($currentUserId)
            ->where('uuid', $uuid)
            ->first();
    }

    public function getManageableList(?int $currentUserId): Collection
    {
        return $this->manageableQuery($currentUserId)
            ->select(['id', 'uuid', 'name'])
            ->get();
    }

    private function manageableQuery(?int $currentUserId): Builder
    {
        return User::select('*')
            ->whereNull('deleted_at')
            ->when($currentUserId, fn (Builder $query) => $query->where('id', '!=', $currentUserId))
            ->where('role', '!=', UserRole::SUPER_ADMIN->value);
    }
}

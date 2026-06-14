<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UserIndexRequest;
use App\Http\Requests\Api\User\UserStoreRequest;
use App\Http\Requests\Api\User\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * List users with pagination, search & sorting
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        $filters = $request->filters();

        $users = $this->userService->getPaginatedUsers($filters);
 
        return $this->paginate(
            "messages.user_list_success",
            UserResource::collection($users),
            $users
        );
    }

    /**
     * Create new user
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        return $this->safeExecute(
            "messages.user_created",
            fn() => $this->userService->createUser(
                $request->validated(),
                request()->ip()
            ),
            201
        );
    }

    /**
     * Show user by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        return $this->success(
            "messages.user_details_success",
            new UserResource($this->findUserOrFail($uuid))
        );
    }

    /**
     * Edit user (same as show)
     */
    public function edit(string $uuid): JsonResponse
    {
        return $this->show($uuid);
    }

    /**
     * Update user
     */
    public function update(UserUpdateRequest $request, string $uuid): JsonResponse
    {
        return $this->safeExecute(
            "messages.user_updated",
            fn() => $this->userService->updateUser(
                $this->findUserOrFail($uuid),
                $request->validated()
            )
        );
    }

    /**
     * Soft delete user
     */
    public function destroy(string $uuid): JsonResponse
    {
        return $this->safeExecute("messages.user_deleted", function () use ($uuid) {
            $this->userService->deleteUser($this->findUserOrFail($uuid));
            return [];
        });
    }

    /**
     * Toggle active/inactive status
     */
    public function active(string $uuid): JsonResponse
    {
        return $this->safeExecute(
            "messages.user_status_updated",
            fn() => [
                "status" => $this->userService->toggleStatus(
                    $this->findUserOrFail($uuid)
                )->status,
            ]
        );
    }

    /**
     * Get user dropdown list
     */
    public function getUserList(): JsonResponse
    {
        return $this->success(
            "messages.user_dropdown_success",
            $this->userService->getUserList()
        );
    }

    /**
     * Fetch user or fail with 404
     */
    private function findUserOrFail(string $uuid): User
    {
        $user = $this->userService->getManageableByUuid($uuid);

        if (!$user) {
            abort(404, __("messages.user_not_found"));
        }

        return $user;
    }
}

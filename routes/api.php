<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EditorUploadController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\RoleModuleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserPermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api.
| Sanctum is used for authentication.
|
*/

/*
|--------------------------------------------------------------------------
| Admin Authentication (Public)
|--------------------------------------------------------------------------
*/

Route::middleware(['ip.throttle', 'burst.throttle'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Requires auth:sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'active.user', 'role.throttle', 'token.throttle'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authenticated Admin User
        |--------------------------------------------------------------------------
        */
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);

        Route::middleware(['admin.only'])->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            | All user management routes.
            */
            Route::prefix('users')->group(function () {

                Route::get('/getList', [UserController::class, 'getUserList']);
                Route::get('/', [UserController::class, 'index']);
                Route::post('/', [UserController::class, 'store']);
                Route::get('/{uuid}', [UserController::class, 'show']);
                Route::get('/{uuid}/edit', [UserController::class, 'edit']);
                Route::put('/{uuid}', [UserController::class, 'update']);
                Route::delete('/{uuid}', [UserController::class, 'destroy']);
                Route::put('/{uuid}/active', [UserController::class, 'active']);
            });

            /*
            |--------------------------------------------------------------------------
            | Modules
            |--------------------------------------------------------------------------
            */
            Route::prefix('modules')->group(function () {
                // Parent/Module list
                Route::get('/module/getList', [ModuleController::class, 'getModuleList']);

                // CRUD
                Route::get('/', [ModuleController::class, 'index']);
                Route::post('/', [ModuleController::class, 'store']);
                Route::get('/{uuid}', [ModuleController::class, 'show']);
                Route::get('/{uuid}/edit', [ModuleController::class, 'edit']);
                Route::put('/{uuid}', [ModuleController::class, 'update']);
                Route::delete('/{uuid}', [ModuleController::class, 'destroy']);
                Route::put('/{uuid}/active', [ModuleController::class, 'active']);
            });

            /*
            |--------------------------------------------------------------------------
            | Role-Modules
            |--------------------------------------------------------------------------
            */
            Route::prefix('role-modules')->group(function () {
                Route::get('/matrix', [RoleModuleController::class, 'matrix']);
                Route::post('/toggle', [RoleModuleController::class, 'toggle']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | User Permissions
        |--------------------------------------------------------------------------
        */
        Route::prefix('user-permissions')->group(function () {
            Route::get('/side-menu', [UserPermissionController::class, 'sidebarMenu']);
            Route::middleware(['admin.only'])->group(function () {
                Route::post('/toggle', [UserPermissionController::class, 'toggle']);
                Route::get('/{uuid}/getAll', [UserPermissionController::class, 'getUsersModulesPermission']);
                Route::get('/{uuid}/module-access', [UserPermissionController::class, 'userModuleAccess']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | Editor Uploads
        |--------------------------------------------------------------------------
        */
        Route::post('/editor/upload',[EditorUploadController::class, 'upload']);
    });
});

# Backend API

Laravel-based backend API for the Authentication, RBAC, Activity Logs and User Management application.

## Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL / MariaDB
- Laravel Sanctum (API auth)

## Folder Structure

```text
laravel-rbac-api/
+-- app/
|   +-- Enums/                 # Centralized enum values for roles, status and permissions
|   +-- Helpers/               # Shared helper functions
|   +-- Http/
|   |   +-- Controllers/Api/   # API controllers for auth, users, modules and RBAC
|   |   +-- Middleware/        # Rate limit and request protection middleware
|   |   +-- Requests/Api/      # Form request validation classes
|   |   +-- Resources/         # API response transformers
|   +-- Mail/                  # Mail classes
|   +-- Models/                # Eloquent models
|   +-- Providers/             # Laravel service providers
|   +-- Services/              # Business logic services
|   +-- Traits/                # Shared model/controller traits
+-- bootstrap/                 # Application bootstrapping and exception handling
+-- config/                    # Laravel and package configuration
+-- database/
|   +-- factories/             # Model factories for tests
|   +-- migrations/            # Database schema definitions
|   +-- seeders/               # Default users, modules, permissions and role access
+-- postman/                   # Postman API collection
+-- public/                    # Public web root and static assets
+-- resources/
|   +-- css/                   # Frontend CSS assets
|   +-- js/                    # Frontend JavaScript assets
|   +-- lang/                  # Translation and validation messages
|   +-- views/                 # Blade views and email templates
+-- routes/
|   +-- api.php                # API route definitions
|   +-- console.php            # Console route definitions
|   +-- web.php                # Web route definitions
+-- storage/                   # Logs, framework cache and generated files
+-- tests/
    +-- Feature/               # API and feature tests
    +-- Unit/                  # Unit tests
```

## Architecture

This project follows a layered Laravel API structure:

1. `routes/api.php` defines public and protected API endpoints.
2. API controllers in `app/Http/Controllers/Api` receive requests and return JSON responses.
3. Request classes in `app/Http/Requests/Api` validate incoming payloads.
4. Service classes in `app/Services` contain the main business logic for authentication, users, modules and permissions.
5. Eloquent models in `app/Models` handle database interaction.
6. API resources in `app/Http/Resources` format response data consistently.
7. Middleware in `app/Http/Middleware` applies authentication and rate-limiting rules.

Main API modules:

- `AuthController` + `AuthService`: login, registration, token refresh, logout and password reset.
- `UserController` + `UserService`: user listing, CRUD, status changes and profile data.
- `ModuleController` + `ModuleService`: module management for the RBAC menu/module system.
- `RoleModuleController`: role-to-module access matrix and toggles.
- `UserPermissionController` + `UserPermissionService`: user-level permission matrix, module access and sidebar menu.
- `ActivityLogController` + `ActivityLogger`: activity log listing and write support.

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL/MariaDB running locally

## Project Setup

1. Clone project and move into backend folder.
2. Install dependencies:

```bash
composer install
```

3. Create environment file:

```bash
cp .env.example .env
```

4. Generate app key:

```bash
php artisan key:generate
```

5. Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=signature_tool
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations + seeders:

```bash
php artisan migrate --seed
```

7. Start server:

```bash
php artisan serve
```

API base URL:

```text
http://127.0.0.1:8000/api
```

## Default Seeded Users

All seeded users use password:

```text
Password@123
```

Available accounts:

- superadmin@yopmail.com (role: super_admin)
- admin@yopmail.com (role: admin)
- user@yopmail.com (role: user)

## Authentication

- Login endpoint: `POST /api/login`
- Uses Sanctum token-based authentication
- Send token in header:

```http
Authorization: Bearer <token>
```

- Refresh token endpoint: `POST /api/refresh` (requires authenticated user context)

## Password Reset

- Forgot password: `POST /api/forgot-password`
- Reset password: `POST /api/reset-password`
- Reset token expiry is controlled by `config/auth.php`:
  - `auth.passwords.users.expire` (minutes, default `60`)
- Public auth routes are rate-limited by middleware:
  - `ip.throttle` (`60` requests / 60 seconds per IP)
  - `burst.throttle` (`30` requests / 30 seconds per IP)

## Useful Commands

Run tests:

```bash
php artisan test
```

Fresh migrate + seed:

```bash
php artisan migrate:fresh --seed
```

Clear caches:

```bash
php artisan optimize:clear
```

## Postman

Import collection from:

```text
postman/signature_tool_api.postman_collection.json
```

Set collection variables after import:

- `base_url` = `http://localhost/api` (or your running URL)
- `token` = value returned from login API

## Notes

- App timezone is configured via `APP_TIMEZONE` in `.env`.
- Exception JSON handling is configured in `bootstrap/app.php` (`withExceptions`).
- User role/status values are centralized in:
  - `app/Enums/UserRole.php`
  - `app/Enums/UserStatus.php`

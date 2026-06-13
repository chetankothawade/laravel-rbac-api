<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Logging\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data, string $ipAddress, string $userAgent): array
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent) {

            $user = User::create([
                'uuid'       => Str::uuid(),
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'last_login_ip' => $ipAddress,
                'last_login_ua' => $userAgent,
                'password'   => Hash::make($data['password']),
                'status'     => UserStatus::INACTIVE->value,
            ]);

            $this->activityLogger()->log('user', $user, 'registered', [], $user);

            return [
                'user'  => $user,
                'token' => null
            ];
        });
    }

    /**
     * Login user
     */
    public function login(array $data): array|false
    {
        $user = User::where('email', $data['email'])->first();


        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return false; // invalid credentials
        }

        if ($user->status !== UserStatus::ACTIVE->value) {
            return ['inactive' => true];
        }

        return DB::transaction(function () use ($user) {

            $token = $user->createToken('ADMIN_AUTH_TOKEN')->plainTextToken;

            $ip        = request()->ip();
            $userAgent = request()->userAgent();   // full UA string

            // Ensure activitylog can resolve causer during login updates
            Auth::setUser($user);

            $user->update([
                'last_login_at'  => Carbon::now(),
                'last_login_ip'  => $ip,
                'last_login_ua'  => $userAgent,
            ]);

            $this->activityLogger()->log('user', $user, 'login', [], $user);

            return [
                'user'  => $user,
                'token' => $token
            ];
        });
    }


    /**
     * Send password reset link
     */
    public function sendResetLink(string $email): bool
    {
        if (! User::where('email', $email)->exists()) {
            return true;
        }

        $token = Str::random(64);

        return DB::transaction(function () use ($email, $token) {

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => Carbon::now()
                ]
            );

            $frontendUrl = (string) config('app.admin_frontend_url');

            Mail::to($email)->send(new ResetPasswordMail($token, $email, $frontendUrl));

            return true;
        });
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data): bool
    {
        $reset = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $reset || ! Hash::check($data['token'], $reset->token)) {
            return false; // invalid token
        }

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);
        if (
            empty($reset->created_at) ||
            Carbon::parse($reset->created_at)->addMinutes($expireMinutes)->isPast()
        ) {
            return false; // invalid or expired token
        }

        return DB::transaction(function () use ($data) {

            User::where('email', $data['email'])->update([
                'password' => Hash::make($data['password'])
            ]);

            DB::table('password_reset_tokens')
                ->where('email', $data['email'])
                ->delete();

            return true;
        });
    }

    /**
     * Fetch using UUID
     */
    public function getByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();
    }


    /**
     * Refresh auth token
     */
    public function refreshAdminToken(Request $request): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new AuthenticationException(__('messages.unauthenticated'));
        }

        // delete current token
        $request->user()->currentAccessToken()?->delete();

        // create new token
        $token = $user->createToken('ADMIN_AUTH_TOKEN')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token
        ];
    }

    private function activityLogger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}

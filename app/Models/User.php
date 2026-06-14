<?php
namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, LogsActivity, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'uuid',
        'email',
        'phone',
        'avatar',
        'password',
        'status',
        'role',
        'last_login_at',
        'last_login_ip',
        'last_login_ua',
        'last_logout_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at'  => 'datetime',
            'last_logout_at' => 'datetime',
        ];
    }


    protected $dates = [
        'deleted_at'
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['uuid', 'password'])
            ->setDescriptionForEvent(fn(string $eventName) => $this->getActivityDescription($eventName));
    }

    public function getActivityDescription(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'User created',
            'updated' => 'User updated',
            'deleted' => 'User deleted',
            'restored' => 'User restored',
            'activated' => 'User activated',
            'deactivated' => 'User deactivated',
            'login' => 'User login',
            'logout' => 'User logout',
            'registered' => 'User registered',
            default => "User {$eventName}",
        };
    }
}

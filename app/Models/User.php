<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public function initializeAuditable(): void
    {
        $this->auditEvents = ['created', 'updated', 'deleted', 'login'];
        $this->auditExclude = [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
        ];
    }

    protected $fillable = [
        'email',
        'password',
        'keycloak_id',
        'is_active',
        'last_login_at',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate UUIDv7 identifiers for primary key.
     */
    public function newUniqueId(): string
    {
        return Uuid::uuid7()->toString();
    }


    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get or create admin profile for this user.
     */
    public function getOrCreateAdmin(): Admin
    {
        return $this->admin ?? $this->admin()->create([
            'user_id' => $this->id,
            'full_name' => $this->email, // or some default name
        ]);
    }

    /**
     * Transform audit data for User
     */
    public function transformAudit(array $data): array
    {
        // Add user UUID
        $data['user_uuid'] = $this->getKey();

        // Mask sensitive information
        if (isset($data['old_values']['password'])) {
            $data['old_values']['password'] = '***MASKED***';
        }

        if (isset($data['new_values']['password'])) {
            $data['new_values']['password'] = '***MASKED***';
        }

        return $data;
    }

    /**
     * Log user login event
     */
    public function logLogin(string $ipAddress): void
    {
        $this->auditEvent = 'login';
        $this->isCustomEvent = true;
        $this->auditCustomOld = [];
        $this->auditCustomNew = [
            'last_login_at' => now()->toDateTimeString(),
            'ip_address' => $ipAddress,
            'user_agent' => request()->userAgent(),
        ];

        $this->save();

        // Also update the last_login_at field
        $this->update(['last_login_at' => now()]);

    }
}

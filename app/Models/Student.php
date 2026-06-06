<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IDType;
use App\Enums\Provenance;
use App\Enums\StudentStatus;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $student_number
 * @property string $full_name
 * @property string $gender
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string $place_of_birth
 * @property string $nationality
 * @property string $phone
 * @property string $emergency_contact_name
 * @property string $emergency_contact_phone
 * @property string $address
 * @property string $photo_url
 * @property string $status
 */
class Student extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    use UsesUuidV7;

    protected $table = 'students';

    public array $auditEvents = ['created', 'updated', 'deleted'];

    public array $auditExclude = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'keycloak_user_id',
        'student_number',
        'first_name',
        'last_name',
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'type_of_id',
        'id_details',
        'ine',
        'email',
        'email_university',
        'phone',
        'phone_2',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'photo_url',
        'status',
        'registration_number',
        'provenance',
        'synced_from',
        'last_synced_at',
    ];

    protected $casts = [
        'user_id' => 'string',
        'keycloak_user_id' => 'string',
        'student_number' => 'string',
        'full_name' => 'string',
        'date_of_birth' => 'date',
        'place_of_birth' => 'string',
        'nationality' => 'string',
        'phone' => 'string',
        'emergency_contact_name' => 'string',
        'emergency_contact_phone' => 'string',
        'address' => 'string',
        'photo_url' => 'string',
        'status' => StudentStatus::class,
        'type_of_id' => IDType::class,
        'provenance' => Provenance::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bacInfo(): HasOne
    {
        return $this->hasOne(StudentBacInfo::class);
    }

    public function socialProfile(): MorphOne
    {
        return $this->morphOne(SocialProfile::class, 'profilable');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function priorDiplomas(): MorphMany
    {
        return $this->morphMany(PriorDiploma::class, 'diplomable');
    }

    public function deliberationResults(): HasMany
    {
        return $this->hasMany(DeliberationResult::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(\App\Models\Grade::class, 'student_id');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(\App\Models\CourseEnrollment::class, 'student_id');
    }

    /** @return HasMany<SemesterResult, Student> */
    public function semesterResults(): HasMany
    {
        return $this->hasMany(\App\Models\SemesterResult::class, 'student_id');
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope a query to only include graduated students.
     */
    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where('status', 'GRADUATED');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $sub) use ($term, $likeOperator): void {
            $sub->where('full_name', $likeOperator, "%{$term}%")
                ->orWhere('student_number', $likeOperator, "%{$term}%")
                ->orWhere('phone', $likeOperator, "%{$term}%");
        });
    }

    /**
     * Get the student's age.
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    /**
     * Generate a unique student number.
     * Format: UCAK{YEAR}{NUMBER}
     * Example: UCAK2024001
     */
    public static function generateStudentNumber(): string
    {
        $year = date('Y');
        $lastStudent = self::where('student_number', 'like', "UCAK{$year}%")
            ->orderBy('student_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_number, -3);
            $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "UCAK{$year}{$newNumber}";
    }

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_GRADUATED = 'GRADUATED';

    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    public const STATUS_EXPELLED = 'EXPELLED';

    public static function getStatuses(): array
    {

        return [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_GRADUATED,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPELLED,
        ];

    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{

    use HasFactory;
    use UsesUuidV7;

    protected $table = 'students';

  



    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'student_number',
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'photo_url',
        'status',
    ];

 
    protected $casts = [
        'user_id' => 'string',
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
        'status' => 'string',
    ];

    /**
     * Get the user that owns the student.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliberationResults()
    {
        return $this->hasMany(DeliberationResult::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the guardians for the student.
     */
    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    /**
     * Get the documents for the student.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
   public function grades()
    {
        return $this->hasMany(\App\Models\Grade::class, 'student_id');
    }

    public function courseEnrollments()
    {
        return $this->hasMany(\App\Models\CourseEnrollment::class, 'student_id');
    }

   
    /**
     * Scope a query to only include active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope a query to only include graduated students.
     */
    public function scopeGraduated($query)
    {
        return $query->where('status', 'GRADUATED');
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
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
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

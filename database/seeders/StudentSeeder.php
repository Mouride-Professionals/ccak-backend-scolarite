<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Document;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    /**
     * @var list<string>
     */
    private array $senegalCities = [
        'Dakar',
        'Pikine',
        'Guediawaye',
        'Rufisque',
        'Thies',
        'Mbour',
        'Saint-Louis',
        'Kaolack',
        'Ziguinchor',
        'Diourbel',
        'Louga',
        'Tambacounda',
        'Kolda',
        'Matam',
        'Kedougou',
        'Sediou',
        'Fatick',
        'Kaffrine',
    ];

    /**
     * @var list<string>
     */
    private array $maleFirstNames = [
        'Mamadou',
        'Abdou',
        'Cheikh',
        'Ousmane',
        'Ibrahima',
        'Moussa',
        'Alioune',
        'Moustapha',
        'Bamba',
        'Amadou',
        'Serigne',
        'Babacar',
        'Modou',
        'Pape',
        'Lamine',
    ];

    /**
     * @var list<string>
     */
    private array $femaleFirstNames = [
        'Aminata',
        'Aissatou',
        'Fatou',
        'Khadija',
        'Mariama',
        'Coumba',
        'Sokhna',
        'Rama',
        'Diarra',
        'Ndeye',
        'Awa',
        'Astou',
        'Khady',
        'Mame',
        'Sira',
    ];

    /**
     * @var list<string>
     */
    private array $lastNames = [
        'Ndiaye',
        'Diop',
        'Ba',
        'Sow',
        'Fall',
        'Gueye',
        'Cisse',
        'Seck',
        'Sy',
        'Sarr',
        'Ndoye',
        'Faye',
        'Kane',
        'Ndao',
        'Gassama',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR'); // Utiliser le français pour des données réalistes
        $academicYearName = $this->currentAcademicYearName();
        [$enrollStart, $enrollEnd] = $this->enrollmentWindow($academicYearName);

        $admins = Admin::all();
        if ($admins->isEmpty()) {
            $this->call(AdminSeeder::class);
            $admins = Admin::all();
        }
        if ($admins->isEmpty()) {
            $gender = $faker->randomElement(['M', 'F']);
            $user = User::factory()->create([
                'email' => $this->uniqueSeedEmail('admin'),
                'email_verified_at' => Carbon::now('Africa/Dakar'),
            ]);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('ADMIN');
            }
            $admins = collect([
                Admin::create([
                    'user_id' => $user->id,
                    'full_name' => $this->pickFullName($gender),
                ]),
            ]);
        }

        for ($i = 0; $i < 100; $i++) {
            // Créer un utilisateur pour l'étudiant
            $user = User::factory()->create([
                'email' => $this->uniqueSeedEmail('student'),
                'email_verified_at' => Carbon::now('Africa/Dakar'),
            ]);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('STUDENT');
            }

            // Créer l'étudiant
            $gender = $faker->randomElement(['M', 'F']);
            $birthDate = Carbon::now('Africa/Dakar')
                ->subYears(rand(18, 26))
                ->subDays(rand(0, 365));
            $firstName = $gender === 'F'
                ? Arr::random($this->femaleFirstNames)
                : Arr::random($this->maleFirstNames);
            $lastName = Arr::random($this->lastNames);
            $fullName = $firstName . ' ' . $lastName;
            $slugName = strtolower(str_replace(' ', '.', $fullName));

            $student = Student::create([
                'user_id' => $user->id,
                'student_number' => $studentNumber = Student::generateStudentNumber(),
                'full_name' => $fullName,
                'gender' => $gender,
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'place_of_birth' => Arr::random($this->senegalCities),
                'nationality' => 'Senegalaise',
                'phone' => $this->senegalPhone(),
                'phone_2' => $faker->boolean(30) ? $this->senegalPhone() : null,
                'email' => $faker->unique()->safeEmail(),
                'email_university' => $slugName . '.' . strtolower($studentNumber) . '@etudiant.ucak.sn',
                'type_of_id' => $faker->randomElement(['PASSPORT', 'NATIONAL_ID', 'DRIVING_LICENSE']),
                'id_details' => $faker->bothify('SN-########'),
                'emergency_contact_name' => $this->pickFullName($faker->randomElement(['M', 'F'])),
                'emergency_contact_phone' => $this->senegalPhone(),
                'address' => Arr::random($this->senegalCities) . ', Senegal',
                'photo_url' => null,
                'status' => $faker->randomElement(['ACTIVE', 'ACTIVE', 'ACTIVE', 'SUSPENDED', 'GRADUATED', 'INACTIVE']),
            ]);

            // Créer 1 à 2 tuteurs
            $numGuardians = $faker->numberBetween(1, 2);
            for ($j = 0; $j < $numGuardians; $j++) {
                $guardianGender = $faker->randomElement(['M', 'F']);
                $guardianFirst = $guardianGender === 'F'
                    ? Arr::random($this->femaleFirstNames)
                    : Arr::random($this->maleFirstNames);
                $guardianLast = Arr::random($this->lastNames);
                Guardian::create([
                    'student_id' => $student->id,
                    'first_name' => $guardianFirst,
                    'last_name' => $guardianLast,
                    'full_name' => $guardianFirst . ' ' . $guardianLast,
                    'relationship' => $faker->randomElement(['Père', 'Mère', 'Tuteur', 'Oncle', 'Tante']),
                    'phone' => $this->senegalPhone(),
                    'phone_2' => $faker->boolean(20) ? $this->senegalPhone() : null,
                    'email' => $faker->email(),
                    'address' => Arr::random($this->senegalCities) . ', Senegal',
                    'occupation' => $faker->jobTitle(),
                ]);
            }

            // Créer des documents avec différents statuts
            $documentTypes = ['CNI', 'BIRTH_CERT', 'BAC_DIPLOMA', 'TRANSCRIPT', 'PHOTO', 'MEDICAL'];
            $numDocuments = $faker->numberBetween(3, 6); // 3 à 6 documents par étudiant
            $selectedTypes = $faker->randomElements($documentTypes, $numDocuments, false);

            foreach ($selectedTypes as $type) {
                $uploadedAt = Carbon::instance($faker->dateTimeBetween($enrollStart, $enrollEnd));
                $status = $faker->randomElement(['PENDING', 'APPROVED', 'REJECTED']);
                $reviewedBy = null;
                $reviewedAt = null;
                $notes = null;

                if ($status !== 'PENDING') {
                    $reviewedBy = $faker->randomElement($admins)->id;
                    $reviewedAt = Carbon::instance(
                        $faker->dateTimeBetween($uploadedAt->copy()->addDays(3), $uploadedAt->copy()->addWeeks(6))
                    );
                    if ($status === 'REJECTED') {
                        $notes = $faker->sentence();
                    }
                }

                Document::create([
                    'student_id' => $student->id,
                    'type' => $type,
                    'file_path' => '',
                    'file_name' => "{$type}_{$student->id}.pdf",
                    'media_id' => null,
                    'status' => $status,
                    'reviewed_by' => $reviewedBy,
                    'notes' => $notes,
                    'uploaded_at' => $uploadedAt,
                    'reviewed_at' => $reviewedAt,
                ]);
            }
        }
    }

    private function pickFullName(string $gender): string
    {
        $first = $gender === 'F'
            ? Arr::random($this->femaleFirstNames)
            : Arr::random($this->maleFirstNames);

        return $first . ' ' . Arr::random($this->lastNames);
    }

    private function senegalPhone(): string
    {
        $prefix = Arr::random(['70', '75', '76', '77', '78']);

        return $prefix . sprintf('%07d', rand(0, 9999999));
    }

    private function uniqueSeedEmail(string $prefix): string
    {
        return sprintf('%s+%s@seed.ucak.sn', $prefix, (string) Str::uuid());
    }

}

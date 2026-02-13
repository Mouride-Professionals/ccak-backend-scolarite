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
            $student = Student::create([
                'user_id' => $user->id,
                'student_number' => Student::generateStudentNumber(),
                'full_name' => $this->pickFullName($gender),
                'gender' => $gender,
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'place_of_birth' => Arr::random($this->senegalCities),
                'nationality' => 'Senegalaise',
                'phone' => $this->senegalPhone(),
                'emergency_contact_name' => $this->pickFullName($faker->randomElement(['M', 'F'])),
                'emergency_contact_phone' => $this->senegalPhone(),
                'address' => Arr::random($this->senegalCities) . ', Senegal',
                'photo_url' => null, // Peut être ajouté plus tard
                'status' => $faker->randomElement(['ACTIVE', 'ACTIVE', 'ACTIVE', 'SUSPENDED', 'GRADUATED']), // Plus de ACTIVE
            ]);

            // Créer 1 à 2 tuteurs
            $numGuardians = $faker->numberBetween(1, 2);
            for ($j = 0; $j < $numGuardians; $j++) {
                Guardian::create([
                    'student_id' => $student->id,
                    'full_name' => $this->pickFullName($faker->randomElement(['M', 'F'])),
                    'relationship' => $faker->randomElement(['FATHER', 'MOTHER', 'GUARDIAN']),
                    'phone' => $this->senegalPhone(),
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

                $document = Document::create([
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

                $this->attachDocumentMedia($document, $type);
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

    private function attachDocumentMedia(Document $document, string $type): void
    {
        $extension = $type === 'PHOTO' ? 'png' : 'pdf';
        $fileName = "{$type}_{$document->student_id}.{$extension}";
        $tmpPath = tempnam(sys_get_temp_dir(), 'doc_seed_');
        if ($tmpPath === false) {
            return;
        }

        $tmpFile = $tmpPath . '.' . $extension;
        rename($tmpPath, $tmpFile);

        if ($extension === 'png') {
            $png = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=='
            );
            file_put_contents($tmpFile, $png ?: '');
        } else {
            $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
            file_put_contents($tmpFile, $pdf);
        }

        try {
            $media = $document->addMedia($tmpFile)
                ->usingFileName($fileName)
                ->usingName($type)
                ->toMediaCollection($type);

            $document->update([
                'media_id' => $media->id,
                'file_path' => $media->getPathRelativeToRoot(),
                'file_name' => $media->file_name,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }
}

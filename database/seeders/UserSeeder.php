<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYearName = $this->currentAcademicYearName();
        [$enrollStart, $enrollEnd] = $this->enrollmentWindow($academicYearName);
        $rangeStart = $enrollStart->copy()->subMonths(6);
        $rangeEnd = $this->academicYearEnd($academicYearName);

        User::factory()
            ->count(10)
            ->state(function () use ($rangeStart, $rangeEnd) {
                $createdAt = Carbon::instance(fake()->dateTimeBetween($rangeStart, $rangeEnd));
                $verifiedAt = $createdAt->copy()->addDays(rand(0, 7));

                return [
                    'email' => fake()->unique()->userName().'@ucak.sn',
                    'email_verified_at' => $verifiedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $verifiedAt->copy()->addDays(rand(0, 30)),
                ];
            })
            ->create();
    }
}

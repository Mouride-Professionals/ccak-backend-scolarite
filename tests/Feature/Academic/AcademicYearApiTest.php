<?php

namespace Tests\Feature\Academic;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class AcademicYearApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private array $permissions = [
        'academic_years.view',
        'academic_years.create',
        'academic_years.update',
        'academic_years.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-20 12:00:00');
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_closed_academic_year_cannot_be_set_current(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $current = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2025-10-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'is_active' => true,
        ]);
        $closed = AcademicYear::factory()->create([
            'name' => '2026-2027',
            'status' => AcademicYearStatus::CLOSED->value,
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/academic-years/{$closed->id}/set-current")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.is_current.0', 'Impossible de définir une année académique fermée comme actuelle.');

        $this->assertTrue($current->refresh()->is_current);
        $this->assertFalse($closed->refresh()->is_current);
    }

    public function test_inactive_academic_year_cannot_be_set_current(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $year = AcademicYear::factory()->create([
            'name' => '2026-2027',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => false,
            'is_active' => false,
        ]);

        $this->putJson("/api/v1/academic-years/{$year->id}/set-current")
            ->assertStatus(422)
            ->assertJsonPath('errors.is_current.0', 'Impossible de définir une année académique inactive comme actuelle.');

        $this->assertFalse($year->refresh()->is_current);
    }

    public function test_past_academic_year_cannot_be_set_current(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $year = AcademicYear::factory()->create([
            'name' => '2024-2025',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2024-10-01',
            'end_date' => '2025-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/academic-years/{$year->id}/set-current")
            ->assertStatus(422)
            ->assertJsonPath('errors.is_current.0', 'Impossible de définir une année académique passée comme actuelle.');

        $this->assertFalse($year->refresh()->is_current);
    }

    public function test_eligible_academic_year_can_be_set_current_and_unsets_previous_current_year(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $previous = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2025-10-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'is_active' => true,
        ]);
        $next = AcademicYear::factory()->create([
            'name' => '2026-2027',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/academic-years/{$next->id}/set-current")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $next->id)
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.status', AcademicYearStatus::OPEN->value);

        $this->assertFalse($previous->refresh()->is_current);
        $this->assertTrue($next->refresh()->is_current);
    }

    public function test_update_rejects_making_closed_year_current(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $year = AcademicYear::factory()->create([
            'name' => '2026-2027',
            'status' => AcademicYearStatus::CLOSED->value,
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/academic-years/{$year->id}", [
            'is_current' => true,
        ])->assertStatus(422)
            ->assertJsonPath('errors.is_current.0', 'Impossible de définir une année académique fermée comme actuelle.');

        $this->assertFalse($year->refresh()->is_current);
    }

    public function test_create_rejects_current_past_year(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $this->postJson('/api/v1/academic-years', [
            'name' => '2024-2025',
            'status' => AcademicYearStatus::OPEN->value,
            'start_date' => '2024-10-01',
            'end_date' => '2025-07-31',
            'is_current' => true,
            'is_active' => true,
        ])->assertStatus(422)
            ->assertJsonPath('errors.is_current.0', 'Impossible de définir une année académique passée comme actuelle.');

        $this->assertDatabaseMissing('academic_years', [
            'name' => '2024-2025',
        ]);
    }
}

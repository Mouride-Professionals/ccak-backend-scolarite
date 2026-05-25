<?php

namespace App\Http\Controllers\Academic;

use App\Exports\MaquetteExport;
use App\Exports\MaquetteTemplateExport;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\ImportMaquetteRequest;
use App\Imports\MaquetteImport;
use App\Models\AcademicProgram;
use App\Models\CourseUnit;
use App\Services\MaquetteImportService;
use App\Services\MaquetteParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaquetteController extends BaseApiController
{
    public function __construct(
        private readonly MaquetteParserService $parser,
        private readonly MaquetteImportService $importer,
    ) {
        $this->middleware('permission:course_units.view')->only(['index']);
        $this->middleware('permission:course_units.create')->only(['import']);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'filter.program_id' => ['sometimes', 'uuid', 'exists:academic_programs,id'],
        ]);

        $units = QueryBuilder::for(CourseUnit::class)
            ->with(['courses' => fn($q) => $q->orderBy('code'), 'academicProgram'])
            ->allowedFilters([
                AllowedFilter::exact('program_id', 'academic_program_id'),
            ])
            ->where('is_active', true)
            ->orderBy('semester_number')
            ->orderBy('code')
            ->get();

        $byProgram = $units->groupBy('academic_program_id')->map(function ($programUnits) {
            $first = $programUnits->first();
            return [
                'program_id'   => $first->academic_program_id,
                'program_name' => $first->academicProgram?->name ?? 'Programme inconnu',
                'semesters'    => $programUnits->groupBy('semester_number')->map(function ($semUnits, $semester) {
                    return [
                        'semester'     => $semester,
                        'course_units' => $semUnits->map(fn($u) => [
                            'id'          => $u->id,
                            'code'        => $u->code,
                            'name'        => $u->name,
                            'credits'     => $u->credits,
                            'coefficient' => $u->coefficient,
                            'type'        => $u->type,
                            'courses'     => $u->courses->map(fn($c) => [
                                'id'            => $c->id,
                                'code'          => $c->code,
                                'name'          => $c->name,
                                'credits'       => $c->credits,
                                'coefficient'   => $c->coefficient,
                                'hours_lecture' => $c->hours_lecture,
                                'hours_td'      => $c->hours_td,
                                'hours_tp'      => $c->hours_tp,
                                'hours_tpe'     => $c->hours_tpe,
                                'vht'           => $c->vht,
                            ]),
                        ]),
                    ];
                })->values(),
            ];
        })->values();

        return $this->success($byProgram, 'Maquette retrieved successfully');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new MaquetteTemplateExport(), 'modele-maquette-lmd.xlsx');
    }

    public function import(ImportMaquetteRequest $request): JsonResponse
    {
        $importer = new MaquetteImport();
        $sheets   = Excel::toArray($importer, $request->file('file'));

        // Merge all sheets into a single row list so multi-sheet files
        // (one sheet per semester group) are parsed in one pass.
        $rows = [];
        foreach ($sheets as $sheet) {
            $rows = array_merge($rows, $sheet, [[]]); // blank separator row between sheets
        }

        $parsed = $this->parser->parse($rows);

        if (! empty($parsed['errors'])) {
            return $this->error($parsed['errors'][0], 422, ['errors' => $parsed['errors']]);
        }

        $result = $this->importer->import($parsed, $request->validated());

        $message = $result['dry_run']
            ? "Simulation terminée : {$result['units_created']} UE et {$result['courses_created']} ECUE seraient créées."
            : "Import terminé : {$result['units_created']} UE et {$result['courses_created']} ECUE créées.";

        return $this->success($result, $message);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'filter.program_id' => ['sometimes', 'uuid', 'exists:academic_programs,id'],
        ]);

        $programId   = $request->input('filter.program_id');
        $programName = $programId ? AcademicProgram::find($programId)?->name : null;
        $filename    = 'maquette' . ($programName ? '-' . Str::slug($programName) : '') . '.xlsx';

        return Excel::download(new MaquetteExport($programId, $programName), $filename);
    }
}

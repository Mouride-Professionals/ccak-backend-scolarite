<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAcademicCalendarRequest;
use App\Http\Resources\Academic\AcademicCalendarResource;
use App\Models\AcademicCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AcademicCalendarController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:academic_calendars.create')->only('store');
        $this->middleware('permission:academic_calendars.view')->only('show');
    }

    public function store(StoreAcademicCalendarRequest $request): JsonResponse
    {
        $data = $request->validated();

        $calendar = DB::transaction(function () use ($data) {
            return AcademicCalendar::updateOrCreate(
                ['academic_year_id' => $data['academic_year_id']],
                $data
            );
        });

        return $this->success(new AcademicCalendarResource($calendar), 'Academic calendar saved', 201);
    }

    public function show(string $academic_year_id): JsonResponse
    {
        $calendar = AcademicCalendar::where('academic_year_id', $academic_year_id)->first();

        if (!$calendar) {
            return $this->error('Academic calendar not found', 404);
        }

        return $this->success(new AcademicCalendarResource($calendar));
    }
}

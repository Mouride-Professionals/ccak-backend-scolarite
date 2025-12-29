<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\DeliberationResult;

class StudentDeliberationController extends BaseApiController
{
    /**
     * Get deliberation history for a student
     * GET /api/students/{id}/deliberations
     */
    public function history(string $student_id)
    {
        $deliberations = DeliberationResult::where('student_id', $student_id)
            ->with(['deliberationSession.academicProgram', 'deliberationSession.academicYear'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success([
            'student_id' => $student_id,
            'deliberations' => $deliberations,
            'total' => $deliberations->count(),
        ]);
    }
}

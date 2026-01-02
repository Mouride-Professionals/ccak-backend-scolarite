<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_log_id' => ['required', 'uuid', 'exists:course_logs,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'uuid', 'exists:students,id'],
            'records.*.status' => ['required', 'string', 'in:PRESENT,ABSENT,LATE,EXCUSED'],
            'records.*.notes' => ['nullable', 'string'],
        ];
    }
}

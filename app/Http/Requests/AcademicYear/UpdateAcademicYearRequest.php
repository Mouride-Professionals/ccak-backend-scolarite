<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicYear;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('academic_year');

        return [
            'name' => ['sometimes', 'string', 'max:255', \Illuminate\Validation\Rule::unique('academic_years', 'name')->ignore($id)],
        ];
    }
}

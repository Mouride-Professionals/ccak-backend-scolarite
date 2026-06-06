<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicYear;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:academic_years,name',
        ];
    }
}

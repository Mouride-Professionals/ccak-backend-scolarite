<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_number' => ['required', 'string', 'max:50', 'unique:rooms,room_number'],
            'name' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:LECTURE_HALL,LAB,TD_ROOM'],
            'equipment' => ['nullable', 'array'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}

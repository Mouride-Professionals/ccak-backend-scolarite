<?php

namespace App\Http\Requests\Notification;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'recipient_ids' => ['required', 'array'],
            'recipient_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(Notification::getTypes())],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(Notification::getChannels())],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

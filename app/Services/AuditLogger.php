<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    private const SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function log(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => (string) $model->getKey(),
            'old_values' => $this->filterSensitive($oldValues),
            'new_values' => $this->filterSensitive($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
        ]);
    }

    /**
     * @param array<string, mixed>|null $values
     * @return array<string, mixed>|null
     */
    private function filterSensitive(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_diff_key($values, array_flip(self::SENSITIVE_FIELDS));
    }
}

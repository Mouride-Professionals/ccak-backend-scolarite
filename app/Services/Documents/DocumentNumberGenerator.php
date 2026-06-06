<?php

namespace App\Services\Documents;

use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentNumberGenerator
{
    private const PREFIXES = [
        'TRANSCRIPT' => 'TRANS',
        'CERTIFICATE' => 'CERT',
        'ATTESTATION' => 'ATT',
        'ID_CARD' => 'IDC',
        'DIPLOMA' => 'DIP',
    ];

    public function generate(string $type): string
    {
        $prefix = self::PREFIXES[$type] ?? 'DOC';
        $year = Carbon::now()->format('Y');
        $unique = Str::upper(Str::random(6));

        return $prefix.'-'.$year.'-'.$unique;
    }

    public function validate(string $documentNumber): bool
    {
        $pattern = '/^[A-Z]{2,5}-\d{4}-[A-Z0-9]{6}$/';

        return preg_match($pattern, $documentNumber) === 1;
    }
}

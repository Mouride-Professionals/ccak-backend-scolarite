<?php

namespace App\Services;

class MaquetteParserService
{
    // ── Our canonical template column indices (A=0 … N=13) ─────────────────
    private const DEFAULT_COL_MAP = [
        'ue_code'   => 0,
        'ue_name'   => 1,
        'ue_type'   => 2,
        'ue_cr'     => 3,
        'ue_coef'   => 4,
        'ecue_code' => 5,
        'ecue_name' => 6,
        'cm'        => 7,
        'td'        => 8,
        'tp'        => 9,
        'tpe'       => 10,
        'vht'       => 11,
        'ecue_cr'   => 12,
        'ecue_coef' => 13,
    ];

    // Normalized (lowercase) header cell values for each field
    private const HEADER_MATCHERS = [
        'ue_code'   => ['code ue', "code de l'ue", "code de l\u{2019}ue"],
        'ue_name'   => ["unités d'enseignement", "unités d\u{2019}enseignement", 'nom ue', 'intitulé ue'],
        'ue_type'   => ['type ue', 'type'],
        // "Crédits" in CAMES files is UE-level (one value per UE row)
        'ue_cr'     => ['crédits ue', 'credits ue', 'crédit ue', 'crédits', 'credits'],
        'ue_coef'   => ['coef ue', 'coefficient ue', 'coef. ue'],
        'ecue_code' => ["code de l'ec", "code de l\u{2019}ec", 'code ecue', 'code ec', 'code écue'],
        'ecue_name' => ['eléments constitutifs', 'éléments constitutifs', 'intitulé ecue', 'intitulé ec', 'ecue'],
        'cm'        => ['cm', 'cours magistral', 'cours magistraux'],
        'td'        => ['td', 'travaux dirigés'],
        'tp'        => ['tp', 'travaux pratiques'],
        'tpe'       => ['tpe', 'travail personnel', 'travail personnel encadré'],
        'vht'       => ['vht', 'volume horaire total', 'vht (h)'],
        'ecue_cr'   => ['crédits ecue', 'crédits ec', 'credits ecue', 'crédit ecue'],
        // "Coeff" in CAMES files is ECUE-level coefficient
        'ecue_coef' => ['coef ecue', 'coefficient ecue', 'coef ec', 'coef. ecue', 'coeff', 'coef'],
    ];

    private const UE_CODE_PATTERN   = '/^[A-Z]{2,8}\d{2,5}$/';
    private const ECUE_CODE_PATTERN = '/^[A-Z]{2,8}\d{3,6}$/';

    /**
     * Parse raw rows from Excel::toArray() into structured data.
     *
     * Supports both our canonical template (fixed A–N columns) and
     * CAMES/UFR-SATA client files (dynamic column layout detected from
     * the header row).
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function parse(array $rows): array
    {
        $result = [
            'program'   => ['name' => null],
            'semesters' => [],
            'warnings'  => [],
            'errors'    => [],
        ];

        $normalized = array_map(fn ($r) => $this->normalizeRow($r), $rows);
        $colMap     = $this->detectColMap($normalized);

        $currentSemester = null;
        $currentUe       = null;
        $rowIndex        = 0;

        foreach ($normalized as $row) {
            $rowIndex++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            // ── Title row: any cell contains "MAQUETTE" ──────────────────────
            foreach ($row as $v) {
                if (is_string($v) && $result['program']['name'] === null && stripos($v, 'MAQUETTE') !== false) {
                    $result['program']['name'] = $this->extractProgramName($v);
                    break;
                }
            }

            // ── Semester header: any cell matches "SEMESTRE N" ───────────────
            $semesterNum = null;
            foreach ($row as $v) {
                if (is_string($v) && preg_match('/^SEMESTRE\s+(\d+)/i', trim($v), $m)) {
                    $semesterNum = (int) $m[1];
                    break;
                }
            }
            if ($semesterNum !== null) {
                $currentUe       = $this->flushUe($currentUe, $currentSemester, $result);
                $currentSemester = $semesterNum;
                if (! isset($result['semesters'][$currentSemester])) {
                    $result['semesters'][$currentSemester] = ['course_units' => []];
                }
                continue;
            }

            // ── Column header row — skip ─────────────────────────────────────
            $rawUeCode = $this->str($row, $colMap['ue_code']);
            if (in_array(strtolower($rawUeCode), self::HEADER_MATCHERS['ue_code'], true)) {
                continue;
            }
            if (isset($colMap['ue_name'])) {
                $rawUeName = $this->str($row, $colMap['ue_name']);
                if (in_array(strtolower($rawUeName), self::HEADER_MATCHERS['ue_name'], true)) {
                    continue;
                }
            }

            // ── Skip rows before any semester header ─────────────────────────
            if ($currentSemester === null) {
                continue;
            }

            $ueCode   = $this->str($row, $colMap['ue_code']);
            $ecueCode = $this->str($row, $colMap['ecue_code']);

            // ── UE row ───────────────────────────────────────────────────────
            if ($ueCode !== '') {
                if (! preg_match(self::UE_CODE_PATTERN, $ueCode)) {
                    $result['warnings'][] = "Ligne {$rowIndex} : code UE \"{$ueCode}\" ignoré (format invalide — attendu ex. APV111).";
                    continue;
                }

                $currentUe = $this->flushUe($currentUe, $currentSemester, $result);
                $currentUe = [
                    'code'        => $ueCode,
                    'name'        => isset($colMap['ue_name']) ? $this->str($row, $colMap['ue_name']) : $ueCode,
                    'type'        => isset($colMap['ue_type']) ? ($this->str($row, $colMap['ue_type']) ?: 'OBLIGATOIRE') : 'OBLIGATOIRE',
                    'credits'     => isset($colMap['ue_cr']) ? $this->num($row, $colMap['ue_cr']) : null,
                    'coefficient' => isset($colMap['ue_coef']) ? $this->float($row, $colMap['ue_coef']) : null,
                    'courses'     => [],
                ];

                // First ECUE may be on the same row as the UE
                if ($ecueCode !== '') {
                    $ecue = $this->parseEcue($row, $colMap, $rowIndex, $result);
                    if ($ecue) {
                        $currentUe['courses'][] = $ecue;
                    }
                }
                continue;
            }

            // ── ECUE continuation row ────────────────────────────────────────
            if ($ecueCode !== '' && $currentUe !== null) {
                $ecue = $this->parseEcue($row, $colMap, $rowIndex, $result);
                if ($ecue) {
                    $currentUe['courses'][] = $ecue;
                }
                continue;
            }
        }

        // Flush last UE
        $this->flushUe($currentUe, $currentSemester, $result);

        if (empty($result['semesters'])) {
            $result['errors'][] = 'Aucun semestre détecté. Utilisez le modèle CAMES téléchargeable et assurez-vous que chaque bloc commence par "SEMESTRE N".';
        } else {
            $totalUes = array_sum(array_map(fn ($s) => count($s['course_units']), $result['semesters']));
            if ($totalUes === 0) {
                $result['errors'][] = 'Format de fichier non reconnu : des semestres ont été détectés mais aucune UE valide n\'a pu être extraite. Vérifiez que les codes UE suivent le format attendu (ex. APV111) et que la structure correspond au modèle CAMES téléchargeable.';
            }
        }

        ksort($result['semesters']);

        return $result;
    }

    // ── Layout detection ───────────────────────────────────────────────────────

    /**
     * Scan rows until we find one that looks like a column header row,
     * then build the column index map from it.
     * Falls back to DEFAULT_COL_MAP if nothing is found.
     */
    private function detectColMap(array $rows): array
    {
        foreach ($rows as $row) {
            $map = $this->tryBuildColMap($row);
            if ($map !== null) {
                return $map;
            }
        }

        return self::DEFAULT_COL_MAP;
    }

    /**
     * Given a single (already normalized) row, attempt to build a column map.
     * Returns null if the row does not look like a header row.
     */
    private function tryBuildColMap(array $row): ?array
    {
        $lower = array_map(fn ($v) => is_string($v) ? mb_strtolower(trim($v)) : '', $row);

        // A header row must have at least ue_code AND ecue_code columns.
        $ueCodeCol   = null;
        $ecueCodeCol = null;

        foreach ($lower as $col => $val) {
            if ($ueCodeCol === null && in_array($val, self::HEADER_MATCHERS['ue_code'], true)) {
                $ueCodeCol = $col;
            }
            if ($ecueCodeCol === null && in_array($val, self::HEADER_MATCHERS['ecue_code'], true)) {
                $ecueCodeCol = $col;
            }
        }

        if ($ueCodeCol === null || $ecueCodeCol === null) {
            return null;
        }

        $map = ['ue_code' => $ueCodeCol, 'ecue_code' => $ecueCodeCol];

        // Scan all other cells for known header matchers
        foreach ($lower as $col => $val) {
            if ($val === '') {
                continue;
            }
            foreach (self::HEADER_MATCHERS as $key => $matchers) {
                if (! isset($map[$key]) && in_array($val, $matchers, true)) {
                    $map[$key] = $col;
                    break;
                }
            }
        }

        // Infer ue_name from position if missing (column just before ue_code)
        if (! isset($map['ue_name']) && $ueCodeCol > 0) {
            $map['ue_name'] = $ueCodeCol - 1;
        }

        // Infer ecue_name from position if missing (column just before ecue_code)
        if (! isset($map['ecue_name']) && $ecueCodeCol > 0) {
            $map['ecue_name'] = $ecueCodeCol - 1;
        }

        return $map;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function parseEcue(array $row, array $colMap, int $rowIndex, array &$result): ?array
    {
        $code = $this->str($row, $colMap['ecue_code']);

        if (! preg_match(self::ECUE_CODE_PATTERN, $code)) {
            $result['warnings'][] = "Ligne {$rowIndex} : code ECUE \"{$code}\" ignoré (format invalide — attendu ex. APV1111).";

            return null;
        }

        $cm  = isset($colMap['cm'])  ? $this->num($row, $colMap['cm'])  : null;
        $td  = isset($colMap['td'])  ? $this->num($row, $colMap['td'])  : null;
        $tp  = isset($colMap['tp'])  ? $this->num($row, $colMap['tp'])  : null;
        $tpe = isset($colMap['tpe']) ? $this->num($row, $colMap['tpe']) : null;
        $vht = isset($colMap['vht']) ? $this->num($row, $colMap['vht']) : null;

        $computedVht = ($cm ?? 0) + ($td ?? 0) + ($tp ?? 0) + ($tpe ?? 0);
        if (($vht === null || $vht === 0) && $computedVht > 0) {
            $vht = $computedVht;
            $result['warnings'][] = "Ligne {$rowIndex} : VHT calculé automatiquement ({$vht}h) pour \"{$code}\".";
        }

        return [
            'code'          => $code,
            'name'          => isset($colMap['ecue_name']) ? $this->str($row, $colMap['ecue_name']) : $code,
            'hours_lecture' => $cm ?? 0,
            'hours_td'      => $td ?? 0,
            'hours_tp'      => $tp ?? 0,
            'hours_tpe'     => $tpe ?? 0,
            'vht'           => $vht ?? 0,
            'credits'       => isset($colMap['ecue_cr']) ? $this->num($row, $colMap['ecue_cr']) : null,
            'coefficient'   => isset($colMap['ecue_coef']) ? $this->float($row, $colMap['ecue_coef']) : null,
        ];
    }

    private function flushUe(?array $ue, ?int $semester, array &$result): null
    {
        if ($ue !== null && $semester !== null) {
            $result['semesters'][$semester]['course_units'][] = $ue;
        }

        return null;
    }

    private function normalizeRow(array $row): array
    {
        return array_pad(array_map(fn ($v) => is_string($v) ? trim($v) : ($v ?? ''), $row), 14, '');
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($v) => $v === '' || $v === null);
    }

    private function str(array $row, int $col): string
    {
        return (string) ($row[$col] ?? '');
    }

    private function num(array $row, int $col): ?int
    {
        $v = $row[$col] ?? '';
        if ($v === '' || $v === null) {
            return null;
        }

        return (int) $v;
    }

    private function float(array $row, int $col): ?float
    {
        $v = $row[$col] ?? '';
        if ($v === '' || $v === null) {
            return null;
        }

        return (float) $v;
    }

    private function extractProgramName(string $titleCell): ?string
    {
        // "MAQUETTE PÉDAGOGIQUE — Licence en Sciences" → "Licence en Sciences"
        if (str_contains($titleCell, '—')) {
            return trim(explode('—', $titleCell, 2)[1]) ?: null;
        }
        // "MAQUETTE - Licence en Sciences"
        if (preg_match('/^MAQUETTE[^-]*-(.+)$/iu', $titleCell, $m)) {
            return trim($m[1]) ?: null;
        }
        // "LA MAQUETTE DE LICENCE EN ELEVAGE..." → "LICENCE EN ELEVAGE..."
        if (preg_match('/MAQUETTE\s+(?:P[ÉE]DAGOGIQUE\s+)?(?:DE\s+|D[UE]\s+|EN\s+)?(.+)$/iu', $titleCell, $m)) {
            $name = trim($m[1]);
            return $name !== '' ? $name : null;
        }

        return null;
    }
}

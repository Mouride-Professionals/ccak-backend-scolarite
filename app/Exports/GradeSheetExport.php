<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Grade sheet Excel export.
 *
 * Column layout (A–G):
 *   A  (hidden)  course_enrollment_id
 *   B            N°
 *   C            Identité (full_name OR code anonymat)
 *   D            N° Carte (blank in anonyma mode)
 *   E            Note         ← import reads this column (index 4)
 *   F            /Max
 *   G            Statut
 */
class GradeSheetExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** @param array<int,array> $students */
    public function __construct(
        private readonly array $headerMeta,
        private readonly array $students,
        private readonly bool $useAnonyma,
    ) {}

    public function array(): array
    {
        $rows = [];

        // ── 5 university block rows ─────────────────────────────────────
        $rows[] = ['UNIVERSITÉ CHEIKH AHMADOU KABS (UCAK) — FICHE DE NOTES', null, null, null, null, null, null];
        $rows[] = ['Matière : ' . ($this->headerMeta['course_code'] ?? '') . ' — ' . ($this->headerMeta['course_name'] ?? ''), null, null, null, null, null, null];
        $rows[] = ['Session : ' . ($this->headerMeta['session_label'] ?? ''), null, null, null, null, null, null];
        $rows[] = ['Année académique : ' . ($this->headerMeta['academic_year_name'] ?? '') . ($this->headerMeta['semester_number'] ? '  |  Semestre : S' . $this->headerMeta['semester_number'] : ''), null, null, null, null, null, null];
        $rows[] = ['Date : ' . ($this->headerMeta['date'] ?? ''), null, null, null, null, null, null];

        // ── Column header row ────────────────────────────────────────────
        $rows[] = [
            'ID_INSCRIPTION', // hidden col A
            'N°',
            $this->useAnonyma ? 'Code anonymat' : 'Nom complet',
            $this->useAnonyma ? '' : 'N° Carte',
            'Note',
            '/Max',
            'Statut',
        ];

        // ── Data rows ────────────────────────────────────────────────────
        foreach ($this->students as $i => $student) {
            $rows[] = [
                $student['course_enrollment_id'],
                $i + 1,
                $this->useAnonyma ? ($student['exam_number'] ?? '') : ($student['full_name'] ?? ''),
                $this->useAnonyma ? '' : ($student['student_number'] ?? ''),
                $student['score'] ?? '',
                $student['max_score'] ?? 20,
                $student['status'] ?? '',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Fiche de Notes';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 2,  // hidden
            'B' => 5,
            'C' => 38,
            'D' => 16,
            'E' => 10,
            'F' => 8,
            'G' => 14,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = 5 + 1 + count($this->students); // header + col headers + data

                // Hide col A (course_enrollment_id)
                $sheet->getColumnDimension('A')->setVisible(false);

                // ── Style university block (rows 1–5) ──────────────────
                $sheet->mergeCells("B1:G1");
                $sheet->mergeCells("B2:G2");
                $sheet->mergeCells("B3:G3");
                $sheet->mergeCells("B4:G4");
                $sheet->mergeCells("B5:G5");

                $sheet->getStyle('B1:G1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00365F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle('B2:G5')->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['argb' => 'FF3C3C3C']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F6FC']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // ── Style column header row (row 6) ────────────────────
                $sheet->getStyle('B6:G6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF3C3C3C']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2F0D9']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF375623']]],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(18);

                // ── Style data rows (rows 7+) ──────────────────────────
                if (count($this->students) > 0) {
                    $dataRange = 'B7:G' . $totalRows;

                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']],
                        ],
                    ]);

                    // Alternate row background
                    foreach (range(7, $totalRows) as $row) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
                            ]);
                        }
                    }

                    // Center N°, Note, /Max, Statut columns
                    $sheet->getStyle("B7:B{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E7:G{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->freezePane('B7');
            },
        ];
    }
}

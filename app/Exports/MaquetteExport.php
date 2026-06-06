<?php

namespace App\Exports;

use App\Models\CourseUnit;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MaquetteExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    private array $rows = [];

    private array $rowMeta = [];

    public function __construct(
        private readonly ?string $programId = null,
        private readonly ?string $programName = null,
    ) {}

    public function array(): array
    {
        $units = CourseUnit::with(['courses' => fn ($q) => $q->orderBy('code'), 'academicProgram'])
            ->where('is_active', true)
            ->when($this->programId, fn ($q) => $q->where('academic_program_id', $this->programId))
            ->orderBy('semester_number')
            ->orderBy('code')
            ->get();

        $title = 'MAQUETTE PÉDAGOGIQUE'.($this->programName ? ' — '.$this->programName : '');
        $this->addRow([$title, ...array_fill(0, 13, null)], 'title');
        $this->addRow(array_fill(0, 14, null), 'empty');

        foreach ($units->groupBy('academic_program_id') as $programUnits) {
            $first = $programUnits->first();
            $programName = $first->academicProgram?->name ?? 'Programme inconnu';

            if (! $this->programId) {
                $this->addRow([$programName, ...array_fill(0, 12, null)], 'program_header');
            }

            foreach ($programUnits->groupBy('semester_number') as $semester => $semUnits) {
                $totalCr = $semUnits->sum('credits');
                $this->addRow(["SEMESTRE {$semester}", ...array_fill(0, 12, null), "{$totalCr} crédits"], 'semester_header');
                $this->addRow([
                    'Code UE', 'Nom UE', 'Type UE', 'Crédits UE', 'Coef UE',
                    'Code ECUE', 'Intitulé ECUE', 'CM', 'TD', 'TP', 'TPE', 'VHT', 'Crédits ECUE', 'Coef ECUE',
                ], 'col_headers');

                foreach ($semUnits as $unit) {
                    $courses = $unit->courses;
                    $count = max($courses->count(), 1);

                    for ($i = 0; $i < $count; $i++) {
                        $c = $courses[$i] ?? null;
                        $vht = $c ? ($c->vht ?? ($c->hours_lecture + $c->hours_td + ($c->hours_tp ?? 0) + ($c->hours_tpe ?? 0))) : null;

                        $this->addRow([
                            $i === 0 ? $unit->code : null,
                            $i === 0 ? $unit->name : null,
                            $i === 0 ? $unit->type : null,
                            $i === 0 ? $unit->credits : null,
                            $i === 0 ? $unit->coefficient : null,
                            $c ? $c->code : null,
                            $c ? $c->name : null,
                            $c ? $c->hours_lecture : null,
                            $c ? $c->hours_td : null,
                            $c ? ($c->hours_tp ?? 0) : null,
                            $c ? ($c->hours_tpe ?? 0) : null,
                            $c ? $vht : null,
                            $c ? $c->credits : null,
                            $c ? $c->coefficient : null,
                        ], $i === 0 ? 'ue_row' : 'ecue_row');
                    }
                }

                $this->addRow(array_fill(0, 14, null), 'empty');
            }
        }

        return $this->rows;
    }

    public function title(): string
    {
        return 'Maquette';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 40, 'C' => 14, 'D' => 12, 'E' => 10,
            'F' => 20, 'G' => 46, 'H' => 8,  'I' => 8,  'J' => 8,
            'K' => 8,  'L' => 8,  'M' => 14, 'N' => 10,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'N';
                $total = count($this->rowMeta);

                for ($i = 0; $i < $total; $i++) {
                    $row = $i + 1;
                    $range = "A{$row}:{$lastCol}{$row}";

                    match ($this->rowMeta[$i]) {
                        'title' => $this->styleTitle($sheet, $range, $row),
                        'program_header' => $this->styleProgramHeader($sheet, $range, $row),
                        'semester_header' => $this->styleSemesterHeader($sheet, $row),
                        'col_headers' => $this->styleColHeaders($sheet, $range, $row),
                        'ue_row' => $this->styleUeRow($sheet, $range),
                        'ecue_row' => $this->styleEcueRow($sheet, $range),
                        default => null,
                    };
                }

                // Right-align numeric columns H–N
                $sheet->getStyle("H1:N{$total}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Freeze below title + blank rows
                $sheet->freezePane('A3');
            },
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function addRow(array $row, string $meta): void
    {
        $this->rows[] = $row;
        $this->rowMeta[] = $meta;
    }

    private function styleProgramHeader($sheet, string $range, int $row): void
    {
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00648C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function styleTitle($sheet, string $range, int $row): void
    {
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00365F']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
    }

    private function styleSemesterHeader($sheet, int $row): void
    {
        $sheet->mergeCells("A{$row}:M{$row}");
        $fullRange = "A{$row}:N{$row}";
        $sheet->getStyle($fullRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00365F']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function styleColHeaders($sheet, string $range, int $row): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF3C3C3C']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F6FC']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBBBBB']],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(16);
    }

    private function styleUeRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5F2FD']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBDDEE']],
            ],
        ]);
    }

    private function styleEcueRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
        ]);
    }
}

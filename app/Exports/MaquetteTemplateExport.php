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
 * Generates a blank importable maquette template following the CAMES/LMD standard.
 *
 * Columns A–N (14 cols):
 *   A  Code UE | B  Nom UE | C  Type UE | D  Crédits UE | E  Coef UE |
 *   F  Code ECUE | G  Intitulé ECUE | H  CM | I  TD | J  TP | K  TPE | L  VHT | M  Crédits ECUE | N  Coef ECUE
 */
class MaquetteTemplateExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    private array $rows = [];

    private array $rowMeta = [];

    private const HEADERS = [
        'Code UE', 'Nom UE', 'Type UE', 'Crédits UE', 'Coef UE',
        'Code ECUE', 'Intitulé ECUE', 'CM', 'TD', 'TP', 'TPE', 'VHT', 'Crédits ECUE', 'Coef ECUE',
    ];

    // Example rows (grayed out) to guide the user
    private const EXAMPLES = [
        1 => [
            ['XXX111', 'Nom de l\'UE 1',  'OBLIGATOIRE', 6, 3, 'XXX1111', 'Nom de l\'ECUE 1', 20, 10, 0, 10, 40, 2, 1],
            [null,     null,              null,          null, null, 'XXX1112', 'Nom de l\'ECUE 2', 15, 5,  5, 15, 40, 2, 1],
            ['XXX112', 'Nom de l\'UE 2',  'OBLIGATOIRE', 4, 2, 'XXX1121', 'Nom de l\'ECUE 3', 20, 0,  0, 20, 40, 2, 2],
        ],
        2 => [
            ['XXX121', 'Nom de l\'UE 3',  'OBLIGATOIRE', 6, 3, 'XXX1211', 'Nom de l\'ECUE 4', 20, 10, 0, 10, 40, 2, 1],
            [null,     null,              null,          null, null, 'XXX1212', 'Nom de l\'ECUE 5', 20, 10, 5, 5,  40, 2, 1],
        ],
    ];

    public function array(): array
    {
        // Title
        $this->addRow(
            ['MAQUETTE PÉDAGOGIQUE — [Remplacez par le nom du programme]', ...array_fill(0, 13, null)],
            'title'
        );

        // Instructions
        $this->addRow(
            ['Instructions : remplacez les lignes grisées par vos données. Ne modifiez pas les en-têtes ni les lignes "SEMESTRE N".', ...array_fill(0, 13, null)],
            'instructions'
        );

        $this->addRow(array_fill(0, 14, null), 'empty');

        foreach (self::EXAMPLES as $semester => $exampleRows) {
            $this->addRow(["SEMESTRE {$semester}", ...array_fill(0, 13, null)], 'semester_header');
            $this->addRow(self::HEADERS, 'col_headers');

            foreach ($exampleRows as $i => $exRow) {
                $this->addRow($exRow, $exRow[0] !== null ? 'ue_example' : 'ecue_example');
            }

            $this->addRow(array_fill(0, 14, null), 'empty');
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
            'A' => 16, 'B' => 38, 'C' => 14, 'D' => 12, 'E' => 10,
            'F' => 18, 'G' => 42, 'H' => 7,  'I' => 7,  'J' => 7,
            'K' => 7,  'L' => 8,  'M' => 14, 'N' => 10,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $total = count($this->rowMeta);

                for ($i = 0; $i < $total; $i++) {
                    $row = $i + 1;
                    $range = "A{$row}:N{$row}";

                    match ($this->rowMeta[$i]) {
                        'title' => $this->styleTitle($sheet, $range, $row),
                        'instructions' => $this->styleInstructions($sheet, $range, $row),
                        'semester_header' => $this->styleSemesterHeader($sheet, $row),
                        'col_headers' => $this->styleColHeaders($sheet, $range, $row),
                        'ue_example' => $this->styleExample($sheet, $range, true),
                        'ecue_example' => $this->styleExample($sheet, $range, false),
                        default => null,
                    };
                }

                // Right-align numeric columns H–N
                $sheet->getStyle("H1:N{$total}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->freezePane('A4');
            },
        ];
    }

    // ── Style helpers ──────────────────────────────────────────────────────────

    private function addRow(array $row, string $meta): void
    {
        $this->rows[] = $row;
        $this->rowMeta[] = $meta;
    }

    private function styleTitle($sheet, string $range, int $row): void
    {
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00365F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
    }

    private function styleInstructions($sheet, string $range, int $row): void
    {
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF555555']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8DC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(28);
    }

    private function styleSemesterHeader($sheet, int $row): void
    {
        $range = "A{$row}:N{$row}";
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00648C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function styleColHeaders($sheet, string $range, int $row): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF3C3C3C']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F6FC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBBBBB']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(16);
    }

    private function styleExample($sheet, string $range, bool $isUeRow): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF999999']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $isUeRow ? 'FFEFF7FF' : 'FFFAFAFA']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFEEEEEE']]],
        ]);
    }
}

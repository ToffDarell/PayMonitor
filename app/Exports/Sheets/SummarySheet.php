<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromArray, WithColumnWidths, WithDrawings, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        protected array $summary,
        protected array $filters,
        protected string $coopName,
        protected ?string $logoPath,
        protected string $verificationCode,
        protected string $generatedAt,
        protected string $currencySymbol = "\u{20B1}",
    ) {
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Spacer column to center content
            'B' => 45,
            'C' => 35,
            'D' => 20,
        ];
    }

    public function drawings(): array
    {
        if (! filled($this->logoPath)) {
            return [];
        }

        $logoFullPath = storage_path('app/public/'.ltrim((string) $this->logoPath, '/'));

        if (! file_exists($logoFullPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Cooperative Logo');
        $drawing->setPath($logoFullPath);
        $drawing->setHeight(70);
        $drawing->setCoordinates('B1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        return [$drawing];
    }

    public function array(): array
    {
        $currency = $this->currencySymbol;

        return [
            ['', '', '', ''],
            ['', $this->coopName, '', ''],
            ['', 'Cooperative Lending Report', '', ''],
            ['', 'Generated '.$this->generatedAt, '', ''],
            ['', '', '', ''],
            ['', 'REPORT DETAILS', '', ''],
            ['', 'Branch', $this->filters['branch'] ?? 'All Branches', ''],
            ['', 'Date From', $this->filters['date_from'] ?? 'All Dates', ''],
            ['', 'Date To', $this->filters['date_to'] ?? 'All Dates', ''],
            ['', 'Generated At', $this->generatedAt, ''],
            ['', '', '', ''],
            ['', 'PORTFOLIO SUMMARY', '', ''],
            ['', 'Total Loans Released (Count)', number_format((float) ($this->summary['total_loans_count'] ?? 0)), ''],
            ['', 'Total Loans Released (Amount)', $currency.number_format((float) ($this->summary['total_loans_amount'] ?? 0), 2), ''],
            ['', 'Total Collections', $currency.number_format((float) ($this->summary['total_collections'] ?? 0), 2), ''],
            ['', 'Outstanding Balance', $currency.number_format((float) ($this->summary['outstanding_balance'] ?? 0), 2), ''],
            ['', 'Overdue Loans', number_format((float) ($this->summary['overdue_loans'] ?? 0)), ''],
            ['', 'Interest Income / Profit', $currency.number_format((float) ($this->summary['interest_income'] ?? 0), 2), ''],
            ['', 'Fully Paid Loans', number_format((float) ($this->summary['fully_paid'] ?? 0)), ''],
            ['', '', '', ''],
            ['', 'DS '."\u{00B7}".' '.$this->verificationCode, '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $primary = '1F2937'; // Changed from bright blue to dark slate
        $lightGray = 'F9FAFB'; // Neutral light grey
        $darkText = '1A1A1A';
        $grayBg = 'F3F4F6';

        return [
            1 => ['font' => ['size' => 8]],
            2 => [
                'font' => [
                    'bold' => true,
                    'size' => 18,
                    'color' => ['argb' => 'FF'.$darkText],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            3 => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                    'color' => ['argb' => 'FF'.$primary],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            4 => [
                'font' => [
                    'italic' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FF6B7280'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            6 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'B7:C10' => [
                'font' => ['size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.$lightGray],
                ],
            ],
            'B7' => ['font' => ['bold' => true, 'size' => 10]],
            'B8' => ['font' => ['bold' => true, 'size' => 10]],
            'B9' => ['font' => ['bold' => true, 'size' => 10]],
            'B10' => ['font' => ['bold' => true, 'size' => 10]],
            12 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'B13:C19' => [
                'font' => ['size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.$grayBg],
                ],
            ],
            'B13' => ['font' => ['bold' => true, 'size' => 10]],
            'B14' => ['font' => ['bold' => true, 'size' => 10]],
            'B15' => ['font' => ['bold' => true, 'size' => 10]],
            'B16' => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                ],
            ],
            'B17' => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                ],
            ],
            'B18' => ['font' => ['bold' => true, 'size' => 10]],
            'B19' => ['font' => ['bold' => true, 'size' => 10]],
            'C13:C19' => [
                'font' => [
                    'bold' => true, 
                    'size' => 10,
                    'color' => ['argb' => 'FF'.$darkText],
                ],
            ],
            21 => [
                'font' => [
                    'size' => 9,
                    'italic' => true,
                    'color' => ['argb' => 'FFD1D5DB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('B1:D21')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getRowDimension(1)->setRowHeight(80);
                $sheet->getRowDimension(2)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(12)->setRowHeight(22);
                $sheet->getRowDimension(21)->setRowHeight(18);

                $sheet->mergeCells('B2:C2');
                $sheet->mergeCells('B3:C3');
                $sheet->mergeCells('B4:C4');
                $sheet->mergeCells('B6:C6');
                $sheet->mergeCells('B12:C12');

                $sheet->getStyle('B6:C10')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF1F2937'],
                        ],
                    ],
                ]);

                $sheet->getStyle('B12:C19')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF1F2937'],
                        ],
                    ],
                ]);

                $sheet->getStyle('C7:C10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('C13:C19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getHeaderFooter()->setOddHeader('&L&B'.$this->coopName.'&RCooperative Lending Report');
                $sheet->getHeaderFooter()->setOddFooter(
                    '&L&8'.$this->coopName
                    .'&C&8DS '."\u{00B7}".' '.$this->verificationCode
                    .'&R&8Page &P of &N'
                );

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setRight(0.5);
            },
        ];
    }
}

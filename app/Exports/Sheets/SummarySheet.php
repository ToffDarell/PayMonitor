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
            'A' => 45,
            'B' => 35,
            'C' => 20,
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
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        return [$drawing];
    }

    public function array(): array
    {
        $currency = $this->currencySymbol;

        return [
            ['', '', ''],
            [$this->coopName, '', ''],
            ['Cooperative Lending Report', '', ''],
            ['Generated '.$this->generatedAt, '', ''],
            ['', '', ''],
            ['REPORT DETAILS', '', ''],
            ['Branch', $this->filters['branch'] ?? 'All Branches', ''],
            ['Date From', $this->filters['date_from'] ?? 'All Dates', ''],
            ['Date To', $this->filters['date_to'] ?? 'All Dates', ''],
            ['Generated At', $this->generatedAt, ''],
            ['', '', ''],
            ['PORTFOLIO SUMMARY', '', ''],
            ['Total Loans Released (Count)', number_format((float) ($this->summary['total_loans_count'] ?? 0)), ''],
            ['Total Loans Released (Amount)', $currency.number_format((float) ($this->summary['total_loans_amount'] ?? 0), 2), ''],
            ['Total Collections', $currency.number_format((float) ($this->summary['total_collections'] ?? 0), 2), ''],
            ['Outstanding Balance', $currency.number_format((float) ($this->summary['outstanding_balance'] ?? 0), 2), ''],
            ['Overdue Loans', number_format((float) ($this->summary['overdue_loans'] ?? 0)), ''],
            ['Interest Income / Profit', $currency.number_format((float) ($this->summary['interest_income'] ?? 0), 2), ''],
            ['Fully Paid Loans', number_format((float) ($this->summary['fully_paid'] ?? 0)), ''],
            ['', '', ''],
            ['DS '."\u{00B7}".' '.$this->verificationCode, '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $blue = '4F46E5';
        $lightBlue = 'EEF2FF';
        $darkText = '1A1A1A';
        $grayBg = 'F8FAFC';

        return [
            1 => ['font' => ['size' => 8]],
            2 => [
                'font' => [
                    'bold' => true,
                    'size' => 18,
                    'color' => ['argb' => 'FF'.$darkText],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            3 => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                    'color' => ['argb' => 'FF'.$blue],
                ],
            ],
            4 => [
                'font' => [
                    'italic' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FF6B7280'],
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
                    'startColor' => ['argb' => 'FF'.$blue],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A7:B10' => [
                'font' => ['size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.$lightBlue],
                ],
            ],
            'A7' => ['font' => ['bold' => true, 'size' => 10]],
            'A8' => ['font' => ['bold' => true, 'size' => 10]],
            'A9' => ['font' => ['bold' => true, 'size' => 10]],
            'A10' => ['font' => ['bold' => true, 'size' => 10]],
            12 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.$blue],
                ],
            ],
            'A13:B19' => [
                'font' => ['size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.$grayBg],
                ],
            ],
            'A13' => ['font' => ['bold' => true, 'size' => 10]],
            'A14' => ['font' => ['bold' => true, 'size' => 10]],
            'A15' => ['font' => ['bold' => true, 'size' => 10]],
            'A16' => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFDC2626'],
                ],
            ],
            'A17' => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFDC2626'],
                ],
            ],
            'A18' => ['font' => ['bold' => true, 'size' => 10]],
            'A19' => ['font' => ['bold' => true, 'size' => 10]],
            'B13:B19' => [
                'font' => ['bold' => true, 'size' => 10],
            ],
            'B14' => ['font' => ['color' => ['argb' => 'FF059669'], 'bold' => true]],
            'B15' => ['font' => ['color' => ['argb' => 'FF059669'], 'bold' => true]],
            'B16' => ['font' => ['color' => ['argb' => 'FFDC2626'], 'bold' => true]],
            'B18' => ['font' => ['color' => ['argb' => 'FF059669'], 'bold' => true]],
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

                $sheet->getStyle('A1:C21')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getRowDimension(1)->setRowHeight(80);
                $sheet->getRowDimension(2)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(12)->setRowHeight(22);
                $sheet->getRowDimension(21)->setRowHeight(18);

                $sheet->mergeCells('A2:B2');
                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells('A4:B4');
                $sheet->mergeCells('A6:C6');
                $sheet->mergeCells('A12:C12');

                $sheet->getStyle('A6:B10')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF4F46E5'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFC7D2FE'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A12:B19')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF4F46E5'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFE5E7EB'],
                        ],
                    ],
                ]);

                $sheet->getStyle('B7:B10')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B13:B19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

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

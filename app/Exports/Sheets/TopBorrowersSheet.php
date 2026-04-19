<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TopBorrowersSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $topBorrowers,
        protected string $coopName,
        protected string $verificationCode,
        protected string $currencySymbol = "\u{20B1}",
    ) {
    }

    public function title(): string
    {
        return 'Top Borrowers';
    }

    public function array(): array
    {
        $rows = [
            [$this->coopName, '', '', '', ''],
            ['Top Borrowers by Outstanding Balance', '', '', '', ''],
            ['', '', '', '', ''],
            ['#', 'Member No.', 'Name', 'Active Loans', 'Total Outstanding'],
        ];

        if ($this->topBorrowers->isEmpty()) {
            $rows[] = ['', '', 'No borrower balances found for the selected filters.', '', ''];
        } else {
            foreach ($this->topBorrowers->values() as $index => $borrower) {
                /** @var Member $borrower */
                $rows[] = [
                    (string) ($index + 1),
                    $borrower->member_number,
                    $borrower->full_name,
                    number_format((float) ($borrower->active_loans_count ?? 0)),
                    $this->currencySymbol.number_format((float) ($borrower->total_outstanding ?? 0), 2),
                ];
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 18,
            'C' => 28,
            'D' => 14,
            'E' => 20,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
            ],
            2 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FF4F46E5'],
                ],
            ],
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $dataStartRow = 5;
                $dataEndRow = max($highestRow, $dataStartRow);

                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');

                $sheet->getStyle('A1:E'.$highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$dataStartRow}:D{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$dataStartRow}:E{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    $fill = $row % 2 === 0 ? 'FFF8FAFC' : 'FFFFFFFF';

                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $fill],
                        ],
                        'borders' => [
                            'bottom' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFE5E7EB'],
                            ],
                        ],
                    ]);
                }

                $sheet->getStyle("A4:E{$dataEndRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF4F46E5'],
                        ],
                    ],
                ]);

                $sheet->getHeaderFooter()->setOddHeader('&L&B'.$this->coopName.'&RTop Borrowers');
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

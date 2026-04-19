<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Loan;
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

class OverdueLoansSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $overdueLoans,
        protected string $coopName,
        protected string $verificationCode,
        protected string $currencySymbol = "\u{20B1}",
    ) {
    }

    public function title(): string
    {
        return 'Overdue Loans';
    }

    public function array(): array
    {
        $rows = [
            [$this->coopName, '', '', '', ''],
            ['Overdue Loans', '', '', '', ''],
            ['', '', '', '', ''],
            ['Member', 'Loan No.', 'Balance', 'Due Date', 'Days Overdue'],
        ];

        if ($this->overdueLoans->isEmpty()) {
            $rows[] = ['No overdue loans found.', '', '', '', ''];
        } else {
            foreach ($this->overdueLoans as $loan) {
                /** @var Loan $loan */
                $rows[] = [
                    $loan->member?->full_name ?? 'N/A',
                    $loan->loan_number,
                    $this->currencySymbol.number_format((float) $loan->outstanding_balance, 2),
                    $loan->due_date?->format('M d, Y') ?? 'N/A',
                    (string) ($loan->due_date?->diffInDays(now()) ?? 0),
                ];
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 14,
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
                $sheet->getStyle("C{$dataStartRow}:C{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E{$dataStartRow}:E{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    $fill = $row % 2 === 0 ? 'FFFEE2E2' : 'FFFEF2F2';

                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $fill],
                        ],
                        'borders' => [
                            'bottom' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFFECACA'],
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

                $sheet->getHeaderFooter()->setOddHeader('&L&B'.$this->coopName.'&ROverdue Loans');
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

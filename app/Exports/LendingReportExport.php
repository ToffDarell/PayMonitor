<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\CollectionsSheet;
use App\Exports\Sheets\LoansByTypeSheet;
use App\Exports\Sheets\OverdueLoansSheet;
use App\Exports\Sheets\SummarySheet;
use App\Exports\Sheets\TopBorrowersSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LendingReportExport implements WithMultipleSheets
{
    public function __construct(
        protected array $summary,
        protected Collection $loansByType,
        protected Collection $collectionsByMonth,
        protected Collection $overdueLoans,
        protected Collection $topBorrowers,
        protected array $filters,
        protected string $coopName,
        protected ?string $logoPath = null,
        protected string $signature = '',
        protected string $verificationCode = '',
        protected string $generatedAt = '',
        protected string $currencySymbol = "\u{20B1}",
    ) {
    }

    public function sheets(): array
    {
        return [
            new SummarySheet(
                summary: $this->summary,
                filters: $this->filters,
                coopName: $this->coopName,
                logoPath: $this->logoPath,
                verificationCode: $this->verificationCode,
                generatedAt: $this->generatedAt,
                currencySymbol: $this->currencySymbol,
            ),
            new LoansByTypeSheet(
                loansByType: $this->loansByType,
                coopName: $this->coopName,
                verificationCode: $this->verificationCode,
                currencySymbol: $this->currencySymbol,
            ),
            new CollectionsSheet(
                collectionsByMonth: $this->collectionsByMonth,
                coopName: $this->coopName,
                verificationCode: $this->verificationCode,
                currencySymbol: $this->currencySymbol,
            ),
            new OverdueLoansSheet(
                overdueLoans: $this->overdueLoans,
                coopName: $this->coopName,
                verificationCode: $this->verificationCode,
                currencySymbol: $this->currencySymbol,
            ),
            new TopBorrowersSheet(
                topBorrowers: $this->topBorrowers,
                coopName: $this->coopName,
                verificationCode: $this->verificationCode,
                currencySymbol: $this->currencySymbol,
            ),
        ];
    }
}

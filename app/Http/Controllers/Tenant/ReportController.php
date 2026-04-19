<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\LendingReportExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\TenantSetting;
use App\Services\ReportSignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', $this->buildReportData($request));
    }

    public function export(Request $request): Response
    {
        return match ($this->validateExportFormat($request)) {
            'excel' => $this->downloadExcel($this->buildReportData($request)),
            'pdf' => $this->downloadPdf($this->buildReportData($request)),
            default => $this->downloadCsv($this->buildReportData($request)),
        };
    }

    public function exportPdf(Request $request): Response
    {
        return $this->downloadPdf($this->buildReportData($request));
    }

    public function exportExcel(Request $request): Response
    {
        return $this->downloadExcel($this->buildReportData($request));
    }

    private function validateExportFormat(Request $request): string
    {
        $validated = $request->validate([
            'format' => ['nullable', 'string', 'in:csv,excel,pdf'],
        ]);

        return $validated['format'] ?? 'csv';
    }

    private function downloadCsv(array $reportData): StreamedResponse
    {
        $sections = $this->reportSections($reportData, true);
        $metadataRows = $this->reportMetadataRows($reportData);
        $filename = 'lending-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($metadataRows, $sections): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            $writeRow = static function (array $row) use ($handle): void {
                fputcsv($handle, $row);
            };

            $writeRow(['PayMonitor - Lending Report']);

            foreach ($metadataRows as $row) {
                $writeRow([$row['label'], $row['value']]);
            }

            foreach ($sections as $section) {
                $writeRow([]);
                $writeRow([$section['title']]);
                $writeRow($section['headers']);

                foreach ($section['rows'] as $row) {
                    $writeRow($row);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function downloadExcel(array $reportData): Response
    {
        $payload = $this->exportPayload($reportData);
        $filename = 'lending-report-'.now()->format('Ymd-His').'.xlsx';

        $export = new LendingReportExport(
            summary: $payload['summary'],
            loansByType: $reportData['loanBreakdown'],
            collectionsByMonth: $reportData['collectionsByMonth'],
            overdueLoans: $reportData['overdueLoans'],
            topBorrowers: $reportData['topBorrowers'],
            filters: $payload['filters'],
            coopName: $payload['coopName'],
            logoPath: $payload['logo'],
            verificationCode: $payload['verificationCode'],
            generatedAt: $payload['generatedAt'],
            currencySymbol: $payload['currencySymbol'],
        );

        return Excel::download($export, $filename);
    }

    private function downloadPdf(array $reportData): Response
    {
        $payload = $this->exportPayload($reportData);
        $filename = 'lending-report-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('reports.pdf', $payload);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    private function exportPayload(array $reportData): array
    {
        $branding = $this->exportBranding();
        $signatureService = app(ReportSignatureService::class);
        $generatedAt = now()->format('Y-m-d H:i:s');
        $filters = [
            'branch' => $reportData['selectedBranchName'],
            'date_from' => $reportData['filters']['date_from'] ?? 'All Dates',
            'date_to' => $reportData['filters']['date_to'] ?? 'All Dates',
            'generated_at' => $generatedAt,
        ];
        $summary = [
            'total_loans_count' => (int) $reportData['totalLoansReleasedCount'],
            'total_loans_amount' => (float) $reportData['totalLoansReleasedAmount'],
            'total_collections' => (float) $reportData['totalCollections'],
            'outstanding_balance' => (float) $reportData['totalOutstandingBalance'],
            'overdue_loans' => (int) $reportData['totalOverdueLoans'],
            'interest_income' => (float) $reportData['interestIncome'],
            'fully_paid' => (int) $reportData['fullyPaidLoansCount'],
        ];
        $signatureData = [
            'coop_name' => $branding['coopName'],
            'generated_at' => $generatedAt,
            'report_type' => 'lending_report',
            'filters' => $filters,
            'summary' => $summary,
        ];
        $signature = $signatureService->generateSignature($signatureData);
        $verificationCode = $signatureService->generateVerificationCode($signature);

        return [
            'coopName' => $branding['coopName'],
            'logo' => $branding['logo'],
            'tagline' => $branding['tagline'],
            'currencySymbol' => $branding['currencySymbol'],
            'generatedAt' => $generatedAt,
            'filters' => $filters,
            'summary' => $summary,
            'loansByType' => $reportData['loanBreakdown']->map(static fn (array $item): object => (object) [
                'name' => $item['name'],
                'count' => (int) $item['count'],
                'total_principal' => (float) $item['total_principal'],
                'total_payable' => (float) $item['total_payable'],
            ])->values(),
            'collectionsByMonth' => $reportData['collectionsByMonth']->map(static fn (array $item): object => (object) [
                'month' => $item['month'],
                'count' => (int) $item['payments_count'],
                'total' => (float) $item['total_collected'],
            ])->values(),
            'overdueLoans' => $reportData['overdueLoans'],
            'topBorrowers' => $reportData['topBorrowers'],
            'verificationCode' => $verificationCode,
        ];
    }

    private function exportBranding(): array
    {
        $settings = TenantSetting::allKeyed();
        $coopName = trim((string) ($settings['cooperative_name'] ?? (tenant()?->name ?? 'PayMonitor')));
        $currencySymbol = trim((string) ($settings['currency_symbol'] ?? ''));

        if ($coopName === '') {
            $coopName = tenant()?->name ?? 'PayMonitor';
        }

        if (in_array($currencySymbol, ['', 'â‚±', 'Ã¢â€šÂ±'], true)) {
            $currencySymbol = "\u{20B1}";
        }

        return [
            'coopName' => $coopName,
            'logo' => filled($settings['logo_path'] ?? null) ? trim((string) $settings['logo_path']) : null,
            'tagline' => trim((string) ($settings['cooperative_tagline'] ?? '')),
            'currencySymbol' => $currencySymbol,
        ];
    }

    private function reportMetadataRows(array $reportData): array
    {
        return [
            ['label' => 'Tenant', 'value' => $this->exportBranding()['coopName']],
            ['label' => 'Branch', 'value' => $reportData['selectedBranchName']],
            ['label' => 'Date From', 'value' => $reportData['filters']['date_from'] ?? 'All Dates'],
            ['label' => 'Date To', 'value' => $reportData['filters']['date_to'] ?? 'All Dates'],
            ['label' => 'Generated At', 'value' => now()->format('Y-m-d H:i:s')],
        ];
    }

    private function reportSections(array $reportData, bool $withCurrencySymbol): array
    {
        $currency = fn (float $amount): string => $this->formatCurrency($amount, $withCurrencySymbol);

        $loanBreakdownRows = $reportData['loanBreakdown']->map(
            fn (array $item): array => [
                $item['name'],
                number_format((int) $item['count']),
                $currency((float) $item['total_principal']),
                $currency((float) $item['total_payable']),
            ],
        )->all();

        $collectionsByMonthRows = $reportData['collectionsByMonth']->map(
            fn (array $month): array => [
                $month['month'],
                number_format((int) $month['payments_count']),
                $currency((float) $month['total_collected']),
            ],
        )->all();

        $overdueLoanRows = $reportData['overdueLoans']->map(
            fn (Loan $loan): array => [
                $loan->member?->full_name ?? 'Unknown Member',
                $loan->loan_number,
                $currency((float) $loan->outstanding_balance),
                $loan->due_date?->format('Y-m-d') ?? 'N/A',
                $loan->due_date ? (string) $loan->due_date->diffInDays(today()) : 'N/A',
            ],
        )->all();

        $topBorrowerRows = $reportData['topBorrowers']->map(
            fn (Member $borrower): array => [
                $borrower->member_number,
                $borrower->full_name,
                number_format((int) ($borrower->active_loans_count ?? 0)),
                $currency((float) ($borrower->total_outstanding ?? 0)),
            ],
        )->all();

        return [
            [
                'title' => 'SUMMARY',
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Total Loans Released (Count)', number_format((int) $reportData['totalLoansReleasedCount'])],
                    ['Total Loans Released (Amount)', $currency((float) $reportData['totalLoansReleasedAmount'])],
                    ['Total Collections', $currency((float) $reportData['totalCollections'])],
                    ['Outstanding Balance', $currency((float) $reportData['totalOutstandingBalance'])],
                    ['Overdue Loans', number_format((int) $reportData['totalOverdueLoans'])],
                    ['Interest Income / Profit', $currency((float) $reportData['interestIncome'])],
                    ['Fully Paid Loans', number_format((int) $reportData['fullyPaidLoansCount'])],
                ],
            ],
            [
                'title' => 'LOAN RELEASES BY TYPE',
                'headers' => ['Loan Type', 'Count', 'Total Principal', 'Total Payable'],
                'rows' => $loanBreakdownRows !== []
                    ? $loanBreakdownRows
                    : [['No loan releases found for the selected period.', '', '', '']],
            ],
            [
                'title' => 'COLLECTIONS BY MONTH',
                'headers' => ['Month', 'Payments Count', 'Total Collected'],
                'rows' => $collectionsByMonthRows !== []
                    ? $collectionsByMonthRows
                    : [['No collections recorded for the selected period.', '', '']],
            ],
            [
                'title' => 'OVERDUE LOANS',
                'headers' => ['Member', 'Loan No.', 'Balance', 'Due Date', 'Days Overdue'],
                'rows' => $overdueLoanRows !== []
                    ? $overdueLoanRows
                    : [['No overdue loans found.', '', '', '', '']],
            ],
            [
                'title' => 'TOP 10 BORROWERS BY OUTSTANDING BALANCE',
                'headers' => ['Member No.', 'Name', 'Active Loans', 'Total Outstanding'],
                'rows' => $topBorrowerRows !== []
                    ? $topBorrowerRows
                    : [['No borrower balances found for the selected filters.', '', '', '']],
            ],
        ];
    }

    private function formatCurrency(float $amount, bool $withCurrencySymbol): string
    {
        $formatted = number_format($amount, 2, '.', ',');

        return $withCurrencySymbol ? $this->exportBranding()['currencySymbol'].$formatted : $formatted;
    }

    private function buildReportData(Request $request): array
    {
        $this->authorize('viewReports', tenant());

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'report_type' => ['nullable', 'string', 'max:50'],
        ]);

        $branchId = $filters['branch_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $reportType = $filters['report_type'] ?? 'summary';

        $loanBaseQuery = Loan::query()
            ->with(['member', 'branch', 'loanType'])
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId));

        $releasedLoansQuery = (clone $loanBaseQuery)
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '<=', $dateTo));

        $paymentBaseQuery = LoanPayment::query()
            ->with(['loan.member', 'loan.branch', 'user'])
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->whereHas(
                    'loan',
                    static fn (Builder $loanQuery): Builder => $loanQuery->where('branch_id', $branchId),
                ),
            )
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('payment_date', '<=', $dateTo));

        $overdueLoansQuery = (clone $loanBaseQuery)
            ->where(function (Builder $query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function (Builder $loanQuery): void {
                        $loanQuery->whereDate('due_date', '<', today())
                            ->where('status', '!=', 'fully_paid');
                    });
            });

        if ($dateFrom !== null || $dateTo !== null) {
            $overdueLoansQuery->where(function (Builder $query) use ($dateFrom, $dateTo): void {
                if ($dateFrom !== null) {
                    $query->whereDate('due_date', '>=', $dateFrom);
                }

                if ($dateTo !== null) {
                    $query->whereDate('due_date', '<=', $dateTo);
                }
            });
        }

        $totalLoansReleasedCount = (clone $releasedLoansQuery)->count();
        $totalLoansReleasedAmount = round((float) (clone $releasedLoansQuery)->sum('principal_amount'), 2);
        $totalCollections = round((float) (clone $paymentBaseQuery)->sum('amount'), 2);
        $totalOutstandingBalance = round((float) (clone $loanBaseQuery)->sum('outstanding_balance'), 2);
        $totalOverdueLoans = (clone $overdueLoansQuery)->count();
        $fullyPaidLoansCount = (clone $loanBaseQuery)
            ->where('status', 'fully_paid')
            ->count();

        $interestIncome = round((float) LoanSchedule::query()
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->whereHas(
                    'loan',
                    static fn (Builder $loanQuery): Builder => $loanQuery->where('branch_id', $branchId),
                ),
            )
            ->where('status', 'paid')
            ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('paid_at', '<=', $dateTo))
            ->sum('interest_portion'), 2);

        $loanBreakdown = LoanType::query()
            ->orderBy('name')
            ->get()
            ->map(function (LoanType $loanType) use ($branchId, $dateFrom, $dateTo): array {
                $loansQuery = $loanType->loans()
                    ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId))
                    ->when($dateFrom !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '>=', $dateFrom))
                    ->when($dateTo !== null, static fn (Builder $query): Builder => $query->whereDate('release_date', '<=', $dateTo));

                return [
                    'name' => $loanType->name,
                    'count' => $loansQuery->count(),
                    'total_principal' => round((float) $loansQuery->sum('principal_amount'), 2),
                    'total_payable' => round((float) $loansQuery->sum('total_payable'), 2),
                ];
            })
            ->filter(static fn (array $item): bool => $item['count'] > 0)
            ->values();

        $collectionsByMonth = (clone $paymentBaseQuery)
            ->get()
            ->groupBy(static fn (LoanPayment $payment): string => $payment->payment_date?->format('Y-m') ?? 'unknown')
            ->sortKeys()
            ->map(static function ($payments, string $month): array {
                $monthLabel = $month === 'unknown'
                    ? 'Unknown'
                    : \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y');

                return [
                    'month' => $monthLabel,
                    'payments_count' => $payments->count(),
                    'total_collected' => round((float) $payments->sum('amount'), 2),
                ];
            })
            ->values();

        $overdueLoans = (clone $overdueLoansQuery)
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        $topBorrowers = Member::query()
            ->when($branchId !== null, static fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->withCount([
                'loans as active_loans_count' => static function (Builder $query) use ($branchId, $dateFrom, $dateTo): void {
                    $query->whereIn('status', ['active', 'overdue', 'restructured'])
                        ->when($branchId !== null, static fn (Builder $branchQuery): Builder => $branchQuery->where('branch_id', $branchId))
                        ->when($dateFrom !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '>=', $dateFrom))
                        ->when($dateTo !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '<=', $dateTo));
                },
            ])
            ->withSum([
                'loans as total_outstanding' => static function (Builder $query) use ($branchId, $dateFrom, $dateTo): void {
                    $query->when($branchId !== null, static fn (Builder $branchQuery): Builder => $branchQuery->where('branch_id', $branchId))
                        ->when($dateFrom !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '>=', $dateFrom))
                        ->when($dateTo !== null, static fn (Builder $dateQuery): Builder => $dateQuery->whereDate('release_date', '<=', $dateTo));
                },
            ], 'outstanding_balance')
            ->get()
            ->filter(static fn (Member $member): bool => (float) ($member->total_outstanding ?? 0) > 0)
            ->sortByDesc(static fn (Member $member): float => (float) ($member->total_outstanding ?? 0))
            ->take(10)
            ->values();

        $branches = Branch::query()->orderBy('name')->get();
        $selectedBranchName = $branchId !== null
            ? (string) ($branches->firstWhere('id', $branchId)?->name ?? 'Unknown Branch')
            : 'All Branches';

        return compact(
            'filters',
            'reportType',
            'branches',
            'selectedBranchName',
            'totalLoansReleasedCount',
            'totalLoansReleasedAmount',
            'totalCollections',
            'totalOutstandingBalance',
            'totalOverdueLoans',
            'interestIncome',
            'fullyPaidLoansCount',
            'loanBreakdown',
            'collectionsByMonth',
            'overdueLoans',
            'topBorrowers',
        );
    }
}

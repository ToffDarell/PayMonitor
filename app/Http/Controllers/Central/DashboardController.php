<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\SupportRequest;
use App\Models\TenantApplication;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const HEALTH_WATCHLIST_SIZE = 8;

    public function __construct(private TenantService $tenantService)
    {
        $this->middleware(static function ($request, $next) {
            abort_unless($request->user()?->hasRole('super_admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $tenants = Tenant::query()
            ->with('plan')
            ->latest()
            ->get();

        $totalTenants = $tenants->count();
        $activeTenants = $tenants->where('status', 'active')->count();
        $overdueTenants = $tenants->where('status', 'overdue')->count();
        $suspendedTenants = $tenants->where('status', 'suspended')->count();
        $inactiveTenants = $tenants->where('status', 'inactive')->count();

        $billableTenants = $tenants->filter(static fn (Tenant $tenant): bool => in_array($tenant->status, ['active', 'overdue'], true));

        $mrr = (float) $billableTenants
            ->sum(static fn (Tenant $tenant): float => (float) ($tenant->plan?->price ?? 0));

        $collectionsThisMonth = (float) BillingInvoice::query()
            ->paid()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $collectedInvoicesThisMonth = BillingInvoice::query()
            ->paid()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->count();

        $newApplicationsThisMonth = TenantApplication::query()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $pendingPayments = BillingInvoice::query()
            ->pendingVerification()
            ->count();

        $tenantBaseAtMonthStart = Tenant::query()
            ->where('created_at', '<', $monthStart)
            ->count();

        $churnedTenantsThisMonth = Tenant::query()
            ->whereIn('status', ['inactive', 'suspended'])
            ->where('created_at', '<', $monthStart)
            ->whereBetween('updated_at', [$monthStart, $now])
            ->count();

        $churnRate = $tenantBaseAtMonthStart > 0
            ? round(($churnedTenantsThisMonth / $tenantBaseAtMonthStart) * 100, 1)
            : 0.0;

        $overdueRate = $billableTenants->count() > 0
            ? round(($overdueTenants / $billableTenants->count()) * 100, 1)
            : 0.0;

        $unresolvedSupportByTenant = SupportRequest::query()
            ->whereNotNull('tenant_id')
            ->where(function ($query): void {
                $query->whereNull('resolved_at')
                    ->orWhere('status', '!=', 'resolved');
            })
            ->selectRaw('tenant_id, COUNT(*) AS unresolved_count')
            ->groupBy('tenant_id')
            ->pluck('unresolved_count', 'tenant_id');

        $invoicesByTenant = BillingInvoice::query()
            ->select(['tenant_id', 'status', 'due_date', 'paymongo_link_id'])
            ->get()
            ->groupBy('tenant_id');

        $tenantHealthSnapshots = $tenants
            ->map(function (Tenant $tenant) use ($unresolvedSupportByTenant, $invoicesByTenant): array {
                return $this->buildTenantHealthSnapshot(
                    $tenant,
                    $invoicesByTenant->get($tenant->id, collect()),
                    (int) ($unresolvedSupportByTenant[$tenant->id] ?? 0),
                );
            })
            ->values();

        $tenantHealthWatchlist = $tenantHealthSnapshots
            ->sortBy([
                ['health_score', 'asc'],
                ['billing_rank', 'desc'],
                ['unresolved_support', 'desc'],
                ['quota_peak_percent', 'desc'],
                ['tenant_name', 'asc'],
            ])
            ->take(self::HEALTH_WATCHLIST_SIZE)
            ->values();

        $healthSummary = [
            'average_score' => round((float) ($tenantHealthSnapshots->avg('health_score') ?? 100), 1),
            'healthy' => $tenantHealthSnapshots->where('health_band', 'healthy')->count(),
            'stable' => $tenantHealthSnapshots->where('health_band', 'stable')->count(),
            'watch' => $tenantHealthSnapshots->where('health_band', 'watch')->count(),
            'critical' => $tenantHealthSnapshots->where('health_band', 'critical')->count(),
            'attention_needed' => $tenantHealthSnapshots->filter(
                static fn (array $snapshot): bool => in_array($snapshot['health_band'], ['watch', 'critical'], true)
            )->count(),
        ];

        $dashboardMetrics = [
            'mrr' => [
                'value' => $mrr,
                'label' => 'Monthly recurring revenue',
                'detail' => $billableTenants->count().' billable tenants',
            ],
            'collections' => [
                'value' => $collectionsThisMonth,
                'label' => 'Collections this month',
                'detail' => $collectedInvoicesThisMonth.' paid invoices',
            ],
            'new_applications' => [
                'value' => $newApplicationsThisMonth,
                'label' => 'New applications',
                'detail' => 'This month',
            ],
            'churn_rate' => [
                'value' => $churnRate,
                'label' => 'Tenant churn rate',
                'detail' => $churnedTenantsThisMonth.' churned this month',
            ],
            'overdue_rate' => [
                'value' => $overdueRate,
                'label' => 'Overdue rate',
                'detail' => $overdueTenants.' overdue tenants',
            ],
            'pending_payments' => [
                'value' => $pendingPayments,
                'label' => 'Pending verification',
                'detail' => 'Awaiting admin confirmation',
            ],
        ];

        $recentTenants = $tenants->take(10);
        $monthlyRevenue = $mrr;

        return view('central.dashboard', compact(
            'dashboardMetrics',
            'healthSummary',
            'tenantHealthWatchlist',
            'totalTenants',
            'activeTenants',
            'overdueTenants',
            'suspendedTenants',
            'inactiveTenants',
            'monthlyRevenue',
            'recentTenants',
            'newApplicationsThisMonth',
            'pendingPayments',
        ));
    }

    /**
     * @param  Collection<int, BillingInvoice>  $invoices
     * @return array<string, mixed>
     */
    private function buildTenantHealthSnapshot(Tenant $tenant, Collection $invoices, int $unresolvedSupport): array
    {
        $usage = $this->tenantService->getTenantUsage($tenant);
        $dbSize = $this->tenantService->getTenantDatabaseSize($tenant);

        $quotaHealth = $this->evaluateQuotaHealth($tenant, $usage);
        $databaseHealth = $this->evaluateDatabaseHealth($dbSize);
        $billingRisk = $this->evaluateBillingRisk($tenant, $invoices);
        $supportPenalty = $this->supportPenalty($unresolvedSupport);

        $healthScore = max(
            0,
            100 - $quotaHealth['penalty'] - $databaseHealth['penalty'] - $supportPenalty - $billingRisk['penalty'],
        );

        $healthBand = $this->resolveHealthBand($healthScore);

        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant' => $tenant,
            'plan_name' => $tenant->plan?->name ?? 'No Plan',
            'health_score' => $healthScore,
            'health_band' => $healthBand['band'],
            'health_label' => $healthBand['label'],
            'health_classes' => $healthBand['classes'],
            'quota_peak_percent' => $quotaHealth['peak_percent'],
            'quota_summary' => $quotaHealth['summary'],
            'quota_penalty' => $quotaHealth['penalty'],
            'database_summary' => $databaseHealth['summary'],
            'database_penalty' => $databaseHealth['penalty'],
            'db_size_formatted' => $databaseHealth['formatted'],
            'db_total_rows' => (int) ($dbSize['total_rows'] ?? 0),
            'unresolved_support' => $unresolvedSupport,
            'support_penalty' => $supportPenalty,
            'billing_label' => $billingRisk['label'],
            'billing_detail' => $billingRisk['detail'],
            'billing_rank' => $billingRisk['rank'],
            'billing_penalty' => $billingRisk['penalty'],
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @return array{penalty:int, peak_percent:float, summary:string}
     */
    private function evaluateQuotaHealth(Tenant $tenant, array $usage): array
    {
        $branchLimit = (int) ($tenant->plan?->max_branches ?? 0);
        $userLimit = (int) ($tenant->plan?->max_users ?? 0);
        $branchesUsed = (int) ($usage['branches'] ?? 0);
        $usersUsed = (int) ($usage['users'] ?? 0);

        $branchPercent = $branchLimit > 0 ? round(($branchesUsed / $branchLimit) * 100, 1) : 0.0;
        $userPercent = $userLimit > 0 ? round(($usersUsed / $userLimit) * 100, 1) : 0.0;
        $peakPercent = max($branchPercent, $userPercent);

        $penalty = match (true) {
            $branchLimit === 0 && $userLimit === 0 => 0,
            $peakPercent >= 110 => 30,
            $peakPercent >= 100 => 24,
            $peakPercent >= 90 => 16,
            $peakPercent >= 75 => 8,
            default => 0,
        };

        return [
            'penalty' => $penalty,
            'peak_percent' => $peakPercent,
            'summary' => sprintf(
                'Branches %s, Users %s',
                $this->formatQuotaUsage($branchesUsed, $branchLimit),
                $this->formatQuotaUsage($usersUsed, $userLimit),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $dbSize
     * @return array{penalty:int, formatted:string, summary:string}
     */
    private function evaluateDatabaseHealth(array $dbSize): array
    {
        $sizeMb = (float) ($dbSize['size_mb'] ?? 0);
        $formatted = (string) ($dbSize['formatted'] ?? number_format($sizeMb, 2).' MB');

        $penalty = match (true) {
            $sizeMb >= 1500 => 20,
            $sizeMb >= 750 => 14,
            $sizeMb >= 250 => 8,
            $sizeMb >= 100 => 4,
            default => 0,
        };

        return [
            'penalty' => $penalty,
            'formatted' => $formatted,
            'summary' => $formatted.' tenant database',
        ];
    }

    /**
     * @param  Collection<int, BillingInvoice>  $invoices
     * @return array{penalty:int, rank:int, label:string, detail:string}
     */
    private function evaluateBillingRisk(Tenant $tenant, Collection $invoices): array
    {
        $overdueInvoices = $invoices->where('status', 'overdue')->count();
        $pendingVerificationInvoices = $invoices->where('status', 'pending_verification')->count();

        if ($tenant->status === 'suspended') {
            return [
                'penalty' => 30,
                'rank' => 5,
                'label' => 'Suspended',
                'detail' => 'Tenant access is suspended.',
            ];
        }

        if ($tenant->status === 'inactive') {
            return [
                'penalty' => 20,
                'rank' => 4,
                'label' => 'Inactive',
                'detail' => 'Tenant is currently inactive.',
            ];
        }

        if ($tenant->status === 'overdue' || $overdueInvoices > 0) {
            return [
                'penalty' => 35,
                'rank' => 4,
                'label' => 'Overdue',
                'detail' => $overdueInvoices > 0
                    ? $overdueInvoices.' overdue invoice(s)'
                    : 'Subscription is past due.',
            ];
        }

        if ($pendingVerificationInvoices > 0) {
            return [
                'penalty' => 18,
                'rank' => 3,
                'label' => 'Verifying payment',
                'detail' => $pendingVerificationInvoices.' payment(s) awaiting confirmation.',
            ];
        }

        $dueDate = $tenant->subscription_due_at;

        if ($dueDate === null) {
            return [
                'penalty' => 8,
                'rank' => 2,
                'label' => 'Billing date missing',
                'detail' => 'No subscription due date is set.',
            ];
        }

        if ($dueDate->lte(today()->copy()->addDays(7))) {
            return [
                'penalty' => 10,
                'rank' => 2,
                'label' => 'Due soon',
                'detail' => 'Due '.$dueDate->diffForHumans(today(), ['parts' => 2, 'short' => true]),
            ];
        }

        return [
            'penalty' => 0,
            'rank' => 0,
            'label' => 'Current',
            'detail' => 'Billing is current.',
        ];
    }

    private function supportPenalty(int $unresolvedSupport): int
    {
        return match (true) {
            $unresolvedSupport >= 5 => 20,
            $unresolvedSupport >= 3 => 12,
            $unresolvedSupport >= 1 => 6,
            default => 0,
        };
    }

    /**
     * @return array{band:string, label:string, classes:string}
     */
    private function resolveHealthBand(int $score): array
    {
        return match (true) {
            $score >= 85 => [
                'band' => 'healthy',
                'label' => 'Healthy',
                'classes' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
            ],
            $score >= 70 => [
                'band' => 'stable',
                'label' => 'Stable',
                'classes' => 'border-sky-500/20 bg-sky-500/10 text-sky-300',
            ],
            $score >= 55 => [
                'band' => 'watch',
                'label' => 'Watch',
                'classes' => 'border-amber-500/20 bg-amber-500/10 text-amber-300',
            ],
            default => [
                'band' => 'critical',
                'label' => 'Critical',
                'classes' => 'border-rose-500/20 bg-rose-500/10 text-rose-300',
            ],
        };
    }

    private function formatQuotaUsage(int $used, int $limit): string
    {
        if ($limit <= 0) {
            return number_format($used).' / Unlimited';
        }

        return number_format($used).' / '.number_format($limit);
    }
}

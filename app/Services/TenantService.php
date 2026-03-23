<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TenantWelcomeMail;
use App\Models\Branch;
use App\Models\Domain;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        $subdomain = Str::lower((string) $data['domain']);
        $baseDomain = $this->tenantBaseDomain();
        $scheme = $this->tenantScheme();
        $tenant = null;

        try {
            $tenant = Tenant::query()->create([
                'id' => $subdomain,
                'name' => $data['name'],
                'email' => $data['admin_email'],
                'plan_id' => $data['plan_id'],
                'address' => $data['address'] ?? null,
                'admin_name' => $data['admin_name'] ?? null,
                'status' => 'active',
                'subscription_due_at' => $data['subscription_due_at'] ?? null,
            ]);

            Domain::query()->create([
                'tenant_id' => $tenant->id,
                'domain' => "{$subdomain}.{$baseDomain}",
            ]);

            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
            ]);

            $password = Str::random(12);

            $tenant->run(function () use ($data, $password): void {
                foreach (['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'] as $roleName) {
                    Role::findOrCreate($roleName, 'web');
                }

                $tenantAdmin = User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $password,
                    'branch_id' => null,
                ]);

                $tenantAdmin->assignRole('tenant_admin');
            });

            Mail::to($data['admin_email'])->send(new TenantWelcomeMail(
                $tenant,
                $data['admin_email'],
                $password,
                "{$scheme}://{$subdomain}.{$baseDomain}/login",
            ));

            return $tenant;
        } catch (Throwable $throwable) {
            if ($tenant instanceof Tenant && $tenant->exists) {
                rescue(static function () use ($tenant): void {
                    $tenant->delete();
                }, report: false);
            }

            throw $throwable;
        }
    }

    public function suspendTenant(Tenant $tenant): void
    {
        $tenant->status = 'suspended';
        $tenant->save();
    }

    public function activateTenant(Tenant $tenant): void
    {
        $tenant->status = 'active';
        $tenant->save();
    }

    public function resendCredentials(Tenant $tenant): void
    {
        $password = Str::random(12);

        $email = $tenant->run(function () use ($password): ?string {
            $tenantAdmin = User::role('tenant_admin')->first();

            if ($tenantAdmin === null) {
                return null;
            }

            $tenantAdmin->password = $password;
            $tenantAdmin->save();

            return $tenantAdmin->email;
        });

        if ($email === null) {
            return;
        }

        Mail::to($email)->send(new TenantWelcomeMail(
            $tenant,
            $email,
            $password,
            rtrim($tenant->getFullDomain(), '/').'/login',
        ));
    }

    public function getTenantUsage(Tenant $tenant): array
    {
        try {
            return $tenant->run(static function (): array {
                $usage = [
                    'branches' => Branch::count(),
                    'users' => User::count(),
                    'members' => Member::count(),
                    'loan_types' => LoanType::count(),
                    'loans' => Loan::count(),
                ];

                $usage['total'] = array_sum($usage);

                return $usage;
            });
        } catch (Throwable) {
            return [
                'branches' => 0,
                'users' => 0,
                'members' => 0,
                'loan_types' => 0,
                'loans' => 0,
                'total' => 0,
            ];
        }
    }

    private function tenantBaseDomain(): string
    {
        return (string) config('tenancy.tenant_base_domain', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }

    private function tenantScheme(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
    }
}

<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Plan;
use App\Models\BillingInvoice;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

function centralHost(): array
{
    return ['HTTP_HOST' => 'localhost'];
}

function createCentralAdmin(): User
{
    Role::findOrCreate('super_admin', 'web');

    $user = User::query()->firstOrCreate(
        ['email' => 'central@example.com'],
        [
            'name' => 'Central Admin',
            'password' => 'password123',
        ],
    );

    if (! $user->hasRole('super_admin')) {
        $user->assignRole('super_admin');
    }

    return $user;
}

function createCentralTenant(array $attributes): Tenant
{
    return Tenant::withoutEvents(static fn (): Tenant => Tenant::query()->create($attributes));
}

afterEach(function (): void {
    \Mockery::close();
    Carbon::setTestNow();
});

test('central dashboard shows tenant metrics and recent tenants', function (): void {
    Carbon::setTestNow('2026-04-19 09:00:00');

    $plan = Plan::query()->create([
        'name' => 'Standard',
        'price' => 1999,
        'max_branches' => 3,
        'max_users' => 10,
    ]);

    createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
        'subscription_due_at' => today()->addDays(10),
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subDays(2),
    ]);

    createCentralTenant([
        'id' => 'bravo',
        'name' => 'Bravo Cooperative',
        'email' => 'bravo@example.com',
        'plan_id' => $plan->id,
        'status' => 'overdue',
        'subscription_due_at' => today()->subDay(),
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subDay(),
    ]);

    createCentralTenant([
        'id' => 'charlie',
        'name' => 'Charlie Cooperative',
        'email' => 'charlie@example.com',
        'plan_id' => $plan->id,
        'status' => 'suspended',
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subDays(3),
    ]);

    BillingInvoice::query()->create([
        'tenant_id' => 'alpha',
        'invoice_number' => 'INV-202604-0001',
        'amount' => 1999,
        'due_date' => today()->addDays(10),
        'status' => 'paid',
        'paid_at' => now()->subDays(2),
    ]);

    BillingInvoice::query()->create([
        'tenant_id' => 'bravo',
        'invoice_number' => 'INV-202604-0002',
        'amount' => 1999,
        'due_date' => today()->subDay(),
        'status' => 'overdue',
        'paymongo_link_id' => 'plink_test_123',
    ]);

    BillingInvoice::query()->create([
        'tenant_id' => 'bravo',
        'invoice_number' => 'INV-202604-0003',
        'amount' => 1999,
        'due_date' => today()->addDays(3),
        'status' => 'pending_verification',
        'paymongo_link_id' => 'plink_test_456',
    ]);

    TenantApplication::query()->create([
        'cooperative_name' => 'Delta Cooperative',
        'cda_registration_number' => 'CDA-2026-0419',
        'address' => '123 Main Street',
        'city' => 'Manila',
        'contact_number' => '+639171234567',
        'email' => 'apply@example.com',
        'admin_name' => 'Delta Admin',
        'admin_email' => 'delta@example.com',
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    SupportRequest::query()->create([
        'tenant_id' => 'alpha',
        'tenant_name' => 'Alpha Cooperative',
        'requester_name' => 'Alpha Admin',
        'requester_email' => 'alpha@example.com',
        'category' => 'general',
        'subject' => 'Recent request',
        'message' => 'Please help us review our account.',
        'status' => 'open',
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ]);

    SupportRequest::query()->create([
        'tenant_id' => 'bravo',
        'tenant_name' => 'Bravo Cooperative',
        'requester_name' => 'Bravo Admin',
        'requester_email' => 'bravo@example.com',
        'category' => 'billing',
        'subject' => 'Old billing issue',
        'message' => 'Our payment needs review.',
        'status' => 'open',
        'created_at' => now()->subHours(40),
        'updated_at' => now()->subHours(40),
    ]);

    SupportRequest::query()->create([
        'tenant_id' => 'charlie',
        'tenant_name' => 'Charlie Cooperative',
        'requester_name' => 'Charlie Admin',
        'requester_email' => 'charlie@example.com',
        'category' => 'technical',
        'subject' => 'Resolved request',
        'message' => 'This issue was resolved.',
        'status' => 'resolved',
        'resolved_at' => now()->subHours(2),
        'created_at' => now()->subHours(10),
        'updated_at' => now()->subHours(2),
    ]);

    $service = Mockery::mock(TenantService::class);
    $service->shouldReceive('getTenantUsage')->times(3)->andReturnUsing(static function (Tenant $tenant): array {
        return match ($tenant->id) {
            'alpha' => ['branches' => 1, 'users' => 4, 'members' => 10, 'loan_types' => 2, 'loans' => 12, 'total' => 29],
            'bravo' => ['branches' => 3, 'users' => 10, 'members' => 25, 'loan_types' => 2, 'loans' => 20, 'total' => 60],
            default => ['branches' => 4, 'users' => 12, 'members' => 18, 'loan_types' => 2, 'loans' => 14, 'total' => 50],
        };
    });
    $service->shouldReceive('getTenantDatabaseSize')->times(3)->andReturnUsing(static function (Tenant $tenant): array {
        return match ($tenant->id) {
            'alpha' => ['size_mb' => 50.0, 'total_rows' => 1000, 'formatted' => '50.00 MB'],
            'bravo' => ['size_mb' => 320.0, 'total_rows' => 5000, 'formatted' => '320.00 MB'],
            default => ['size_mb' => 900.0, 'total_rows' => 12000, 'formatted' => '900.00 MB'],
        };
    });
    app()->instance(TenantService::class, $service);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->get('/central/dashboard');

    $response->assertOk()
        ->assertViewHas('totalTenants', 3)
        ->assertViewHas('activeTenants', 1)
        ->assertViewHas('overdueTenants', 1)
        ->assertViewHas('suspendedTenants', 1)
        ->assertViewHas('inactiveTenants', 0)
        ->assertViewHas('monthlyRevenue', 3998.0)
        ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
            return (float) data_get($metrics, 'mrr.value') === 3998.0
                && (float) data_get($metrics, 'collections.value') === 1999.0
                && (int) data_get($metrics, 'new_applications.value') === 1
                && (float) data_get($metrics, 'churn_rate.value') === 33.3
                && (float) data_get($metrics, 'overdue_rate.value') === 50.0
                && (int) data_get($metrics, 'pending_payments.value') === 1;
        })
        ->assertViewHas('healthSummary', function (array $healthSummary): bool {
            return $healthSummary['attention_needed'] === 2
                && $healthSummary['critical'] === 2;
        })
        ->assertViewHas('tenantHealthWatchlist', function ($watchlist): bool {
            $bravo = collect($watchlist)->firstWhere('tenant_id', 'bravo');
            $charlie = collect($watchlist)->firstWhere('tenant_id', 'charlie');

            return $bravo !== null
                && $charlie !== null
                && $bravo['health_label'] === 'Critical'
                && $charlie['billing_label'] === 'Suspended';
        })
        ->assertSee('Alpha Cooperative');
});

test('tenant index paginates and enriches tenants with usage', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Basic',
        'price' => 499,
        'max_branches' => 1,
        'max_users' => 5,
    ]);

    $tenant = createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $service = Mockery::mock(TenantService::class);
    $service->shouldReceive('getTenantUsage')->once()->withArgs(fn (Tenant $subject): bool => $subject->is($tenant))->andReturn([
        'branches' => 1,
        'users' => 1,
        'members' => 1,
        'loan_types' => 1,
        'loans' => 1,
        'total' => 5,
    ]);
    app()->instance(TenantService::class, $service);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->get('/central/tenants');

    $response->assertOk()
        ->assertViewHas('tenants', fn ($paginator): bool => data_get($paginator->items()[0], 'usage.total') === 5)
        ->assertSee('Alpha Cooperative');
});

test('tenant store uses tenant service and redirects with success', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Standard',
        'price' => 999,
        'max_branches' => 3,
        'max_users' => 15,
    ]);

    $service = Mockery::mock(TenantService::class);
    $service->shouldReceive('createTenant')->once()->andReturn(new Tenant([
        'id' => 'delta',
        'name' => 'Delta Cooperative',
        'email' => 'admin@delta.test',
        'plan_id' => $plan->id,
    ]));
    app()->instance(TenantService::class, $service);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->post('/central/tenants', [
        'name' => 'Delta Cooperative',
        'domain' => 'delta',
        'admin_name' => 'Delta Admin',
        'admin_email' => 'admin@delta.test',
        'plan_id' => $plan->id,
        'address' => 'Main Street',
        'subscription_due_at' => today()->addMonth()->toDateString(),
    ]);

    $response->assertRedirect('/central/tenants')
        ->assertSessionHas('success', 'Tenant created. Credentials sent to admin@delta.test');
});

test('tenant show loads plan domain and usage', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Premium',
        'price' => 1999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    $tenant = createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Domain::query()->create([
        'domain' => 'alpha.paymonitor.com',
        'tenant_id' => $tenant->id,
    ]);

    $service = Mockery::mock(TenantService::class);
    $service->shouldReceive('getTenantUsage')->once()->andReturn([
        'branches' => 2,
        'users' => 5,
        'members' => 20,
        'loan_types' => 3,
        'loans' => 8,
        'total' => 38,
    ]);
    app()->instance(TenantService::class, $service);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->get('/central/tenants/'.$tenant->id);

    $response->assertOk()
        ->assertSee('alpha.paymonitor.com')
        ->assertSee('38');
});

test('tenant update persists central fields and subscription changes', function (): void {
    $firstPlan = Plan::query()->create([
        'name' => 'Basic',
        'price' => 499,
        'max_branches' => 1,
        'max_users' => 5,
    ]);

    $nextPlan = Plan::query()->create([
        'name' => 'Premium',
        'price' => 1999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    $tenant = createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $firstPlan->id,
        'status' => 'active',
    ]);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->put('/central/tenants/'.$tenant->id, [
        'name' => 'Alpha Lending Cooperative',
        'address' => 'Updated Address',
        'plan_id' => $nextPlan->id,
        'subscription_due_at' => today()->addDays(20)->toDateString(),
        'status' => 'inactive',
    ]);

    $response->assertRedirect('/central/tenants');

    $updatedTenant = $tenant->fresh();

    expect($updatedTenant->name)->toBe('Alpha Lending Cooperative');
    expect($updatedTenant->address)->toBe('Updated Address');
    expect($updatedTenant->plan_id)->toBe($nextPlan->id);
    expect($updatedTenant->subscription_due_at?->toDateString())->toBe(today()->addDays(20)->toDateString());
    expect($updatedTenant->status)->toBe('inactive');
});

test('tenant destroy removes domains before deleting tenant', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Basic',
        'price' => 499,
        'max_branches' => 1,
        'max_users' => 5,
    ]);

    $tenant = Tenant::query()->create([
        'id' => 'deletealpha',
        'name' => 'Delete Alpha Cooperative',
        'email' => 'deletealpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Domain::query()->create([
        'domain' => 'deletealpha.paymonitor.com',
        'tenant_id' => $tenant->id,
    ]);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->delete('/central/tenants/'.$tenant->id);

    $response->assertRedirect('/central/tenants');
    expect(Tenant::query()->find($tenant->id))->toBeNull();
    expect(Domain::query()->where('tenant_id', $tenant->id)->exists())->toBeFalse();
});

test('tenant actions suspend activate and resend credentials through the service', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Basic',
        'price' => 499,
        'max_branches' => 1,
        'max_users' => 5,
    ]);

    $tenant = createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $service = Mockery::mock(TenantService::class);
    $service->shouldReceive('suspendTenant')->once()->withArgs(fn (Tenant $subject): bool => $subject->is($tenant));
    $service->shouldReceive('activateTenant')->once()->withArgs(fn (Tenant $subject): bool => $subject->is($tenant));
    $service->shouldReceive('resendCredentials')->once()->withArgs(fn (Tenant $subject): bool => $subject->is($tenant));
    app()->instance(TenantService::class, $service);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->post('/central/tenants/'.$tenant->id.'/suspend')
        ->assertRedirect()
        ->assertSessionHas('success', 'Tenant suspended successfully.');

    $this->withServerVariables(centralHost())->post('/central/tenants/'.$tenant->id.'/activate')
        ->assertRedirect()
        ->assertSessionHas('success', 'Tenant activated successfully.');

    $this->withServerVariables(centralHost())->post('/central/tenants/'.$tenant->id.'/resend-credentials')
        ->assertRedirect()
        ->assertSessionHas('success', 'Credentials resent to tenant admin');
});

test('plan controller lists counts creates updates and guards deletion', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Starter',
        'price' => 350,
        'max_branches' => 1,
        'max_users' => 3,
    ]);

    createCentralTenant([
        'id' => 'linked',
        'name' => 'Linked Cooperative',
        'email' => 'linked@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->get('/central/plans')
        ->assertOk()
        ->assertViewHas('plans', fn ($plans): bool => $plans->firstWhere('id', $plan->id)?->tenants_count === 1);

    $this->withServerVariables(centralHost())->get('/central/plans/'.$plan->id.'/edit')
        ->assertOk()
        ->assertSee('Secure cooperative portal access')
        ->assertSee('Loan and member management')
        ->assertSee('Centralized reporting tools');

    $this->withServerVariables(centralHost())->post('/central/plans', [
        'name' => 'Growth',
        'price' => 1200,
        'max_branches' => 5,
        'max_users' => 20,
        'description' => "5 Branches\n20 Staff Users",
    ])->assertRedirect('/central/plans');

    $createdPlan = Plan::query()->where('name', 'Growth')->firstOrFail();

    $this->withServerVariables(centralHost())->put('/central/plans/'.$createdPlan->id, [
        'name' => 'Growth Plus',
        'price' => 1400,
        'max_branches' => 8,
        'max_users' => 25,
        'description' => "8 Branches\n25 Staff Users\nPriority onboarding",
    ])->assertRedirect('/central/plans');

    expect($createdPlan->fresh()->name)->toBe('Growth Plus');
    expect($createdPlan->fresh()->description)->toBe("8 Branches\n25 Staff Users\nPriority onboarding");

    $this->withServerVariables(centralHost())->post('/central/plans', [
        'name' => 'Foundation',
        'price' => 700,
        'max_branches' => 3,
        'max_users' => 12,
        'description' => '',
    ])->assertRedirect('/central/plans');

    expect(Plan::query()->where('name', 'Foundation')->firstOrFail()->description)->toBe(Plan::defaultDescription());

    $this->withServerVariables(centralHost())->delete('/central/plans/'.$plan->id)
        ->assertRedirect('/central/plans')
        ->assertSessionHas('error', 'Cannot delete plan with active tenants');
});

test('public pricing and application form load plans dynamically', function (): void {
    Plan::query()->create([
        'name' => 'Starter',
        'price' => 499,
        'max_branches' => 2,
        'max_users' => 10,
        'description' => "Short-term lending tools\nEmail support",
    ]);

    $customPlan = Plan::query()->create([
        'name' => 'Ultra Pro Max',
        'price' => 2000,
        'max_branches' => 100,
        'max_users' => 0,
        'description' => "100 Branches\nUnlimited Staff Users\nCustom onboarding",
    ]);

    $this->withServerVariables(centralHost())->get('/')
        ->assertOk()
        ->assertSee('Starter')
        ->assertSee('Ultra Pro Max')
        ->assertSee('/apply?plan='.$customPlan->id, false);

    $this->withServerVariables(centralHost())->get('/apply?plan='.$customPlan->id)
        ->assertOk()
        ->assertSee('Ultra Pro Max Plan')
        ->assertSee('value="'.$customPlan->id.'"', false);

    $this->withServerVariables(centralHost())->post('/apply', [
        'cooperative_name' => 'North Star Cooperative',
        'cda_registration_number' => 'CDA-2026-0001',
        'first_name' => 'Nova',
        'last_name' => 'Santos',
        'email' => 'nova@example.com',
        'phone' => '+639171234567',
        'plan' => $customPlan->id,
    ])->assertRedirect('/apply/thank-you');

    $application = \App\Models\TenantApplication::query()
        ->where('email', 'nova@example.com')
        ->firstOrFail();

    expect($application->plan_id)->toBe($customPlan->id);
});

test('payment controller classifies statuses and mark paid extends from the later date', function (): void {
    Carbon::setTestNow('2026-03-18');

    $plan = Plan::query()->create([
        'name' => 'Premium',
        'price' => 1999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    $currentTenant = createCentralTenant([
        'id' => 'current',
        'name' => 'Current Cooperative',
        'email' => 'current@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
        'subscription_due_at' => now()->addDays(10),
    ]);

    $dueSoonTenant = createCentralTenant([
        'id' => 'due-soon',
        'name' => 'Due Soon Cooperative',
        'email' => 'duesoon@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
        'subscription_due_at' => now()->addDays(5),
    ]);

    $overdueTenant = createCentralTenant([
        'id' => 'overdue',
        'name' => 'Overdue Cooperative',
        'email' => 'overdue@example.com',
        'plan_id' => $plan->id,
        'status' => 'overdue',
        'subscription_due_at' => now()->subDay(),
    ]);

    $nullDueTenant = createCentralTenant([
        'id' => 'null-due',
        'name' => 'Null Due Cooperative',
        'email' => 'nulldue@example.com',
        'plan_id' => $plan->id,
        'status' => 'inactive',
    ]);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->get('/central/payments')
        ->assertOk()
        ->assertViewHas('tenants', function ($paginator) use ($currentTenant, $dueSoonTenant, $overdueTenant, $nullDueTenant): bool {
            $statuses = $paginator->getCollection()->mapWithKeys(fn (Tenant $tenant): array => [$tenant->id => $tenant->payment_status]);

            return $statuses[$currentTenant->id] === 'current'
                && $statuses[$dueSoonTenant->id] === 'due_soon'
                && $statuses[$overdueTenant->id] === 'overdue'
                && $statuses[$nullDueTenant->id] === 'overdue';
        });

    $this->withServerVariables(centralHost())->post('/central/payments/'.$currentTenant->id.'/mark-paid')
        ->assertRedirect()
        ->assertSessionHas('success', 'Payment recorded successfully.');

    expect($currentTenant->fresh()->subscription_due_at?->toDateString())->toBe('2026-04-27');
    expect($currentTenant->fresh()->status)->toBe('active');
});

test('version check endpoint returns the latest release snapshot', function (): void {
    $service = Mockery::mock(\App\Services\GitHubVersionService::class);
    $service->shouldReceive('getUpdateInfo')->once()->andReturnUsing(static fn (): array => [
        'update_available' => true,
        'latest_version' => 'v1.2.3',
        'current_version' => 'v1.2.2',
        'release_name' => 'April Update',
        'changelog' => "- Fixed cache handling\n- Improved updater stability",
    ]);
    app()->instance(\App\Services\GitHubVersionService::class, $service);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->post('/central/versions/check')
        ->assertOk()
        ->assertJson([
            'update_available' => true,
            'latest_version' => 'v1.2.3',
            'current_version' => 'v1.2.2',
            'release_name' => 'April Update',
            'changelog' => "- Fixed cache handling\n- Improved updater stability",
        ]);
});

test('version apply endpoint redirects with success when updater succeeds', function (): void {
    $service = Mockery::mock(\App\Services\GitHubVersionService::class);
    $service->shouldReceive('applyUpdate')
        ->once()
        ->with('central@example.com')
        ->andReturnUsing(static fn (): array => [
            'success' => true,
            'message' => 'Update applied successfully',
        ]);
    app()->instance(\App\Services\GitHubVersionService::class, $service);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->post('/central/versions/apply')
        ->assertRedirect('/central/versions')
        ->assertSessionHas('success', 'Update applied successfully');
});

test('version apply endpoint redirects with error and warning when updater fails', function (): void {
    $service = Mockery::mock(\App\Services\GitHubVersionService::class);
    $service->shouldReceive('applyUpdate')
        ->once()
        ->with('central@example.com')
        ->andReturnUsing(static fn (): array => [
            'success' => false,
            'message' => 'Update failed',
            'output' => 'git fetch failed: network timeout',
        ]);
    app()->instance(\App\Services\GitHubVersionService::class, $service);

    actingAs(createCentralAdmin());

    $this->withServerVariables(centralHost())->post('/central/versions/apply')
        ->assertRedirect('/central/versions')
        ->assertSessionHas('error', 'Update failed')
        ->assertSessionHas('warning', 'git fetch failed: network timeout');
});

<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Plan;
use App\Models\AppVersion;
use App\Models\Tenant;
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
    $plan = Plan::query()->create([
        'name' => 'Premium',
        'price' => 1999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    createCentralTenant([
        'id' => 'alpha',
        'name' => 'Alpha Cooperative',
        'email' => 'alpha@example.com',
        'plan_id' => $plan->id,
        'status' => 'active',
        'subscription_due_at' => today()->addDays(10),
    ]);

    createCentralTenant([
        'id' => 'bravo',
        'name' => 'Bravo Cooperative',
        'email' => 'bravo@example.com',
        'plan_id' => $plan->id,
        'status' => 'overdue',
        'subscription_due_at' => today()->subDay(),
    ]);

    createCentralTenant([
        'id' => 'charlie',
        'name' => 'Charlie Cooperative',
        'email' => 'charlie@example.com',
        'plan_id' => $plan->id,
        'status' => 'suspended',
    ]);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->get('/central/dashboard');

    $response->assertOk()
        ->assertViewHas('totalTenants', 3)
        ->assertViewHas('activeTenants', 1)
        ->assertViewHas('overdueTenants', 1)
        ->assertViewHas('suspendedTenants', 1)
        ->assertViewHas('inactiveTenants', 0)
        ->assertViewHas('monthlyRevenue', 1999.0)
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

test('version store publishes a new active release and deactivates the old one', function (): void {
    AppVersion::query()->create([
        'version_number' => '1.1.0',
        'title' => 'Previous Release',
        'changelog' => "Existing change\nExisting improvement",
        'is_active' => true,
        'released_at' => now()->subMonth(),
    ]);

    actingAs(createCentralAdmin());

    $response = $this->withServerVariables(centralHost())->post('/central/versions', [
        'version_number' => '1.2.0',
        'title' => 'March Update',
        'changelog' => "Added tenant updates\nAdded tenant settings",
        'released_at' => today()->toDateString(),
        'is_active' => '1',
    ]);

    $response->assertRedirect('/central/versions')
        ->assertSessionHas('success', 'App version published successfully.');

    $newVersion = AppVersion::query()->where('version_number', '1.2.0')->firstOrFail();
    $oldVersion = AppVersion::query()->where('version_number', '1.1.0')->firstOrFail();

    expect($newVersion->is_active)->toBeTrue();
    expect($newVersion->title)->toBe('March Update');
    expect($oldVersion->is_active)->toBeFalse();
});

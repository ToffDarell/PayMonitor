<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantPermissions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class);

beforeEach(function (): void {
    $this->tenantDatabasePaths = [];

    $centralMigrationPaths = collect(File::files(database_path('migrations')))
        ->map(static fn (SplFileInfo $file): string => $file->getPathname())
        ->all();

    Artisan::call('migrate:fresh', [
        '--path' => $centralMigrationPaths,
        '--realpath' => true,
        '--force' => true,
    ]);
});

afterEach(function (): void {
    foreach ($this->tenantDatabasePaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

function provisionModuleTenant(string $tenantId = 'module-demo'): Tenant
{
    $plan = Plan::query()->create([
        'name' => "Plan {$tenantId}",
        'price' => 999,
        'max_branches' => 0,
        'max_users' => 0,
    ]);

    $tenant = Tenant::withoutEvents(static fn (): Tenant => Tenant::query()->create([
        'id' => $tenantId,
        'name' => ucfirst($tenantId).' Cooperative',
        'email' => "{$tenantId}@example.com",
        'plan_id' => $plan->id,
        'status' => 'active',
        'subscription_due_at' => today()->addMonth(),
    ]));

    Domain::query()->create([
        'domain' => "{$tenantId}.localhost",
        'tenant_id' => $tenant->id,
    ]);

    $databasePath = database_path($tenant->database()->getName());

    if (File::exists($databasePath)) {
        File::delete($databasePath);
    }

    File::put($databasePath, '');

    test()->tenantDatabasePaths = [
        ...test()->tenantDatabasePaths,
        $databasePath,
    ];

    Artisan::call('tenants:migrate', [
        '--tenants' => [$tenant->id],
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
        '--force' => true,
    ]);

    $tenant->run(static function (): void {
        TenantPermissions::ensureConfigured();
    });

    return $tenant;
}

function createModuleTenantUser(Tenant $tenant): User
{
    return $tenant->run(static function (): User {
        $user = User::query()->create([
            'name' => 'Module Admin',
            'email' => 'module-admin@example.com',
            'password' => 'password123',
        ]);

        $user->assignRole('tenant_admin');

        return $user;
    });
}

test('tenant module migration and seeder match the presentation task', function (): void {
    $tenant = provisionModuleTenant('module-check');

    $tenant->run(static function (): void {
        expect(Schema::hasTable('modules'))->toBeTrue();
        expect(Schema::getColumnListing('modules'))->toEqualCanonicalizing([
            'id',
            'name',
            'plan_id',
            'created_at',
            'updated_at',
        ]);

        expect(\App\Models\Module::query()->count())->toBe(50);
        expect(\App\Models\Module::query()->min('plan_id'))->toBe(1);
        expect(\App\Models\Module::query()->max('plan_id'))->toBe(50);
    });
});

test('tenant module index route returns the required text', function (): void {
    $tenant = provisionModuleTenant();
    $user = createModuleTenantUser($tenant);

    actingAs($user);

    $this->get('http://module-demo.localhost/modules')
        ->assertOk()
        ->assertSeeText('THIS IS MODULE INDEX');
});

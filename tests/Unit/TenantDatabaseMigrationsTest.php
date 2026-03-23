<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Artisan::call('migrate:fresh', [
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ]);
});

test('tenant database migrations create the lending cooperative tables', function () {
    expect(Schema::hasTable('branches'))->toBeTrue();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('members'))->toBeTrue();
    expect(Schema::hasTable('loan_types'))->toBeTrue();
    expect(Schema::hasTable('loans'))->toBeTrue();
    expect(Schema::hasTable('loan_payments'))->toBeTrue();
    expect(Schema::hasTable('loan_schedules'))->toBeTrue();
    expect(Schema::hasTable('audit_logs'))->toBeTrue();
    expect(Schema::hasTable('roles'))->toBeTrue();
    expect(Schema::hasTable('permissions'))->toBeTrue();
    expect(Schema::hasTable('model_has_roles'))->toBeTrue();
    expect(Schema::hasTable('model_has_permissions'))->toBeTrue();
    expect(Schema::hasTable('role_has_permissions'))->toBeTrue();

    expect(Schema::getColumnListing('branches'))->toBe([
        'id',
        'name',
        'address',
        'is_active',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('members'))->toBe([
        'id',
        'branch_id',
        'member_number',
        'first_name',
        'last_name',
        'middle_name',
        'birthdate',
        'gender',
        'civil_status',
        'address',
        'phone',
        'email',
        'occupation',
        'is_active',
        'joined_at',
        'created_at',
        'updated_at',
    ]);

    expect(Schema::getColumnListing('loans'))->toBe([
        'id',
        'member_id',
        'branch_id',
        'user_id',
        'loan_type_id',
        'loan_number',
        'principal_amount',
        'interest_rate',
        'interest_type',
        'term_months',
        'total_interest',
        'total_payable',
        'monthly_payment',
        'amount_paid',
        'outstanding_balance',
        'status',
        'release_date',
        'due_date',
        'notes',
        'created_at',
        'updated_at',
    ]);
});

test('tenant database migrations add the required indexes and foreign keys', function () {
    $userIndexes = collect(DB::select("PRAGMA index_list('users')"))->pluck('name');
    $memberIndexes = collect(DB::select("PRAGMA index_list('members')"))->pluck('name');
    $loanIndexes = collect(DB::select("PRAGMA index_list('loans')"))->pluck('name');
    $loanPaymentIndexes = collect(DB::select("PRAGMA index_list('loan_payments')"))->pluck('name');
    $loanScheduleIndexes = collect(DB::select("PRAGMA index_list('loan_schedules')"))->pluck('name');

    expect($userIndexes)->toContain('users_branch_id_index');
    expect($memberIndexes)->toContain('members_branch_id_index');
    expect($loanIndexes)->toContain('loans_member_id_index');
    expect($loanIndexes)->toContain('loans_branch_id_index');
    expect($loanPaymentIndexes)->toContain('loan_payments_loan_id_index');
    expect($loanScheduleIndexes)->toContain('loan_schedules_loan_id_index');

    $userForeignKeys = collect(DB::select("PRAGMA foreign_key_list('users')"));
    $memberForeignKeys = collect(DB::select("PRAGMA foreign_key_list('members')"));
    $loanForeignKeys = collect(DB::select("PRAGMA foreign_key_list('loans')"));
    $loanPaymentForeignKeys = collect(DB::select("PRAGMA foreign_key_list('loan_payments')"));
    $loanScheduleForeignKeys = collect(DB::select("PRAGMA foreign_key_list('loan_schedules')"));
    $auditLogForeignKeys = collect(DB::select("PRAGMA foreign_key_list('audit_logs')"));

    expect($userForeignKeys->pluck('table'))->toContain('branches');
    expect($memberForeignKeys->pluck('table'))->toContain('branches');
    expect($loanForeignKeys->pluck('table'))->toContain('members');
    expect($loanForeignKeys->pluck('table'))->toContain('branches');
    expect($loanForeignKeys->pluck('table'))->toContain('users');
    expect($loanForeignKeys->pluck('table'))->toContain('loan_types');
    expect($loanPaymentForeignKeys->pluck('table'))->toContain('loans');
    expect($loanPaymentForeignKeys->pluck('table'))->toContain('users');
    expect($loanScheduleForeignKeys->pluck('table'))->toContain('loans');
    expect($auditLogForeignKeys->pluck('table'))->toContain('users');
});

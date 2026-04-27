<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const DEFAULT_DESCRIPTION = "Secure cooperative portal access\nLoan and member management\nCentralized reporting tools";

    protected $fillable = [
        'name',
        'price',
        'max_branches',
        'max_users',
        'description',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public static function defaultDescription(): string
    {
        return self::DEFAULT_DESCRIPTION;
    }

    public static function getAvailableFeatures(): array
    {
        return [
            'basic_members' => ['name' => 'Member Management', 'desc' => 'Register and manage cooperative borrowers.', 'requires' => null],
            'loan_management' => ['name' => 'Loan Management', 'desc' => 'Create and track loans with amortization.', 'requires' => null],
            'loan_types' => ['name' => 'Loan Type Configuration', 'desc' => 'Configure custom loan products.', 'requires' => null],
            'payment_tracking' => ['name' => 'Payment Tracking', 'desc' => 'Record and monitor loan payment collections.', 'requires' => null],
            'basic_reports' => ['name' => 'Basic Reports', 'desc' => 'Generate PDF and Excel lending reports.', 'requires' => null],
            'branch_management' => ['name' => 'Branch Management', 'desc' => 'Manage multiple cooperative branches.', 'requires' => null],
            'multi_user' => ['name' => 'Multi-User Access', 'desc' => 'Add staff with role-based access control.', 'requires' => null],
            'collections_dashboard' => ['name' => 'Collections Dashboard', 'desc' => 'Real-time collection monitoring.', 'requires' => 'payment_tracking'],
            'overdue_loan_management' => ['name' => 'Overdue Loan Management', 'desc' => 'Send reminders, apply penalties, restructure, and write off overdue loans.', 'requires' => 'loan_management'],
            'advanced_reports' => ['name' => 'Advanced Reports', 'desc' => 'Full analytics with trend analysis.', 'requires' => 'basic_reports'],
            'audit_logs' => ['name' => 'Audit Logs', 'desc' => 'Complete action history and change tracking.', 'requires' => null],
            'member_documents' => ['name' => 'Member Documents', 'desc' => 'Attach files to member profiles.', 'requires' => 'basic_members'],
            'loan_documents' => ['name' => 'Loan Documents', 'desc' => 'Attach documents to loan records.', 'requires' => 'loan_management'],
            'custom_roles' => ['name' => 'Custom Roles', 'desc' => 'Create custom staff roles and permissions.', 'requires' => 'multi_user'],
            'advanced_analytics' => ['name' => 'Advanced Analytics', 'desc' => 'Business performance insights.', 'requires' => 'advanced_reports'],
        ];
    }

    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->features ?? []);
    }
}

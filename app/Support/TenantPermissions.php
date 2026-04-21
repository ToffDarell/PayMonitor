<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class TenantPermissions
{
    public const RBAC_CUSTOMIZED = 'rbac.customized';

    public const DASHBOARD_VIEW = 'dashboard.view';
    public const MEMBERS_VIEW = 'members.view';
    public const MEMBERS_CREATE = 'members.create';
    public const MEMBERS_UPDATE = 'members.update';
    public const MEMBERS_DELETE = 'members.delete';
    public const MEMBER_DOCUMENTS_VIEW = 'member-documents.view';
    public const MEMBER_DOCUMENTS_UPLOAD = 'member-documents.upload';
    public const MEMBER_DOCUMENTS_DELETE = 'member-documents.delete';
    public const LOANS_VIEW = 'loans.view';
    public const LOANS_CREATE = 'loans.create';
    public const LOANS_UPDATE = 'loans.update';
    public const LOANS_DELETE = 'loans.delete';
    public const LOANS_COMPUTE = 'loans.compute-preview';
    public const LOAN_DOCUMENTS_VIEW = 'loan-documents.view';
    public const LOAN_DOCUMENTS_UPLOAD = 'loan-documents.upload';
    public const LOAN_DOCUMENTS_DELETE = 'loan-documents.delete';
    public const LOAN_PAYMENTS_VIEW = 'loan-payments.view';
    public const LOAN_PAYMENTS_CREATE = 'loan-payments.create';
    public const LOAN_PAYMENTS_UPDATE = 'loan-payments.update';
    public const LOAN_PAYMENTS_DELETE = 'loan-payments.delete';
    public const COLLECTIONS_VIEW = 'collections.view';
    public const LOAN_TYPES_VIEW = 'loan-types.view';
    public const LOAN_TYPES_CREATE = 'loan-types.create';
    public const LOAN_TYPES_UPDATE = 'loan-types.update';
    public const LOAN_TYPES_DELETE = 'loan-types.delete';
    public const BRANCHES_VIEW = 'branches.view';
    public const BRANCHES_CREATE = 'branches.create';
    public const BRANCHES_UPDATE = 'branches.update';
    public const BRANCHES_DELETE = 'branches.delete';
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';
    public const SETTINGS_VIEW = 'settings.view';
    public const SETTINGS_UPDATE = 'settings.update';
    public const REPORTS_VIEW = 'reports.view';
    public const AUDIT_LOGS_VIEW = 'audit-logs.view';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::MEMBERS_VIEW,
            self::MEMBERS_CREATE,
            self::MEMBERS_UPDATE,
            self::MEMBERS_DELETE,
            self::MEMBER_DOCUMENTS_VIEW,
            self::MEMBER_DOCUMENTS_UPLOAD,
            self::MEMBER_DOCUMENTS_DELETE,
            self::LOANS_VIEW,
            self::LOANS_CREATE,
            self::LOANS_UPDATE,
            self::LOANS_DELETE,
            self::LOANS_COMPUTE,
            self::LOAN_DOCUMENTS_VIEW,
            self::LOAN_DOCUMENTS_UPLOAD,
            self::LOAN_DOCUMENTS_DELETE,
            self::LOAN_PAYMENTS_VIEW,
            self::LOAN_PAYMENTS_CREATE,
            self::LOAN_PAYMENTS_UPDATE,
            self::LOAN_PAYMENTS_DELETE,
            self::COLLECTIONS_VIEW,
            self::LOAN_TYPES_VIEW,
            self::LOAN_TYPES_CREATE,
            self::LOAN_TYPES_UPDATE,
            self::LOAN_TYPES_DELETE,
            self::BRANCHES_VIEW,
            self::BRANCHES_CREATE,
            self::BRANCHES_UPDATE,
            self::BRANCHES_DELETE,
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_DELETE,
            self::SETTINGS_VIEW,
            self::SETTINGS_UPDATE,
            self::REPORTS_VIEW,
            self::AUDIT_LOGS_VIEW,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function assignable(): array
    {
        return self::all();
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::DASHBOARD_VIEW => 'View Dashboard',
            self::MEMBERS_VIEW => 'View Members',
            self::MEMBERS_CREATE => 'Create Members',
            self::MEMBERS_UPDATE => 'Edit Members',
            self::MEMBERS_DELETE => 'Delete Members',
            self::MEMBER_DOCUMENTS_VIEW => 'View Member Documents',
            self::MEMBER_DOCUMENTS_UPLOAD => 'Upload Member Documents',
            self::MEMBER_DOCUMENTS_DELETE => 'Delete Member Documents',
            self::LOANS_VIEW => 'View Loans',
            self::LOANS_CREATE => 'Create Loans',
            self::LOANS_UPDATE => 'Edit Loans',
            self::LOANS_DELETE => 'Delete Loans',
            self::LOANS_COMPUTE => 'Compute Loan Preview',
            self::LOAN_DOCUMENTS_VIEW => 'View Loan Documents',
            self::LOAN_DOCUMENTS_UPLOAD => 'Upload Loan Documents',
            self::LOAN_DOCUMENTS_DELETE => 'Delete Loan Documents',
            self::LOAN_PAYMENTS_VIEW => 'View Payments',
            self::LOAN_PAYMENTS_CREATE => 'Record Payments',
            self::LOAN_PAYMENTS_UPDATE => 'Edit Payments',
            self::LOAN_PAYMENTS_DELETE => 'Delete Payments',
            self::COLLECTIONS_VIEW => 'View Collections',
            self::LOAN_TYPES_VIEW => 'View Loan Types',
            self::LOAN_TYPES_CREATE => 'Create Loan Types',
            self::LOAN_TYPES_UPDATE => 'Edit Loan Types',
            self::LOAN_TYPES_DELETE => 'Delete Loan Types',
            self::BRANCHES_VIEW => 'View Branches',
            self::BRANCHES_CREATE => 'Create Branches',
            self::BRANCHES_UPDATE => 'Edit Branches',
            self::BRANCHES_DELETE => 'Delete Branches',
            self::USERS_VIEW => 'View Users',
            self::USERS_CREATE => 'Create Users',
            self::USERS_UPDATE => 'Edit Users',
            self::USERS_DELETE => 'Delete Users',
            self::SETTINGS_VIEW => 'View Settings',
            self::SETTINGS_UPDATE => 'Update Settings',
            self::REPORTS_VIEW => 'View Reports',
            self::AUDIT_LOGS_VIEW => 'View Audit Logs',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function groupedAssignable(): array
    {
        return [
            'General' => [
                self::DASHBOARD_VIEW,
                self::REPORTS_VIEW,
            ],
            'Members' => [
                self::MEMBERS_VIEW,
                self::MEMBERS_CREATE,
                self::MEMBERS_UPDATE,
                self::MEMBERS_DELETE,
                self::MEMBER_DOCUMENTS_VIEW,
                self::MEMBER_DOCUMENTS_UPLOAD,
                self::MEMBER_DOCUMENTS_DELETE,
            ],
            'Loans' => [
                self::LOANS_VIEW,
                self::LOANS_CREATE,
                self::LOANS_UPDATE,
                self::LOANS_DELETE,
                self::LOANS_COMPUTE,
                self::LOAN_DOCUMENTS_VIEW,
                self::LOAN_DOCUMENTS_UPLOAD,
                self::LOAN_DOCUMENTS_DELETE,
            ],
            'Payments' => [
                self::LOAN_PAYMENTS_VIEW,
                self::LOAN_PAYMENTS_CREATE,
                self::LOAN_PAYMENTS_UPDATE,
                self::LOAN_PAYMENTS_DELETE,
                self::COLLECTIONS_VIEW,
            ],
            'Loan Types' => [
                self::LOAN_TYPES_VIEW,
                self::LOAN_TYPES_CREATE,
                self::LOAN_TYPES_UPDATE,
                self::LOAN_TYPES_DELETE,
            ],
            'Compliance' => [
                self::AUDIT_LOGS_VIEW,
            ],
            'Administration' => [
                self::BRANCHES_VIEW,
                self::BRANCHES_CREATE,
                self::BRANCHES_UPDATE,
                self::BRANCHES_DELETE,
                self::USERS_VIEW,
                self::USERS_CREATE,
                self::USERS_UPDATE,
                self::USERS_DELETE,
                self::SETTINGS_VIEW,
                self::SETTINGS_UPDATE,
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function roleDefaults(): array
    {
        return [
            'tenant_admin' => self::all(),
            'branch_manager' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBERS_CREATE,
                self::MEMBERS_UPDATE,
                self::MEMBER_DOCUMENTS_VIEW,
                self::MEMBER_DOCUMENTS_UPLOAD,
                self::LOANS_VIEW,
                self::LOANS_CREATE,
                self::LOANS_UPDATE,
                self::LOANS_COMPUTE,
                self::LOAN_DOCUMENTS_VIEW,
                self::LOAN_DOCUMENTS_UPLOAD,
                self::LOAN_PAYMENTS_VIEW,
                self::LOAN_PAYMENTS_CREATE,
                self::COLLECTIONS_VIEW,
                self::LOAN_TYPES_VIEW,
                self::LOAN_TYPES_CREATE,
                self::LOAN_TYPES_UPDATE,
                self::LOAN_TYPES_DELETE,
                self::REPORTS_VIEW,
            ],
            'loan_officer' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBERS_CREATE,
                self::MEMBERS_UPDATE,
                self::MEMBER_DOCUMENTS_VIEW,
                self::MEMBER_DOCUMENTS_UPLOAD,
                self::LOANS_VIEW,
                self::LOANS_CREATE,
                self::LOANS_COMPUTE,
                self::LOAN_DOCUMENTS_VIEW,
                self::LOAN_DOCUMENTS_UPLOAD,
                self::LOAN_PAYMENTS_VIEW,
                self::LOAN_PAYMENTS_CREATE,
                self::COLLECTIONS_VIEW,
                self::REPORTS_VIEW,
            ],
            'cashier' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::LOANS_VIEW,
                self::LOAN_PAYMENTS_VIEW,
                self::LOAN_PAYMENTS_CREATE,
                self::COLLECTIONS_VIEW,
                self::REPORTS_VIEW,
            ],
            'viewer' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBER_DOCUMENTS_VIEW,
                self::LOANS_VIEW,
                self::LOAN_DOCUMENTS_VIEW,
                self::LOAN_PAYMENTS_VIEW,
                self::COLLECTIONS_VIEW,
                self::REPORTS_VIEW,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function defaultsForRole(string $role): array
    {
        return self::roleDefaults()[$role] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function systemRoles(): array
    {
        return array_keys(self::roleDefaults());
    }

    public static function isSystemRole(string $role): bool
    {
        return in_array($role, self::systemRoles(), true);
    }

    public static function displayRoleName(string $role): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $role));
    }

    /**
     * @return array<int, string>
     */
    public static function permissionsForRole(string $role, string $guard = 'web'): array
    {
        $roleModel = Role::query()
            ->where('name', $role)
            ->where('guard_name', $guard)
            ->first();

        if ($roleModel !== null) {
            return $roleModel->permissions()
                ->pluck('name')
                ->reject(static fn (string $permission): bool => $permission === self::RBAC_CUSTOMIZED)
                ->values()
                ->all();
        }

        return self::defaultsForRole($role);
    }

    /**
     * @return array<int, string>
     */
    public static function rolesFor(string $permission): array
    {
        $roles = [];

        foreach (self::roleDefaults() as $role => $permissions) {
            if (in_array($permission, $permissions, true)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    public static function ensureConfigured(string $guard = 'web'): void
    {
        foreach ([self::RBAC_CUSTOMIZED, ...self::all()] as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        foreach (self::roleDefaults() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($permissions);
        }
    }
}

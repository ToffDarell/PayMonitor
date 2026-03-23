<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\LoanPaymentPolicy;
use App\Policies\LoanPolicy;
use App\Policies\LoanTypePolicy;
use App\Policies\MemberPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Branch::class => BranchPolicy::class,
        User::class => UserPolicy::class,
        Member::class => MemberPolicy::class,
        LoanType::class => LoanTypePolicy::class,
        Loan::class => LoanPolicy::class,
        LoanPayment::class => LoanPaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Contracts\Policies;

use App\Models\User;
use Modules\Contracts\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'customers.view', 'customers.manage',
        ]);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->hasAnyPermission(['customers.view', 'customers.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->hasPermissionTo('customers.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->hasPermissionTo('customers.manage');
    }
}

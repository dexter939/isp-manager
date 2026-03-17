<?php

declare(strict_types=1);

namespace Modules\Contracts\Policies;

use App\Models\User;
use Modules\Contracts\Models\Contract;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'contracts.view', 'contracts.manage',
        ]);
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id
            && $user->hasAnyPermission(['contracts.view', 'contracts.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('contracts.manage');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id
            && $user->hasPermissionTo('contracts.manage');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id
            && $user->hasRole(['super_admin', 'admin']);
    }
}

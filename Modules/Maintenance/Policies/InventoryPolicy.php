<?php

declare(strict_types=1);

namespace Modules\Maintenance\Policies;

use App\Models\User;
use Modules\Maintenance\Models\InventoryItem;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('warehouse');
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id
            && ($user->hasRole('admin') || $user->hasRole('warehouse'));
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id
            && $user->hasRole('admin');
    }
}

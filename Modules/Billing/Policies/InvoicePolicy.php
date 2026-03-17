<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use App\Models\User;
use Modules\Billing\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'billing', 'agent']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (!$user->hasAnyRole(['super_admin', 'admin', 'billing', 'agent'])) {
            return false;
        }
        // agent può vedere solo le fatture dei propri contratti
        if ($user->hasRole('agent') && !$user->hasRole('admin')) {
            return $invoice->agent_id === $user->id;
        }
        return $invoice->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'billing']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'billing'])
            && $invoice->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin'])
            && $invoice->tenant_id === $user->tenant_id;
    }
}

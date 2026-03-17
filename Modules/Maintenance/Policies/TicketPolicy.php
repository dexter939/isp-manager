<?php

declare(strict_types=1);

namespace Modules\Maintenance\Policies;

use App\Models\User;
use Modules\Maintenance\Models\TroubleTicket;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // tutti gli operatori autenticati possono listare
    }

    public function view(User $user, TroubleTicket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TroubleTicket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id;
    }

    public function delete(User $user, TroubleTicket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id
            && $user->hasRole('admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * Wrapper Authenticatable per gli agenti — punta alla tabella `agents`.
 * Usato esclusivamente dal guard `agent` (portale agenti).
 */
class AgentPortalUser extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    protected $table = 'agents';

    protected $hidden = ['portal_password', 'portal_remember_token'];

    protected $casts = [
        'portal_last_login_at' => 'datetime',
        'commission_rate'      => 'decimal:2',
    ];

    /** Laravel usa portal_email come identificatore di login */
    public function getAuthIdentifierName(): string
    {
        return 'portal_email';
    }

    /** Laravel usa questo campo come password */
    public function getAuthPassword(): string
    {
        return $this->portal_password ?? '';
    }

    public function getRememberTokenName(): string
    {
        return 'portal_remember_token';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->portal_email ?? '';
    }

    /** Nome visualizzabile nel portale */
    public function getDisplayNameAttribute(): string
    {
        return $this->business_name;
    }
}

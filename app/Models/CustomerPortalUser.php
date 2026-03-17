<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * Wrapper Authenticatable per i clienti — punta alla tabella `customers`.
 * Non sostituisce Modules\Contracts\Models\Customer, serve solo al guard portale.
 */
class CustomerPortalUser extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'email',
        'portal_password',
        'portal_remember_token',
        'portal_email_verified_at',
        'portal_last_login_at',
    ];

    protected $hidden = ['portal_password', 'portal_remember_token'];

    protected $casts = [
        'portal_email_verified_at' => 'datetime',
        'portal_last_login_at'     => 'datetime',
    ];

    /** Laravel usa questo campo come password */
    public function getAuthPassword(): string
    {
        return $this->portal_password ?? '';
    }

    public function getRememberTokenName(): string
    {
        return 'portal_remember_token';
    }

    /** Nome visualizzabile nel portale */
    public function getDisplayNameAttribute(): string
    {
        return $this->ragione_sociale
            ?? trim("{$this->nome} {$this->cognome}");
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email ?? '';
    }
}

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Wizard Session TTL
    |--------------------------------------------------------------------------
    | Durata sessione wizard in ore. Dopo questo tempo la sessione in Redis
    | viene invalidata automaticamente.
    */
    'session_ttl_hours' => env('WIZARD_SESSION_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry
    |--------------------------------------------------------------------------
    | Minuti di validità del codice OTP inviato al cliente.
    */
    'otp_expires_minutes' => env('WIZARD_OTP_EXPIRES_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | OTP Length
    |--------------------------------------------------------------------------
    | Numero di cifre del codice OTP.
    */
    'otp_length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Redis Prefix
    |--------------------------------------------------------------------------
    | Prefisso chiave Redis per le sessioni wizard.
    */
    'redis_prefix' => 'wizard:session:',
];

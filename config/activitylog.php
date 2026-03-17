<?php

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * Se vuoi usare un modello Activity custom, mettilo qui.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * Tabella dove vengono salvati i log.
     */
    'table_name' => env('ACTIVITY_LOG_TABLE_NAME', 'activity_log'),

    /*
     * Database connection per la tabella activity_log.
     */
    'database_connection' => env('ACTIVITY_LOG_DB_CONNECTION', env('DB_CONNECTION', 'pgsql')),

    /*
     * Abilita il logging automatico dei modelli Eloquent
     * (tramite HasActivity trait).
     */
    'default_auth_driver' => null,

    /*
     * Numero max di log entries da mantenere per modello.
     * null = nessun limite.
     */
    'delete_records_older_than_days' => 365,

    /*
     * Formato del soggetto causante.
     */
    'subject_returns_soft_deleted_models' => true,
];

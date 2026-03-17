<?php

return [

    // ── RADIUS / FreeRADIUS ───────────────────────────────────────────────────

    /*
     * Shared secret tra Laravel e FreeRADIUS per CoA/Disconnect (RFC 5176).
     * Deve corrispondere al client.conf del NAS e al file clients.conf di FreeRADIUS.
     */
    'radius_coa_secret' => env('RADIUS_COA_SECRET', 'testing123'),

    /*
     * Porta UDP CoA sul NAS (default RFC 5176 = 3799).
     */
    'radius_coa_port' => (int) env('RADIUS_COA_PORT', 3799),

    /*
     * Timeout in secondi per i comandi radclient.
     */
    'radius_coa_timeout' => (int) env('RADIUS_COA_TIMEOUT', 5),

    // ── Walled Garden ─────────────────────────────────────────────────────────

    /*
     * URL del portale captive a cui vengono reindirizzati gli utenti sospesi.
     * Il token univoco viene appeso come query string: ?token=<uuid>.
     */
    'walled_garden_url' => env('WALLED_GARDEN_URL', 'https://paga.isp.local'),

    /*
     * TTL del token walled garden in ore (default 72h).
     */
    'walled_garden_token_ttl_hours' => (int) env('WALLED_GARDEN_TOKEN_TTL', 72),

    /*
     * Banda assegnata agli utenti in walled garden (kbps).
     */
    'walled_garden_dl_kbps' => (int) env('WALLED_GARDEN_DL_KBPS', 128),
    'walled_garden_ul_kbps' => (int) env('WALLED_GARDEN_UL_KBPS', 128),

    // ── Data Retention (Decreto Pisanu) ───────────────────────────────────────

    /*
     * Anni di retention per i log di accounting RADIUS (D.Lgs 196/2003).
     */
    'retention_years' => (int) env('RADIUS_RETENTION_YEARS', 6),

    // ── SNMP Poller ───────────────────────────────────────────────────────────

    /*
     * Intervallo in secondi tra un ciclo di polling SNMP e il successivo.
     */
    'snmp_poll_interval' => (int) env('SNMP_POLL_INTERVAL', 300),

];

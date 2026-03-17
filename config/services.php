<?php

return [

    // ── Stripe ────────────────────────────────────────────────────────────────
    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // ── Twilio / WhatsApp ─────────────────────────────────────────────────────
    'twilio' => [
        'account_sid'       => env('TWILIO_ACCOUNT_SID'),
        'auth_token'        => env('TWILIO_AUTH_TOKEN'),
        'from_number'       => env('TWILIO_FROM_NUMBER'),
        'default_tenant_id' => env('TWILIO_DEFAULT_TENANT_ID'),
    ],

    'whatsapp' => [
        'token'             => env('WHATSAPP_TOKEN'),
        'phone_number_id'   => env('WHATSAPP_PHONE_NUMBER_ID'),
        'app_secret'        => env('WHATSAPP_APP_SECRET'),
        'verify_token'      => env('WHATSAPP_VERIFY_TOKEN'),
        'default_tenant_id' => env('WHATSAPP_DEFAULT_TENANT_ID'),
    ],

    // ── Anthropic / AI ────────────────────────────────────────────────────────
    'anthropic' => [
        'api_key'       => env('ANTHROPIC_API_KEY'),
        'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    // ── GenieACS (TR-069 ACS) ─────────────────────────────────────────────────
    'genieacs' => [
        'url'      => env('GENIEACS_BASE_URL', 'http://localhost:7557'),
        'username' => env('GENIEACS_USERNAME', 'admin'),
        'password' => env('GENIEACS_PASSWORD', ''),
    ],

    // ── SNMP ──────────────────────────────────────────────────────────────────
    'snmp' => [
        'community'  => env('SNMP_COMMUNITY', 'public'),
        'timeout_ms' => (int) env('SNMP_TIMEOUT_MS', 2000),
        'retries'    => (int) env('SNMP_RETRIES', 2),
    ],

    // ── Open Fiber ────────────────────────────────────────────────────────────
    'openfiber' => [
        'lt_base_url' => env('OF_LINE_TESTING_BASE_URL', 'https://api.openfiber.it/linetesting/v1'),
        'lt_api_key'  => env('OF_TOKEN_ID'),
        'olo_code'    => env('OF_CODICE_OPERATORE'),
    ],

    // ── FiberCop ──────────────────────────────────────────────────────────────
    'fibercop' => [
        'ngasp_base_url'  => env('FC_API_BASE_URL', 'https://api.fibercop.it/ngasp/v1'),
        'client_key'      => env('FC_CLIENT_KEY'),
        'client_secret'   => env('FC_CLIENT_SECRET'),
        'oauth_token_url' => env('FC_OAUTH_TOKEN_URL', 'https://api.fibercop.it/oauth/token'),
    ],

    // ── Postman / Webhook (interno) ───────────────────────────────────────────
    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

];

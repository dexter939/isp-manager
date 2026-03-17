<?php

return [
    'channel'        => env('SDI_CHANNEL', 'aruba'), // 'aruba'|'pec'
    'aruba_endpoint' => env('SDI_ARUBA_ENDPOINT', 'https://fatturazioneelettronica.aruba.it/v1'),
    'aruba_api_key'  => env('SDI_ARUBA_API_KEY', ''),
    'max_retries'    => (int) env('SDI_MAX_RETRIES', 3),
    'validate_xsd'   => (bool) env('SDI_VALIDATE_XSD', true),
    'cedente' => [
        'partita_iva'    => env('COMPANY_PIVA', ''),
        'codice_fiscale' => env('COMPANY_CF', ''),
        'denominazione'  => env('COMPANY_NAME', 'ISPManager Srl'),
        'indirizzo'      => env('COMPANY_ADDRESS', ''),
        'cap'            => env('COMPANY_CAP', ''),
        'comune'         => env('COMPANY_CITY', ''),
        'provincia'      => env('COMPANY_PROVINCE', ''),
        'nazione'        => 'IT',
        'regime_fiscale' => env('COMPANY_REGIME_FISCALE', 'RF01'),
    ],
    'retention_years' => 10,
];

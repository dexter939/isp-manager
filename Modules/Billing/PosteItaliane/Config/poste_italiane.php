<?php

return [
    'conto_corrente'  => env('POSTE_CONTO_CORRENTE', ''),
    'intestatario'    => env('POSTE_INTESTATARIO', ''),
    'scadenza_giorni' => (int) env('POSTE_SCADENZA_GIORNI', 30),
    'mock'            => (bool) env('POSTE_MOCK', false),
];

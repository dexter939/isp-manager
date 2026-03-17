<?php
return [
    'provider'         => env('ROUTE_PROVIDER', 'osrm'),
    'osrm_url'         => env('OSRM_URL', 'https://router.project-osrm.org'),
    'google_maps_key'  => env('GOOGLE_MAPS_API_KEY'),
    'max_greedy_stops' => (int)env('MAX_GREEDY_STOPS', 20),
    'cache_ttl_hours'  => (int)env('ROUTE_CACHE_TTL_HOURS', 24),
];

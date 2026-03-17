<?php
return [
    'provider'              => env('ELEVATION_PROVIDER', 'open_elevation'),
    'open_elevation_url'    => env('OPEN_ELEVATION_URL', 'https://api.open-elevation.com/api/v1/lookup'),
    'srtm_data_path'        => storage_path('srtm/'),
    'default_sample_points' => (int)env('ELEVATION_SAMPLE_POINTS', 100),
    'default_antenna_height_m' => (int)env('ELEVATION_ANTENNA_HEIGHT', 10),
    'default_cpe_height_m'  => (int)env('ELEVATION_CPE_HEIGHT', 3),
    'cache_ttl_days'        => (int)env('ELEVATION_CACHE_TTL_DAYS', 7),
];

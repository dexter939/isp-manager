<?php

// Laravel 11 explicit provider registration.
// nwidart/laravel-modules discovers module providers via config/modules.php,
// so only app-level providers need to be listed here.

return [
    App\Providers\AppServiceProvider::class,
    Modules\Infrastructure\NetworkSites\Providers\NetworkSitesServiceProvider::class,
    Modules\Infrastructure\Topology\Providers\TopologyServiceProvider::class,
    Modules\Infrastructure\TopologyDiscovery\Providers\TopologyDiscoveryServiceProvider::class,
];

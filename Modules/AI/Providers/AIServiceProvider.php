<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AI\Services\PredictiveMarketingService;
use Modules\AI\Services\TicketWriterService;
use Modules\AI\Services\VoiceService;
use Modules\AI\Services\WhatsAppService;

class AIServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AI';
    protected string $moduleNameLower = 'ai';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Bind services as singletons — shared state-free, safe for Octane
        $this->app->singleton(TicketWriterService::class);
        $this->app->singleton(WhatsAppService::class);
        $this->app->singleton(VoiceService::class);
        $this->app->singleton(PredictiveMarketingService::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }
}

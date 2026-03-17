<?php

declare(strict_types=1);

namespace Modules\Maintenance\RouteOptimizer\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Maintenance\RouteOptimizer\Services\RouteOptimizerService;

class RoutesOptimizeCommand extends Command
{
    protected $signature = 'routes:optimize
                            {--technician= : Technician user ID (omit for all active technicians)}
                            {--date=       : Date to optimize (Y-m-d, default: today)}';

    protected $description = 'Optimize GPS route plans for field technicians (TSP nearest-neighbor)';

    public function handle(RouteOptimizerService $service): int
    {
        $date = Carbon::parse($this->option('date') ?? today());

        if ($techId = $this->option('technician')) {
            $technicians = DB::table('users')->where('id', $techId)->get();
        } else {
            $technicians = DB::table('users')
                ->where('is_active', true)
                ->whereJsonContains('roles', 'technician')
                ->get();
        }

        if ($technicians->isEmpty()) {
            $this->warn('No technicians found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($technicians->count());
        $bar->start();

        foreach ($technicians as $tech) {
            try {
                $plan = $service->optimize($tech->id, $date);
                $this->line(" Technician {$tech->name}: {$plan->total_distance_km} km, {$plan->total_duration_minutes} min");
            } catch (\Throwable $e) {
                $this->error(" Technician {$tech->name}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Route optimization completed.');

        return self::SUCCESS;
    }
}

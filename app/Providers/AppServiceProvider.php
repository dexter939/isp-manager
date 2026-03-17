<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\Helper;
use App\Listeners\Mail\SendContractSignedMail;
use App\Listeners\Mail\SendInvoiceGeneratedMail;
use App\Listeners\Mail\SendInvoiceOverdueMail;
use App\Listeners\Mail\SendPaymentReceivedMail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Events\InvoiceGenerated;
use Modules\Billing\Events\InvoiceOverdue;
use Modules\Billing\Events\PaymentReceived;
use Modules\Contracts\Events\ContractSigned;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Helper alias for Blade views
        $this->app->bind('helper', fn() => new Helper());
    }

    public function boot(): void
    {
        // Super admin bypassa tutte le policy
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Enforce HTTPS in produzione
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // ── Email template listeners ───────────────────────────────────────
        Event::listen(InvoiceGenerated::class, SendInvoiceGeneratedMail::class);
        Event::listen(InvoiceOverdue::class,   SendInvoiceOverdueMail::class);
        Event::listen(PaymentReceived::class,  SendPaymentReceivedMail::class);
        Event::listen(ContractSigned::class,   SendContractSignedMail::class);

        // Bootstrap 5 pagination
        Paginator::useBootstrapFive();

        // Model::preventLazyLoading in sviluppo
        if ($this->app->isLocal()) {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading();
        }

        // Reset per-request static state in Octane (correct event — not tick)
        Event::listen(\Laravel\Octane\Events\RequestReceived::class, function () {
            Helper::resetPageConfig();
        });
    }
}

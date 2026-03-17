<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\EmailTemplateWebController;
use App\Http\Controllers\Web\SuperAdminController;
use App\Http\Controllers\Web\BillingWebController;
use App\Http\Controllers\Web\ServicePlanWebController;
use App\Http\Controllers\Web\SettingsWebController;
use App\Http\Controllers\Web\ContractWebController;
use App\Http\Controllers\Web\CoverageWebController;
use App\Http\Controllers\Web\CustomerWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\InventoryWebController;
use App\Http\Controllers\Web\MaintenanceWebController;
use App\Http\Controllers\Web\MonitoringWebController;
use App\Http\Controllers\Web\NetworkWebController;
use App\Http\Controllers\Web\ReportingWebController;
use App\Http\Controllers\Web\TicketWebController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\AgentPortal\AgentPortalAuthController;
use App\Http\Controllers\AgentPortal\AgentPortalController;
use App\Http\Controllers\Web\AgentWebController;
use App\Http\Controllers\Web\ProvisioningWebController;
use App\Http\Controllers\Web\SdiWebController;
use App\Http\Controllers\Web\PrepaidWebController;
use Illuminate\Support\Facades\Route;

// ── Auth (guest) ──────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',           [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login',          [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password',[AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile',           [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile',           [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::patch('/profile/password',[AuthController::class, 'updatePassword'])->name('profile.password');

    // Settings
    Route::get('/settings',             [SettingsWebController::class, 'show'])->name('settings.show');
    Route::put('/settings',             [SettingsWebController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingsWebController::class, 'testEmail'])->name('settings.test-email');

    // ── Email Templates ───────────────────────────────────────────────────────
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/',              [EmailTemplateWebController::class, 'index'])->name('index');
        Route::get('/{slug}/edit',   [EmailTemplateWebController::class, 'edit'])->name('edit');
        Route::put('/{slug}',        [EmailTemplateWebController::class, 'update'])->name('update');
        Route::delete('/{slug}',     [EmailTemplateWebController::class, 'reset'])->name('reset');
        Route::post('/{slug}/toggle',[EmailTemplateWebController::class, 'toggle'])->name('toggle');
        Route::get('/{slug}/preview',[EmailTemplateWebController::class, 'preview'])->name('preview');
        Route::post('/{slug}/test',  [EmailTemplateWebController::class, 'sendTest'])->name('test');
    });

    // ── Customers ─────────────────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',           [CustomerWebController::class, 'index'])->name('index');
        Route::get('/create',     [CustomerWebController::class, 'create'])->name('create');
        Route::post('/',          [CustomerWebController::class, 'store'])->name('store');
        Route::get('/{id}',       [CustomerWebController::class, 'show'])->name('show');
        Route::get('/{id}/edit',  [CustomerWebController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [CustomerWebController::class, 'update'])->name('update');
        Route::delete('/{id}',    [CustomerWebController::class, 'destroy'])->name('destroy');
    });

    // ── Service Plans ─────────────────────────────────────────────────────────
    Route::prefix('service-plans')->name('service-plans.')->group(function () {
        Route::get('/',           [ServicePlanWebController::class, 'index'])->name('index');
        Route::get('/create',     [ServicePlanWebController::class, 'create'])->name('create');
        Route::post('/',          [ServicePlanWebController::class, 'store'])->name('store');
        Route::get('/{id}/edit',  [ServicePlanWebController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [ServicePlanWebController::class, 'update'])->name('update');
        Route::delete('/{id}',    [ServicePlanWebController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle', [ServicePlanWebController::class, 'toggleActive'])->name('toggle');
    });

    // ── Contracts ─────────────────────────────────────────────────────────────
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/',                  [ContractWebController::class, 'index'])->name('index');
        Route::get('/create',            [ContractWebController::class, 'create'])->name('create');
        Route::post('/',                 [ContractWebController::class, 'store'])->name('store');
        Route::get('/{id}',              [ContractWebController::class, 'show'])->name('show');
        Route::get('/{id}/edit',         [ContractWebController::class, 'edit'])->name('edit');
        Route::put('/{id}',              [ContractWebController::class, 'update'])->name('update');
        Route::patch('/{id}/suspend',    [ContractWebController::class, 'suspend'])->name('suspend');
        Route::patch('/{id}/reactivate', [ContractWebController::class, 'reactivate'])->name('reactivate');
    });

    // ── Provisioning ──────────────────────────────────────────────────────────
    Route::prefix('provisioning')->name('provisioning.')->group(function () {
        Route::get('/',              [ProvisioningWebController::class, 'index'])->name('index');
        Route::get('/create',        [ProvisioningWebController::class, 'create'])->name('create');
        Route::post('/',             [ProvisioningWebController::class, 'store'])->name('store');
        Route::get('/vlan-pool',     [ProvisioningWebController::class, 'vlanPool'])->name('vlan-pool');
        Route::get('/{id}',          [ProvisioningWebController::class, 'show'])->name('show');
        Route::post('/{id}/send',    [ProvisioningWebController::class, 'send'])->name('send');
        Route::post('/{id}/cancel',  [ProvisioningWebController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/reschedule', [ProvisioningWebController::class, 'reschedule'])->name('reschedule');
        Route::post('/{id}/unsuspend',  [ProvisioningWebController::class, 'unsuspend'])->name('unsuspend');
    });

    // ── Tickets ───────────────────────────────────────────────────────────────
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/',                    [TicketWebController::class, 'index'])->name('index');
        Route::get('/sla',                 [TicketWebController::class, 'sla'])->name('sla');
        Route::get('/create',              [TicketWebController::class, 'create'])->name('create');
        Route::post('/',                   [TicketWebController::class, 'store'])->name('store');
        Route::get('/{id}',                [TicketWebController::class, 'show'])->name('show');
        Route::patch('/{id}/resolve',      [TicketWebController::class, 'resolve'])->name('resolve');
        Route::post('/{id}/transition',    [TicketWebController::class, 'transition'])->name('transition');
        Route::post('/{id}/assign',        [TicketWebController::class, 'assign'])->name('assign');
        Route::post('/{id}/notes',         [TicketWebController::class, 'addNote'])->name('note');
    });

    // ── Billing ───────────────────────────────────────────────────────────────
    Route::prefix('billing')->name('billing.')->group(function () {

        // SDI / Fatturazione Elettronica
        Route::prefix('sdi')->name('sdi.')->group(function () {
            Route::get('/',          [SdiWebController::class, 'index'])->name('index');
            Route::get('/batch',     [SdiWebController::class, 'batch'])->name('batch');
            Route::post('/batch',    [SdiWebController::class, 'batchTransmit'])->name('batch.post');
            Route::get('/{id}',      [SdiWebController::class, 'show'])->name('show');
            Route::post('/{id}/retry', [SdiWebController::class, 'retry'])->name('retry');
        });

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',          [BillingWebController::class, 'invoiceIndex'])->name('index');
            Route::get('/{id}',      [BillingWebController::class, 'invoiceShow'])->name('show');
            Route::get('/{id}/pdf',  [BillingWebController::class, 'invoicePdf'])->name('pdf');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [BillingWebController::class, 'paymentIndex'])->name('index');
        });

        Route::prefix('proforma')->name('proforma.')->group(function () {
            Route::get('/', [BillingWebController::class, 'proformaIndex'])->name('index');
        });

        Route::prefix('bundles')->name('bundles.')->group(function () {
            Route::get('/',             [BillingWebController::class, 'bundlesIndex'])->name('index');
            Route::post('/',            [BillingWebController::class, 'bundleStore'])->name('store');
            Route::put('/{id}',         [BillingWebController::class, 'bundleUpdate'])->name('update');
            Route::delete('/{id}',      [BillingWebController::class, 'bundleDestroy'])->name('destroy');
            Route::post('/{id}/toggle', [BillingWebController::class, 'bundleToggle'])->name('toggle');
        });

        Route::get('/payment-matching', [BillingWebController::class, 'paymentMatchingIndex'])->name('payment-matching.index');

        // SEPA SDD
        Route::prefix('sepa')->name('sepa.')->group(function () {
            Route::get('/',             [BillingWebController::class, 'sepaIndex'])->name('index');
            Route::post('/generate',    [BillingWebController::class, 'sepaGenerate'])->name('generate');
            Route::post('/import',      [BillingWebController::class, 'sepaImportReturn'])->name('import');
        });

        // Generazione fatture manuale
        Route::prefix('run')->name('run.')->group(function () {
            Route::get('/',             [BillingWebController::class, 'billingRunIndex'])->name('index');
            Route::post('/generate',    [BillingWebController::class, 'billingRunGenerate'])->name('generate');
        });

        // Prepaid / Ricariche
        Route::prefix('prepaid')->name('prepaid.')->group(function () {
            Route::get('/wallets',            [PrepaidWebController::class, 'walletsIndex'])->name('wallets.index');
            Route::get('/wallets/{id}',       [PrepaidWebController::class, 'walletShow'])->name('wallets.show');
            Route::post('/wallets/{id}/adjust',[PrepaidWebController::class, 'walletAdjust'])->name('wallets.adjust');
            Route::get('/products',           [PrepaidWebController::class, 'productsIndex'])->name('products.index');
            Route::post('/products',          [PrepaidWebController::class, 'productStore'])->name('products.store');
            Route::put('/products/{id}',      [PrepaidWebController::class, 'productUpdate'])->name('products.update');
            Route::post('/products/{id}/toggle',[PrepaidWebController::class, 'productToggle'])->name('products.toggle');
            Route::delete('/products/{id}',   [PrepaidWebController::class, 'productDestroy'])->name('products.destroy');
        });

        Route::prefix('dunning')->name('dunning.')->group(function () {
            Route::get('/', [BillingWebController::class, 'dunningIndex'])->name('index');
        });
    });

    // ── Coverage ──────────────────────────────────────────────────────────────
    Route::prefix('coverage')->name('coverage.')->group(function () {
        Route::get('/',              [CoverageWebController::class, 'index'])->name('index');
        Route::get('/feasibility',   [CoverageWebController::class, 'feasibility'])->name('feasibility');
        Route::post('/import',       [CoverageWebController::class, 'import'])->name('import');
        Route::get('/elevation',     [CoverageWebController::class, 'elevationIndex'])->name('elevation.index');
        Route::post('/elevation',    [CoverageWebController::class, 'elevationCalculate'])->name('elevation.calculate');
    });

    // ── Network ───────────────────────────────────────────────────────────────
    Route::prefix('network')->name('network.')->group(function () {
        Route::get('/radius',     [NetworkWebController::class, 'radius'])->name('radius');
        Route::get('/fair-usage', [NetworkWebController::class, 'fairUsageIndex'])->name('fair-usage.index');

        Route::prefix('topology')->name('topology.')->group(function () {
            Route::get('/',                    [NetworkWebController::class, 'topologyIndex'])->name('index');
            Route::post('/links',              [NetworkWebController::class, 'topologyLinkStore'])->name('links.store');
            Route::delete('/links/{id}',       [NetworkWebController::class, 'topologyLinkDestroy'])->name('links.destroy');
            Route::post('/discovery/run',      [NetworkWebController::class, 'topologyDiscoveryRun'])->name('discovery.run');
            Route::post('/discovery/{id}/confirm', [NetworkWebController::class, 'topologyDiscoveryConfirm'])->name('discovery.confirm');
            Route::post('/discovery/{id}/reject',  [NetworkWebController::class, 'topologyDiscoveryReject'])->name('discovery.reject');
        });

        Route::prefix('sites')->name('sites.')->group(function () {
            Route::get('/',     [NetworkWebController::class, 'sitesIndex'])->name('index');
            Route::get('/{id}', [NetworkWebController::class, 'sitesShow'])->name('show');
        });
    });

    // ── Monitoring ────────────────────────────────────────────────────────────
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/',       [MonitoringWebController::class, 'index'])->name('index');
        Route::get('/alerts', [MonitoringWebController::class, 'alerts'])->name('alerts');

        Route::prefix('cpe')->name('cpe.')->group(function () {
            Route::get('/',     [MonitoringWebController::class, 'cpeIndex'])->name('index');
            Route::get('/{id}', [MonitoringWebController::class, 'cpeShow'])->name('show');
        });
    });

    // ── Maintenance ───────────────────────────────────────────────────────────
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/oncall',  [MaintenanceWebController::class, 'onCallIndex'])->name('oncall.index');

        Route::prefix('dispatcher')->name('dispatcher.')->group(function () {
            Route::get('/',                    [MaintenanceWebController::class, 'dispatcherIndex'])->name('index');
            Route::post('/assignments',        [MaintenanceWebController::class, 'dispatcherAssignmentStore'])->name('assignments.store');
            Route::delete('/assignments/{id}', [MaintenanceWebController::class, 'dispatcherAssignmentDestroy'])->name('assignments.destroy');
        });

        Route::get('/inventory-rma', [MaintenanceWebController::class, 'inventoryRmaIndex'])->name('inventory-rma.index');

        Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
            Route::get('/',              [MaintenanceWebController::class, 'purchaseOrdersIndex'])->name('index');
            Route::post('/',             [MaintenanceWebController::class, 'purchaseOrderStore'])->name('store');
            Route::post('/{id}/approve', [MaintenanceWebController::class, 'purchaseOrderApprove'])->name('approve');
            Route::post('/{id}/receive', [MaintenanceWebController::class, 'purchaseOrderReceive'])->name('receive');
        });

        Route::prefix('route-optimizer')->name('route-optimizer.')->group(function () {
            Route::get('/',          [MaintenanceWebController::class, 'routeOptimizerIndex'])->name('index');
            Route::post('/generate', [MaintenanceWebController::class, 'routeOptimizerGenerate'])->name('generate');
        });
    });

    // ── Admin ─────────────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:manage-tenants')->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',            [AdminWebController::class, 'usersIndex'])->name('index');
            Route::get('/create',      [AdminWebController::class, 'usersCreate'])->name('create');
            Route::post('/',           [AdminWebController::class, 'usersStore'])->name('store');
            Route::get('/{id}/edit',   [AdminWebController::class, 'usersEdit'])->name('edit');
            Route::put('/{id}',        [AdminWebController::class, 'usersUpdate'])->name('update');
            Route::delete('/{id}',     [AdminWebController::class, 'usersDestroy'])->name('destroy');
        });

        Route::prefix('agents')->name('agents.')->group(function () {
            Route::get('/',            [AgentWebController::class, 'index'])->name('index');
            Route::get('/create',      [AgentWebController::class, 'create'])->name('create');
            Route::post('/',           [AgentWebController::class, 'store'])->name('store');
            Route::get('/{id}',        [AgentWebController::class, 'show'])->name('show');
            Route::get('/{id}/edit',   [AgentWebController::class, 'edit'])->name('edit');
            Route::put('/{id}',        [AgentWebController::class, 'update'])->name('update');
            Route::patch('/{id}/reset-password', [AgentWebController::class, 'resetPortalPassword'])->name('reset-password');
            Route::post('/{agentId}/liquidations/{liquidationId}/approve', [AgentWebController::class, 'approveLiquidation'])->name('liquidations.approve');
            Route::post('/{agentId}/liquidations/{liquidationId}/pay',     [AgentWebController::class, 'payLiquidation'])->name('liquidations.pay');
        });
    });

    // ── Inventory ─────────────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',           [InventoryWebController::class, 'index'])->name('index');
        Route::get('/create',     [InventoryWebController::class, 'create'])->name('create');
        Route::post('/',          [InventoryWebController::class, 'store'])->name('store');
        Route::get('/{id}',       [InventoryWebController::class, 'show'])->name('show');
        Route::get('/{id}/edit',  [InventoryWebController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [InventoryWebController::class, 'update'])->name('update');
        Route::delete('/{id}',    [InventoryWebController::class, 'destroy'])->name('destroy');
    });

    // ── Super Admin ───────────────────────────────────────────────────────────
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/',              [SuperAdminController::class, 'index'])->name('index');
            Route::get('/create',        [SuperAdminController::class, 'create'])->name('create');
            Route::post('/',             [SuperAdminController::class, 'store'])->name('store');
            Route::get('/{id}',          [SuperAdminController::class, 'show'])->name('show');
            Route::get('/{id}/edit',     [SuperAdminController::class, 'edit'])->name('edit');
            Route::put('/{id}',          [SuperAdminController::class, 'update'])->name('update');
            Route::post('/{id}/toggle',  [SuperAdminController::class, 'toggleActive'])->name('toggle');
            Route::post('/{id}/impersonate', [SuperAdminController::class, 'impersonate'])->name('impersonate');
        });
        Route::post('/stop-impersonating', [SuperAdminController::class, 'stopImpersonating'])->name('stop-impersonating');
    });

    // ── API Docs ──────────────────────────────────────────────────────────────
    Route::get('/api-docs', fn () => view('api-docs.index'))->name('api-docs.index');

    // ── Reporting ─────────────────────────────────────────────────────────────
    Route::prefix('reporting')->name('reporting.')->group(function () {
        Route::get('/',         [ReportingWebController::class, 'index'])->name('index');
        Route::get('/revenue',  [ReportingWebController::class, 'revenue'])->name('revenue');
        Route::get('/contracts',[ReportingWebController::class, 'contracts'])->name('contracts');
        Route::get('/tickets',  [ReportingWebController::class, 'tickets'])->name('tickets');
        Route::get('/agents',   [ReportingWebController::class, 'agents'])->name('agents');
    });

});

// ── Agent Portal ──────────────────────────────────────────────────────────────
Route::prefix('agent-portal')->name('agent-portal.')->group(function () {

    Route::middleware('guest:agent')->group(function () {
        Route::get('/login',  [AgentPortalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AgentPortalAuthController::class, 'login'])->name('login.post');
    });

    Route::post('/logout', [AgentPortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:agent')->group(function () {
        Route::get('/',             [AgentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/contracts',    [AgentPortalController::class, 'contracts'])->name('contracts');
        Route::get('/commissions',  [AgentPortalController::class, 'commissions'])->name('commissions');
        Route::get('/liquidations', [AgentPortalController::class, 'liquidations'])->name('liquidations');
        Route::get('/profile',      [AgentPortalController::class, 'profile'])->name('profile');
        Route::patch('/profile/password', [AgentPortalController::class, 'updatePassword'])->name('password.update');
    });
});

// ── Customer Portal ───────────────────────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {

    // Guest (unauthenticated portal)
    Route::middleware('guest:portal')->group(function () {
        Route::get('/login',  [PortalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [PortalAuthController::class, 'login'])->name('login.post');
    });

    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    // Authenticated portal
    Route::middleware('auth:portal')->group(function () {
        Route::get('/',                    [PortalController::class, 'dashboard'])->name('dashboard');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',                              [PortalController::class, 'invoices'])->name('index');
            Route::get('/{id}',                          [PortalController::class, 'invoiceShow'])->name('show');
            Route::get('/{id}/pdf',                      [PortalController::class, 'invoicePdf'])->name('pdf');
            Route::get('/{id}/pay',                      [PortalController::class, 'payInvoice'])->name('pay');
            Route::post('/{id}/pay',                     [PortalController::class, 'initiatePayment'])->name('pay.initiate');
            Route::post('/{id}/charge/{methodId}',       [PortalController::class, 'chargeMethod'])->name('charge');
        });

        Route::get('/payments/success',                  [PortalController::class, 'paymentSuccess'])->name('payments.success');
        Route::get('/payments/cancelled',                [PortalController::class, 'paymentCancelled'])->name('payments.cancelled');
        Route::get('/payment-methods',                   [PortalController::class, 'paymentMethods'])->name('payment-methods');
        Route::delete('/payment-methods/{id}',           [PortalController::class, 'deletePaymentMethod'])->name('payment-methods.destroy');

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                   [PortalController::class, 'tickets'])->name('index');
            Route::get('/create',             [PortalController::class, 'ticketCreate'])->name('create');
            Route::post('/',                  [PortalController::class, 'ticketStore'])->name('store');
            Route::get('/{ticketNumber}',     [PortalController::class, 'ticketShow'])->name('show');
        });
    });
});

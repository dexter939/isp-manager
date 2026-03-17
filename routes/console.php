<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled jobs ────────────────────────────────────────────────────────────

// Genera fatture mensili il 1° di ogni mese alle 06:00
Schedule::command('billing:generate-monthly')->monthlyOn(1, '06:00');

// Genera file SEPA il 5° del mese alle 07:00
Schedule::command('billing:generate-sepa')->monthlyOn(5, '07:00');

// RADIUS sync ogni notte alle 02:00
Schedule::command('radius:sync-users')->dailyAt('02:00');

// Pulizia retention Decreto Pisanu — 1° gennaio alle 03:00
Schedule::command('radius:export-retention')->yearlyOn(1, 1, '03:00');

// Monitoring BTS ogni 5 minuti
Schedule::command('monitoring:poll-bts')->everyFiveMinutes();

// ── Fase 7 BSS/Administrative scheduled jobs ───────────────────────────────

// SDI: riprova invii falliti ogni 6 ore
Schedule::command('sdi:retry')->everySixHours();

// DunningManager: ciclo solleciti ogni giorno alle 08:00
// (sostituisce la voce generica billing:dunning:run)
Schedule::command('dunning:run')->dailyAt('08:00');

// AgentNetwork: liquidazione provvigioni il 1° del mese alle 04:00
Schedule::command('agents:liquidate')->monthlyOn(1, '04:00');

// CDR: fatturazione VoIP il 28 del mese alle 02:00
Schedule::job(new \Modules\Billing\Cdr\Jobs\CdrBillingJob)->monthlyOn(28, '02:00');

// FieldService: pulizia posizioni GPS ogni notte alle 03:00
Schedule::job(new \Modules\Maintenance\FieldService\Jobs\PositionCleanupJob)->dailyAt('03:00');

// ── Fase 8 Competitive Features scheduled jobs ────────────────────────────

// Proforma: scade proforma non convertite ogni notte alle 01:00
Schedule::job(new \Modules\Billing\Proforma\Jobs\ExpireProformasJob)->dailyAt('01:00');

// FairUsage: reset contatori traffico mensile il 1° del mese alle 00:05
Schedule::job(new \Modules\Network\FairUsage\Jobs\FupResetJob)->monthlyOn(1, '00:05');

// PurchaseOrders: verifica soglie di riordino ogni giorno alle 07:00
Schedule::job(new \Modules\Maintenance\PurchaseOrders\Jobs\ReorderCheckJob)->dailyAt('07:00');

// TopologyDiscovery: discovery LLDP/SNMP ogni lunedì alle 03:00
Schedule::job(new \Modules\Infrastructure\TopologyDiscovery\Jobs\TopologyDiscoveryJob)->weeklyOn(1, '03:00');

// SLA: controllo violazioni e escalation ogni 15 minuti
Schedule::command('tickets:check-sla')->everyFifteenMinutes();

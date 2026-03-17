<?php

namespace Modules\Contracts\AgentNetwork\Services;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\AgentNetwork\Events\LiquidationApproved;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionEntry;
use Modules\Contracts\AgentNetwork\Models\CommissionLiquidation;

class CommissionLiquidationService
{
    /**
     * Generates monthly liquidation for all agents with pending entries.
     * Uses DB::transaction().
     */
    public function generateLiquidation(Carbon $month): array
    {
        $periodMonth = $month->startOfMonth()->toDateString();
        $created     = 0;
        $totalMoney  = Money::ofMinor(0, 'EUR');

        DB::transaction(function () use ($periodMonth, &$created, &$totalMoney) {
            $agentIds = CommissionEntry::where('status', 'pending')
                ->where('period_month', $periodMonth)
                ->distinct()
                ->pluck('agent_id');

            foreach ($agentIds as $agentId) {
                $agent = Agent::find($agentId);
                if (!$agent) continue;

                $entries = CommissionEntry::where('agent_id', $agentId)
                    ->where('status', 'pending')
                    ->where('period_month', $periodMonth)
                    ->lockForUpdate()
                    ->get();

                $total = $entries->sum('amount_cents');

                $liquidation = CommissionLiquidation::create([
                    'agent_id'          => $agentId,
                    'period_month'      => $periodMonth,
                    'total_amount_cents'=> $total,
                    'status'            => 'draft',
                    'iban'              => $agent->iban,
                ]);

                $entries->each->update([
                    'status'         => 'approved',
                    'liquidation_id' => $liquidation->id,
                ]);

                $created++;
                $totalMoney = $totalMoney->plus(Money::ofMinor($total, 'EUR'));
            }
        });

        return ['liquidations_created' => $created, 'total_amount' => $totalMoney];
    }

    /**
     * Approves a draft liquidation.
     */
    public function approveLiquidation(CommissionLiquidation $liquidation, object $approvedBy): void
    {
        $liquidation->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);

        event(new LiquidationApproved($liquidation));
    }

    /**
     * Marks liquidation as paid (after bank transfer).
     */
    public function markAsPaid(CommissionLiquidation $liquidation): void
    {
        $liquidation->update(['status' => 'paid', 'paid_at' => now()]);
        $liquidation->entries()->update(['status' => 'paid']);
    }
}

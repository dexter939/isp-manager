<?php

namespace Modules\Contracts\AgentNetwork\Services;

use Brick\Money\Money;
use Modules\Contracts\AgentNetwork\Events\CommissionCalculated;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionEntry;
use Modules\Contracts\AgentNetwork\Models\CommissionRule;

class CommissionCalculator
{
    /**
     * Calculates commission for a contract + invoice using brick/money.
     * Rule resolution: agent+offer > agent-only > global+offer > global default.
     */
    public function calculate(Agent $agent, object $contract, Money $invoiceAmount): Money
    {
        $rule = $this->resolveRule($agent, $contract);

        if ($rule->rate_type === 'fixed') {
            return Money::ofMinor($rule->rate_value_cents ?? 0, 'EUR');
        }

        $rate = (float) ($rule->rate_percentage ?? $agent->commission_rate ?? config('agent_network.default_commission_rate', 10));

        return $invoiceAmount->multipliedBy($rate / 100, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Resolves the applicable CommissionRule for agent + contract.
     * Priority: agent-specific+offer-specific > agent-specific > global+offer-specific > global default.
     */
    public function resolveRule(Agent $agent, object $contract): CommissionRule
    {
        $offerType = $contract->offer_type ?? null;

        // 1. Agent-specific + offer-specific
        $rule = CommissionRule::where('agent_id', $agent->id)
            ->where('offer_type', $offerType)
            ->where('active', true)
            ->orderByDesc('priority')
            ->first();

        if ($rule) return $rule;

        // 2. Agent-specific (any offer)
        $rule = CommissionRule::where('agent_id', $agent->id)
            ->whereNull('offer_type')
            ->where('active', true)
            ->orderByDesc('priority')
            ->first();

        if ($rule) return $rule;

        // 3. Global + offer-specific
        $rule = CommissionRule::whereNull('agent_id')
            ->where('offer_type', $offerType)
            ->where('active', true)
            ->orderByDesc('priority')
            ->first();

        if ($rule) return $rule;

        // 4. Global default
        $rule = CommissionRule::whereNull('agent_id')
            ->whereNull('offer_type')
            ->where('active', true)
            ->orderByDesc('priority')
            ->first();

        if ($rule) return $rule;

        // Fallback: create virtual rule with agent's default rate
        $virtual = new CommissionRule([
            'rate_type'        => 'percentage',
            'rate_percentage'  => $agent->commission_rate,
        ]);
        return $virtual;
    }

    /**
     * Creates a CommissionEntry for a contract + invoice event.
     */
    public function createEntry(Agent $agent, object $contract, object $invoice): CommissionEntry
    {
        $invoiceAmount = Money::ofMinor($invoice->total_cents ?? 0, 'EUR');
        $commission    = $this->calculate($agent, $contract, $invoiceAmount);
        $rule          = $this->resolveRule($agent, $contract);

        $entry = CommissionEntry::create([
            'agent_id'     => $agent->id,
            'contract_id'  => $contract->id,
            'invoice_id'   => $invoice->id,
            'rule_id'      => $rule->id ?? null,
            'amount_cents' => $commission->getMinorAmount()->toInt(),
            'status'       => 'pending',
            'period_month' => now()->startOfMonth()->toDateString(),
        ]);

        event(new CommissionCalculated($entry));

        return $entry;
    }
}

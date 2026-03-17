<?php
namespace Modules\Billing\PaymentMatching\Services;
use Modules\Billing\PaymentMatching\Models\PaymentMatchingRule;
use Modules\Billing\PaymentMatching\Models\PaymentMatchingLog;
class MatchResult {
    public function __construct(
        public readonly bool $matched,
        public readonly ?string $ruleId,
        public readonly ?string $invoiceId,
        public readonly string $actionTaken,
        public readonly array $evaluationDetails = [],
    ) {}
}
class SimulationResult {
    public function __construct(
        public readonly bool $wouldMatch,
        public readonly ?string $ruleId,
        public readonly ?string $ruleName,
        public readonly ?string $invoiceId,
        public readonly string $actionTaken,
        public readonly array $evaluationDetails = [],
    ) {}
}
class PaymentMatchingEngine {
    public function match(array $payment): MatchResult {
        $rules = PaymentMatchingRule::active()->get();
        $evaluationDetails = [];
        foreach ($rules as $rule) {
            $criteriaResult = $this->evaluateCriteria($rule->criteria, $payment);
            $evaluationDetails[] = ['rule_id' => $rule->id, 'rule_name' => $rule->name, 'matched' => $criteriaResult['matched'], 'criteria_results' => $criteriaResult['details']];
            if ($criteriaResult['matched']) {
                $invoiceId = $this->resolveInvoice($rule->action->value, $payment);
                $actionTaken = $rule->action->value . ($invoiceId ? ':' . $invoiceId : '');
                PaymentMatchingLog::create(['payment_id' => $payment['id'], 'rule_id' => $rule->id, 'matched' => true, 'invoice_id' => $invoiceId, 'action_taken' => $actionTaken, 'evaluation_details' => $evaluationDetails]);
                return new MatchResult(true, $rule->id, $invoiceId, $actionTaken, $evaluationDetails);
            }
        }
        PaymentMatchingLog::create(['payment_id' => $payment['id'], 'rule_id' => null, 'matched' => false, 'invoice_id' => null, 'action_taken' => 'no_match', 'evaluation_details' => $evaluationDetails]);
        return new MatchResult(false, null, null, 'no_match', $evaluationDetails);
    }
    public function simulate(array $paymentData): SimulationResult {
        $rules = PaymentMatchingRule::active()->get();
        $evaluationDetails = [];
        foreach ($rules as $rule) {
            $criteriaResult = $this->evaluateCriteria($rule->criteria, $paymentData);
            $evaluationDetails[] = ['rule_id' => $rule->id, 'rule_name' => $rule->name, 'matched' => $criteriaResult['matched'], 'criteria_results' => $criteriaResult['details']];
            if ($criteriaResult['matched']) {
                $invoiceId = $this->resolveInvoice($rule->action->value, $paymentData, simulate: true);
                return new SimulationResult(true, $rule->id, $rule->name, $invoiceId, $rule->action->value, $evaluationDetails);
            }
        }
        return new SimulationResult(false, null, null, null, 'no_match', $evaluationDetails);
    }
    private function evaluateCriteria(array $criteria, array $payment): array {
        $details = [];
        foreach ($criteria as $criterion) {
            $paymentValue = $this->getPaymentField($payment, $criterion['field']);
            $compareValue = $this->getCompareValue($payment, $criterion['match_against'], $criterion['value'] ?? null);
            $result = $this->applyOperator($paymentValue, $criterion['operator'], $compareValue);
            $details[] = ['field' => $criterion['field'], 'operator' => $criterion['operator'], 'match_against' => $criterion['match_against'], 'result' => $result];
            if (!$result) return ['matched' => false, 'details' => $details];
        }
        return ['matched' => true, 'details' => $details];
    }
    private function getPaymentField(array $payment, string $field): mixed {
        return match($field) {
            'variable_symbol' => $payment['variable_symbol'] ?? null,
            'specific_symbol' => $payment['specific_symbol'] ?? null,
            'payment_note'    => $payment['note'] ?? null,
            'iban'            => $payment['debtor_iban'] ?? null,
            'amount'          => $payment['amount_cents'] ?? null,
            default           => null,
        };
    }
    private function getCompareValue(array $payment, string $matchAgainst, ?string $literal): mixed {
        return match($matchAgainst) {
            'invoice_number'             => $payment['variable_symbol'] ?? null,
            'customer_number'            => $payment['customer_number'] ?? null,
            'assigned_variable_symbol'   => $payment['assigned_variable_symbol'] ?? null,
            'literal'                    => $literal,
            default                      => $literal,
        };
    }
    private function applyOperator(mixed $a, string $operator, mixed $b): bool {
        if ($a === null) return false;
        return match($operator) {
            'equals'       => (string)$a === (string)$b,
            'contains'     => str_contains((string)$a, (string)$b),
            'starts_with'  => str_starts_with((string)$a, (string)$b),
            'greater_than' => (float)$a > (float)$b,
            'less_than'    => (float)$a < (float)$b,
            default        => false,
        };
    }
    private function resolveInvoice(string $action, array $payment, bool $simulate = false): ?string {
        if ($action === 'add_to_credit' || $action === 'skip') return null;
        // In real implementation this would query invoices table
        return $payment['invoice_id'] ?? null;
    }
}

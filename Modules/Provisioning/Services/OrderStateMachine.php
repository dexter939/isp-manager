<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services;

use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Events\OrderStateChanged;
use Modules\Provisioning\Models\CarrierOrder;

/**
 * Macchina a stati degli ordini carrier.
 *
 * Transizioni valide mappate su codici stato OF/FC (SPECINT v2.0/2.3).
 * Ad ogni transizione:
 * - Aggiorna carrier_orders.state
 * - Crea record in activity_log (via LogsActivity)
 * - Dispatch evento OrderStateChanged
 */
class OrderStateMachine
{
    /**
     * Processa il risultato di un webhook carrier e aggiorna lo stato dell'ordine.
     * Chiamato da ProcessCarrierWebhookJob.
     */
    public function processWebhookResult(CarrierOrder $order, WebhookResult $result): void
    {
        if ($result->newState === null) {
            Log::info("OrderStateMachine: webhook {$result->messageType} per ordine #{$order->id} senza cambio stato.");
            return;
        }

        $this->transition($order, $result->newState, context: [
            'message_type'         => $result->messageType,
            'codice_ordine_of'     => $result->codiceOrdineOf,
            'cvlan'                => $result->cvlan,
            'gpon_attestazione'    => $result->gponAttestazione,
            'id_apparato'          => $result->idApparatoConsegnato,
            'scheduled_date'       => $result->scheduledDate,
            'flag_desospensione'   => $result->flagDesospensione,
        ]);

        // Aggiorna campi tecnici ricevuti dal webhook
        $updates = [];
        if ($result->codiceOrdineOf && !$order->codice_ordine_of) {
            $updates['codice_ordine_of'] = $result->codiceOrdineOf;
        }
        if ($result->cvlan) {
            $updates['cvlan'] = $result->cvlan;
        }
        if ($result->gponAttestazione) {
            $updates['gpon_attestazione'] = substr($result->gponAttestazione, 0, 30); // max 30 char spec
        }
        if ($result->idApparatoConsegnato) {
            $updates['id_apparato_consegnato'] = $result->idApparatoConsegnato;
        }
        if ($result->scheduledDate) {
            $updates['scheduled_date'] = $result->scheduledDate;
        }
        if ($result->newState === OrderState::Completed) {
            $updates['completed_at'] = now();
        }

        if ($updates) {
            $order->update($updates);
        }
    }

    /**
     * Transizione esplicita (usata da Job, Controller, DunningService).
     *
     * @throws \LogicException se la transizione non è valida
     */
    public function transition(CarrierOrder $order, OrderState $to, array $context = []): void
    {
        $from = $order->state;

        if (!$from->canTransitionTo($to)) {
            throw new \LogicException(
                "OrderStateMachine: transizione non valida {$from->value} → {$to->value} per ordine #{$order->id}"
            );
        }

        $order->update(['state' => $to->value]);

        Log::info("Ordine #{$order->id} {$from->value} → {$to->value}", $context);

        event(new OrderStateChanged(
            order: $order,
            from: $from,
            to: $to,
            context: $context,
        ));
    }

    /**
     * Marca l'ordine come inviato al carrier.
     * Chiamato da SendActivationOrderJob dopo invio con successo.
     */
    public function markSent(CarrierOrder $order): void
    {
        $this->transition($order, OrderState::Sent);
        $order->update(['sent_at' => now()]);
    }

    /**
     * Gestisce un errore di invio (NACK o timeout).
     * Schedula il prossimo retry secondo policy: 0s → 5min → 30min → RetryFailed.
     */
    public function handleSendFailure(CarrierOrder $order, string $errorMessage): void
    {
        $order->update(['last_error' => $errorMessage]);

        if ($order->retry_count >= 3) {
            $this->transition($order, OrderState::RetryFailed, ['error' => $errorMessage]);
            return;
        }

        // Non cambia stato — rimane Ko/Draft; schedula retry
        $order->scheduleRetry();

        Log::warning("Ordine #{$order->id} errore #{$order->retry_count}: {$errorMessage}. Prossimo retry: {$order->fresh()->next_retry_at}");
    }

    /**
     * Cancella un ordine (da qualsiasi stato non finale).
     */
    public function cancel(CarrierOrder $order, string $reason = ''): void
    {
        $this->transition($order, OrderState::Cancelled, ['reason' => $reason]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Provisioning\Contracts;

use Illuminate\Http\Request;
use Modules\Provisioning\Data\CarrierResponse;
use Modules\Provisioning\Data\LineStatusResult;
use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Models\CarrierOrder;

interface CarrierInterface
{
    /**
     * Invia ordine di attivazione al carrier.
     * OF: OLO_ActivationSetup_OpenStream (SOAP)
     * FC: REST POST /orders/activation
     */
    public function sendActivationOrder(CarrierOrder $order): CarrierResponse;

    /**
     * Invia ordine di variazione servizio attivo.
     * OF: OLO_ChangeSetup_OpenStream (SOAP)
     */
    public function sendChangeOrder(CarrierOrder $order): CarrierResponse;

    /**
     * Invia ordine di cessazione.
     * OF: OLO_DeactivationOrder (SOAP)
     */
    public function sendDeactivationOrder(CarrierOrder $order): CarrierResponse;

    /**
     * Rimodula data appuntamento.
     * OF: OLO_Reschedule (SOAP)
     */
    public function sendReschedule(CarrierOrder $order, \Carbon\Carbon $newDate): CarrierResponse;

    /**
     * Desospenzione linea.
     * OF: OLO_StatusUpdate con FLAG_DESOSPENSIONE
     */
    public function sendUnsuspend(CarrierOrder $order): CarrierResponse;

    /**
     * Verifica stato linea attiva (line test).
     * OF: GET /linetesting (REST) — consuma quota
     * FC: statusZpoint / resourceStatusApoint
     *
     * NOTA: chiamare SOLO tramite CarrierGateway che gestisce quota e cache.
     */
    public function checkLineStatus(string $resourceId): LineStatusResult;

    /**
     * Apre trouble ticket assurance al carrier.
     * OF: OLO_TicketRequest (SOAP)
     */
    public function openTroubleTicket(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): CarrierResponse;

    /**
     * Aggiorna un trouble ticket esistente.
     * OF: OLO_TicketUpdate (SOAP)
     */
    public function updateTroubleTicket(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): CarrierResponse;

    /**
     * Chiude un trouble ticket.
     * OF: OLO_TicketCompletion (SOAP)
     */
    public function closeTroubleTicket(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): CarrierResponse;

    /**
     * Processa webhook inbound da carrier.
     * Parsifica XML, valida schema, ritorna stato ordine aggiornato.
     */
    public function handleInboundWebhook(Request $request): WebhookResult;
}

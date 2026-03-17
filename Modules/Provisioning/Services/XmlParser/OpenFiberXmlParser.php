<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services\XmlParser;

use Modules\Provisioning\Data\WebhookResult;
use Modules\Provisioning\Enums\OrderState;

/**
 * Parsifica messaggi XML inbound da Open Fiber.
 *
 * Messaggi gestiti (SPECINT v2.0/2.3):
 * - OF_StatusUpdate
 * - OF_CompletionOrder_OpenStream
 * - OF_Reschedule
 * - OF_TicketUpdate
 * - OF_InfoUpdate
 */
class OpenFiberXmlParser
{
    /**
     * Parsifica il body XML del webhook e ritorna WebhookResult.
     * Il body è già stato estratto dall'envelope SOAP.
     *
     * @throws \InvalidArgumentException se XML malformato
     */
    public function parse(string $xmlBody): WebhookResult
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = implode('; ', array_map(fn($e) => $e->message, libxml_get_errors()));
            libxml_clear_errors();
            throw new \InvalidArgumentException("XML OF malformato: {$errors}");
        }

        $rootName = $xml->getName();

        return match (true) {
            str_contains($rootName, 'StatusUpdate')       => $this->parseStatusUpdate($xml),
            str_contains($rootName, 'CompletionOrder')    => $this->parseCompletionOrder($xml),
            str_contains($rootName, 'Reschedule')         => $this->parseReschedule($xml),
            str_contains($rootName, 'TicketUpdate')       => $this->parseTicketUpdate($xml),
            str_contains($rootName, 'InfoUpdate')         => $this->parseInfoUpdate($xml),
            default => new WebhookResult(
                parsed: false,
                messageType: $rootName,
                codiceOrdineOlo: null,
                codiceOrdineOf: null,
                newState: null,
                scheduledDate: null,
                cvlan: null,
                gponAttestazione: null,
                idApparatoConsegnato: null,
                flagDesospensione: null,
                errorMessage: "Tipo messaggio non gestito: {$rootName}",
            ),
        };
    }

    /**
     * OF_StatusUpdate — cambio stato ordine.
     * STATO: 0=Acquisito, 1=AcquisitoKO, 2=Pianificato,
     *        3=Annullato, 4=Sospeso, 5=Espletato, 6=EspletataKO,
     *        7=Rimodulato, 8=ModificatoOK, 9=ModificatoKO
     */
    private function parseStatusUpdate(\SimpleXMLElement $xml): WebhookResult
    {
        $stato       = (string) ($xml->STATO_ORDINE ?? $xml->STATO ?? '');
        $codiceOlo   = (string) ($xml->CODICE_ORDINE_OLO ?? '');
        $codiceOf    = (string) ($xml->CODICE_ORDINE_OF ?? '');
        $cvlan       = (string) ($xml->VLAN ?? $xml->CVLAN ?? '');
        $gpon        = substr((string) ($xml->GPON_DI_ATTESTAZIONE ?? ''), 0, 30); // max 30 char spec OF
        $dataAppunto = (string) ($xml->DATA_APPUNTAMENTO ?? '');
        $desospen    = (string) ($xml->FLAG_DESOSPENSIONE ?? '');

        $newState = null;
        try {
            $newState = OrderState::fromOfStatusCode($stato);
        } catch (\UnexpectedValueException) {
            // stato sconosciuto: log ma non bloccare
        }

        return new WebhookResult(
            parsed: true,
            messageType: 'OF_StatusUpdate',
            codiceOrdineOlo: $codiceOlo ?: null,
            codiceOrdineOf: $codiceOf ?: null,
            newState: $newState,
            scheduledDate: $dataAppunto ?: null,
            cvlan: $cvlan ?: null,
            gponAttestazione: $gpon ?: null,
            idApparatoConsegnato: null,
            flagDesospensione: $desospen ?: null,
            rawFields: json_decode(json_encode($xml), true) ?? [],
        );
    }

    /**
     * OF_CompletionOrder_OpenStream — completion finale.
     * STATO_ORDINE: "0"=Espletato, "1"=EspletataKO
     * Causali: B05, C14, C15
     */
    private function parseCompletionOrder(\SimpleXMLElement $xml): WebhookResult
    {
        $stato               = (string) ($xml->STATO_ORDINE ?? '');
        $codiceOlo           = (string) ($xml->CODICE_ORDINE_OLO ?? '');
        $codiceOf            = (string) ($xml->CODICE_ORDINE_OF ?? '');
        $idApparato          = (string) ($xml->ID_APPARATO_CONSEGNATO ?? '');
        $gpon                = substr((string) ($xml->GPON_DI_ATTESTAZIONE ?? ''), 0, 30);

        $newState = match ($stato) {
            '0' => OrderState::Completed,
            '1' => OrderState::Ko,
            default => null,
        };

        return new WebhookResult(
            parsed: true,
            messageType: 'OF_CompletionOrder_OpenStream',
            codiceOrdineOlo: $codiceOlo ?: null,
            codiceOrdineOf: $codiceOf ?: null,
            newState: $newState,
            scheduledDate: null,
            cvlan: null,
            gponAttestazione: $gpon ?: null,
            idApparatoConsegnato: $idApparato ?: null,
            flagDesospensione: null,
            rawFields: json_decode(json_encode($xml), true) ?? [],
        );
    }

    /**
     * OF_Reschedule — rimodulazione data da OF.
     */
    private function parseReschedule(\SimpleXMLElement $xml): WebhookResult
    {
        return new WebhookResult(
            parsed: true,
            messageType: 'OF_Reschedule',
            codiceOrdineOlo: (string) ($xml->CODICE_ORDINE_OLO ?? '') ?: null,
            codiceOrdineOf: (string) ($xml->CODICE_ORDINE_OF ?? '') ?: null,
            newState: OrderState::Scheduled,
            scheduledDate: (string) ($xml->NUOVA_DATA ?? '') ?: null,
            cvlan: null,
            gponAttestazione: null,
            idApparatoConsegnato: null,
            flagDesospensione: null,
            rawFields: json_decode(json_encode($xml), true) ?? [],
        );
    }

    /**
     * OF_TicketUpdate — aggiornamento ticket da OF.
     * CAUSA_GUASTO: "01"=Causa Open Fiber
     * DESC_TECNICA_GUASTO: "10"=Sostituzione Apparati CLI
     */
    private function parseTicketUpdate(\SimpleXMLElement $xml): WebhookResult
    {
        return new WebhookResult(
            parsed: true,
            messageType: 'OF_TicketUpdate',
            codiceOrdineOlo: (string) ($xml->CODICE_ORDINE_OLO ?? '') ?: null,
            codiceOrdineOf: (string) ($xml->CODICE_ORDINE_OF ?? '') ?: null,
            newState: null,
            scheduledDate: null,
            cvlan: null,
            gponAttestazione: null,
            idApparatoConsegnato: null,
            flagDesospensione: null,
            rawFields: json_decode(json_encode($xml), true) ?? [],
        );
    }

    private function parseInfoUpdate(\SimpleXMLElement $xml): WebhookResult
    {
        return new WebhookResult(
            parsed: true,
            messageType: 'OF_InfoUpdate',
            codiceOrdineOlo: (string) ($xml->CODICE_ORDINE_OLO ?? '') ?: null,
            codiceOrdineOf: (string) ($xml->CODICE_ORDINE_OF ?? '') ?: null,
            newState: null,
            scheduledDate: null,
            cvlan: null,
            gponAttestazione: null,
            idApparatoConsegnato: null,
            flagDesospensione: null,
            rawFields: json_decode(json_encode($xml), true) ?? [],
        );
    }
}

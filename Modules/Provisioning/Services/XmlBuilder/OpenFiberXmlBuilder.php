<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services\XmlBuilder;

use Modules\Provisioning\Models\CarrierOrder;

/**
 * Costruisce messaggi XML per Open Fiber (SPECINT v2.0/2.3).
 * Tutti i messaggi SOAP seguono lo stesso envelope base.
 */
class OpenFiberXmlBuilder
{
    private string $codiceOperatore;
    private string $soapEndpoint;

    public function __construct()
    {
        $this->codiceOperatore = (string) config('provisioning.openfiber.codice_operatore', '');
        $this->soapEndpoint    = (string) config('provisioning.openfiber.soap_endpoint', '');
    }

    /**
     * OLO_ActivationSetup_OpenStream
     * Ordine attivazione FTTH.
     */
    public function buildActivationSetup(CarrierOrder $order): string
    {
        $addr = $order->contract->indirizzo_installazione;

        return $this->wrapSoap('OLO_ActivationSetup_OpenStream', [
            'CODICE_ORDINE_OLO'         => $order->codice_ordine_olo,
            'CODICE_OPERATORE'           => $this->codiceOperatore,
            'ID_BUILDING'               => $order->contract->id_building ?? '',
            'CVLAN'                     => $order->cvlan ?? '',
            'INDIRIZZO'                 => ($addr['via'] ?? '') . ' ' . ($addr['civico'] ?? ''),
            'COMUNE'                    => $addr['comune'] ?? '',
            'PROVINCIA'                 => $addr['provincia'] ?? '',
            'CAP'                       => $addr['cap'] ?? '',
            'NOME_COGNOME_CLIENTE'      => $order->contract->customer->full_name,
            'RECAPITO_TEL_CLIENTE_1'    => $order->contract->customer->cellulare ?? '',
            'EMAIL_CLIENTE'             => $order->contract->customer->email ?? '',
            'TECNOLOGIA'                => $order->contract->servicePlan->technology ?? 'FTTH',
            'ORDINE_PROCACCIATO'        => '0', // 0 = non procacciato
        ]);
    }

    /**
     * OLO_ChangeSetup_OpenStream
     * Variazione su servizio attivo.
     */
    public function buildChangeSetup(CarrierOrder $order): string
    {
        return $this->wrapSoap('OLO_ChangeSetup_OpenStream', [
            'CODICE_ORDINE_OLO'      => $order->codice_ordine_olo,
            'CODICE_ORDINE_OF'       => $order->codice_ordine_of ?? '',
            'CODICE_OPERATORE'        => $this->codiceOperatore,
            'TIPO_MODIFICA'          => 'VARIAZIONE_SERVIZIO',
        ]);
    }

    /**
     * OLO_DeactivationOrder
     * Cessazione servizio.
     */
    public function buildDeactivation(CarrierOrder $order): string
    {
        return $this->wrapSoap('OLO_DeactivationOrder', [
            'CODICE_ORDINE_OLO' => $order->codice_ordine_olo,
            'CODICE_ORDINE_OF'  => $order->codice_ordine_of ?? '',
            'CODICE_OPERATORE'   => $this->codiceOperatore,
            'MOTIVO_CESSAZIONE' => 'RECESSO_CLIENTE',
        ]);
    }

    /**
     * OLO_Reschedule
     * Rimodulazione data appuntamento.
     */
    public function buildReschedule(CarrierOrder $order, \Carbon\Carbon $newDate): string
    {
        return $this->wrapSoap('OLO_Reschedule', [
            'CODICE_ORDINE_OLO'  => $order->codice_ordine_olo,
            'CODICE_ORDINE_OF'   => $order->codice_ordine_of ?? '',
            'CODICE_OPERATORE'    => $this->codiceOperatore,
            'NUOVA_DATA'         => $newDate->format('Y-m-d'),
        ]);
    }

    /**
     * OLO_StatusUpdate (desospensione)
     * FLAG_DESOSPENSIONE = 1.
     */
    public function buildUnsuspend(CarrierOrder $order): string
    {
        return $this->wrapSoap('OLO_StatusUpdate', [
            'CODICE_ORDINE_OLO'   => $order->codice_ordine_olo,
            'CODICE_ORDINE_OF'    => $order->codice_ordine_of ?? '',
            'CODICE_OPERATORE'     => $this->codiceOperatore,
            'FLAG_DESOSPENSIONE'  => '1',
        ]);
    }

    /**
     * OLO_TicketRequest
     * Apertura ticket assurance.
     * RECAPITO_TELEFONICO_CLIENTE_1 SEMPRE obbligatorio.
     */
    public function buildTicketRequest(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): string
    {
        return $this->wrapSoap('OLO_TicketRequest', [
            'CODICE_ORDINE_OLO'             => $ticket->codiceOrdineOlo,
            'CODICE_ORDINE_OF'              => $ticket->codiceOrdineOf,
            'CODICE_OPERATORE'               => $this->codiceOperatore,
            'RECAPITO_TELEFONICO_CLIENTE_1' => $ticket->recapitoTelefonicoCliente,
            'CAUSA_GUASTO'                  => $ticket->causaGuasto,
            'DESC_TECNICA_GUASTO'           => $ticket->descTecnicaGuasto,
            'NOTE_OLO'                      => $ticket->noteAgente ?? '',
        ]);
    }

    /**
     * OLO_TicketUpdate
     */
    public function buildTicketUpdate(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): string
    {
        return $this->wrapSoap('OLO_TicketUpdate', [
            'CODICE_ORDINE_OLO' => $ticket->codiceOrdineOlo,
            'CODICE_ORDINE_OF'  => $ticket->codiceOrdineOf,
            'TICKET_ID'         => $ticket->ticketId ?? '',
            'CODICE_OPERATORE'   => $this->codiceOperatore,
            'CAUSA_GUASTO'      => $ticket->causaGuasto,
            'DESC_TECNICA_GUASTO' => $ticket->descTecnicaGuasto,
        ]);
    }

    /**
     * OLO_TicketCompletion
     */
    public function buildTicketCompletion(\Modules\Provisioning\Data\TroubleTicketRequest $ticket): string
    {
        return $this->wrapSoap('OLO_TicketCompletion', [
            'CODICE_ORDINE_OLO' => $ticket->codiceOrdineOlo,
            'TICKET_ID'         => $ticket->ticketId ?? '',
            'CODICE_OPERATORE'   => $this->codiceOperatore,
            'ESITO_CHIUSURA'    => 'RISOLTO',
        ]);
    }

    // ---- Private helpers ----

    private function wrapSoap(string $messageName, array $fields): string
    {
        $body = '';
        foreach ($fields as $key => $value) {
            $value = htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
            $body .= "        <{$key}>{$value}</{$key}>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:of="http://openfiber.it/olo/{$messageName}">
    <soapenv:Header/>
    <soapenv:Body>
        <of:{$messageName}>
{$body}        </of:{$messageName}>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }
}

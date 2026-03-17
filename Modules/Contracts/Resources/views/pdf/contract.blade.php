<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #222; line-height: 1.4; }
    .page { padding: 20mm 15mm; }
    h1 { font-size: 16pt; color: #1a3c5e; border-bottom: 2px solid #1a3c5e; padding-bottom: 6px; margin-bottom: 16px; }
    h2 { font-size: 12pt; color: #1a3c5e; margin-top: 18px; margin-bottom: 8px; }
    h3 { font-size: 10pt; color: #444; margin-top: 12px; margin-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table th { background: #1a3c5e; color: white; padding: 5px 8px; text-align: left; font-size: 9pt; }
    table td { padding: 4px 8px; border-bottom: 1px solid #ddd; font-size: 9pt; }
    table tr:nth-child(even) td { background: #f5f8fb; }
    .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
    .company-name { font-size: 14pt; font-weight: bold; color: #1a3c5e; }
    .contract-number { font-size: 10pt; color: #666; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 8pt; font-weight: bold; }
    .badge-ftth { background: #d4edda; color: #155724; }
    .badge-fttc { background: #d1ecf1; color: #0c5460; }
    .badge-fwa  { background: #fff3cd; color: #856404; }
    .price-box { border: 1px solid #1a3c5e; padding: 10px 14px; border-radius: 4px; background: #f0f5ff; margin: 12px 0; }
    .price-row { display: flex; justify-content: space-between; padding: 3px 0; }
    .price-total { font-size: 13pt; font-weight: bold; color: #1a3c5e; border-top: 1px solid #1a3c5e; margin-top: 6px; padding-top: 6px; }
    .footer { position: fixed; bottom: 10mm; left: 15mm; right: 15mm; font-size: 7pt; color: #999; border-top: 1px solid #eee; padding-top: 4px; }
    .signature-section { margin-top: 30px; border: 1px solid #ccc; padding: 12px; border-radius: 4px; }
    .signature-box { height: 40px; border-bottom: 1px solid #333; margin-top: 10px; }
    .page-break { page-break-after: always; }
    .highlight { background: #fffbea; padding: 8px; border-left: 3px solid #f0a500; margin: 8px 0; font-size: 9pt; }
</style>
</head>
<body>

<div class="page">

{{-- HEADER --}}
<div class="header">
    <div>
        <div class="company-name">{{ $company['ragione_sociale'] }}</div>
        <div style="font-size:8pt; color:#666;">
            P.IVA {{ $company['piva'] }} — {{ $company['indirizzo'] }}, {{ $company['cap'] }} {{ $company['citta'] }} ({{ $company['provincia'] }})
        </div>
        <div style="font-size:8pt; color:#666;">{{ $company['pec'] }}</div>
    </div>
    <div style="text-align:right;">
        <div class="contract-number">Contratto n. {{ str_pad($contract->id, 8, '0', STR_PAD_LEFT) }}</div>
        <div style="font-size:8pt; color:#666;">{{ $generatedAt->format('d/m/Y') }}</div>
    </div>
</div>

<h1>CONTRATTO DI FORNITURA SERVIZI TLC</h1>

{{-- SEZIONE 1: CLIENTE --}}
<h2>1. Dati Cliente</h2>
<table>
    <tr>
        <th colspan="2">Anagrafica</th>
    </tr>
    <tr>
        <td width="35%"><strong>Tipo</strong></td>
        <td>{{ $customer->type->label() }}</td>
    </tr>
    @if($customer->type->value === 'azienda')
    <tr>
        <td><strong>Ragione Sociale</strong></td>
        <td>{{ $customer->ragione_sociale }}</td>
    </tr>
    <tr>
        <td><strong>Partita IVA</strong></td>
        <td>{{ $customer->piva }}</td>
    </tr>
    @else
    <tr>
        <td><strong>Nominativo</strong></td>
        <td>{{ $customer->full_name }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Codice Fiscale</strong></td>
        <td>{{ $customer->codice_fiscale }}</td>
    </tr>
    <tr>
        <td><strong>Email</strong></td>
        <td>{{ $customer->email }}</td>
    </tr>
    @if($customer->pec)
    <tr>
        <td><strong>PEC</strong></td>
        <td>{{ $customer->pec }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Cellulare</strong></td>
        <td>{{ $customer->cellulare }}</td>
    </tr>
    <tr>
        <td><strong>Metodo di pagamento</strong></td>
        <td>{{ $customer->payment_method->label() }}</td>
    </tr>
</table>

{{-- SEZIONE 2: INDIRIZZO INSTALLAZIONE --}}
<h2>2. Indirizzo di Installazione</h2>
@php $addr = $contract->indirizzo_installazione; @endphp
<table>
    <tr>
        <td width="35%"><strong>Indirizzo</strong></td>
        <td>{{ $addr['via'] }} {{ $addr['civico'] }}{{ isset($addr['scala']) ? ', Sc. '.$addr['scala'] : '' }}{{ isset($addr['piano']) ? ', P. '.$addr['piano'] : '' }}{{ isset($addr['interno']) ? ', Int. '.$addr['interno'] : '' }}</td>
    </tr>
    <tr>
        <td><strong>Comune</strong></td>
        <td>{{ $addr['comune'] }} ({{ $addr['provincia'] }}) — {{ $addr['cap'] }}</td>
    </tr>
    @if($contract->codice_ui)
    <tr>
        <td><strong>Codice UI FiberCop</strong></td>
        <td>{{ $contract->codice_ui }}</td>
    </tr>
    @endif
    @if($contract->id_building)
    <tr>
        <td><strong>ID Building Open Fiber</strong></td>
        <td>{{ $contract->id_building }}</td>
    </tr>
    @endif
</table>

{{-- SEZIONE 3: OFFERTA --}}
<h2>3. Offerta Commerciale</h2>
<table>
    <tr>
        <td width="35%"><strong>Piano</strong></td>
        <td>
            {{ $servicePlan->name }}
            <span class="badge badge-{{ strtolower($servicePlan->technology) }}">{{ $servicePlan->technology }}</span>
        </td>
    </tr>
    <tr>
        <td><strong>Carrier</strong></td>
        <td>{{ $servicePlan->carrier->label() }}</td>
    </tr>
    <tr>
        <td><strong>Velocità</strong></td>
        <td>{{ $servicePlan->bandwidth_dl }} Mbps download / {{ $servicePlan->bandwidth_ul }} Mbps upload</td>
    </tr>
    <tr>
        <td><strong>Ciclo fatturazione</strong></td>
        <td>{{ $contract->billing_cycle->label() }}, giorno {{ $contract->billing_day }}</td>
    </tr>
    <tr>
        <td><strong>Durata minima</strong></td>
        <td>{{ $servicePlan->min_contract_months }} mesi
            @if($contract->min_end_date)
            (fino al {{ \Carbon\Carbon::parse($contract->min_end_date)->format('d/m/Y') }})
            @endif
        </td>
    </tr>
</table>

<div class="price-box">
    <h3 style="margin-top:0;">Riepilogo Economico (IVA esclusa)</h3>
    <div class="price-row">
        <span>Canone mensile</span>
        <span>€ {{ number_format($contract->monthly_price, 2, ',', '.') }}</span>
    </div>
    @if($contract->activation_fee > 0)
    <div class="price-row">
        <span>Costo attivazione (una tantum)</span>
        <span>€ {{ number_format($contract->activation_fee, 2, ',', '.') }}</span>
    </div>
    @endif
    @if($contract->modem_fee > 0)
    <div class="price-row">
        <span>Contributo modem/ONT</span>
        <span>€ {{ number_format($contract->modem_fee, 2, ',', '.') }}</span>
    </div>
    @endif
    <div class="price-row price-total">
        <span>Canone mensile + IVA 22%</span>
        <span>€ {{ number_format($contract->monthly_price * 1.22, 2, ',', '.') }}</span>
    </div>
</div>

{{-- SEZIONE 4: CONDIZIONI --}}
<h2>4. Condizioni Generali di Contratto</h2>
<p style="font-size:8pt; color:#444;">
Il presente contratto è regolato dalle Condizioni Generali di Fornitura disponibili sul sito dell'operatore e dall'Allegato Tecnico specifico per la tecnologia scelta. Il Cliente dichiara di aver preso visione e di accettare integralmente le condizioni contrattuali, l'Informativa Privacy ai sensi del GDPR (Reg. UE 2016/679) e i diritti di recesso previsti dal Codice del Consumo (D.Lgs. 206/2005).
</p>

<div class="highlight">
    <strong>Diritto di recesso:</strong> Il Cliente consumatore può esercitare il diritto di recesso entro 14 giorni dalla stipula del contratto inviando comunicazione scritta a {{ $company['pec'] }}.
</div>

{{-- SEZIONE 5: FIRMA --}}
@if($signed)
<h2>5. Firma Elettronica Avanzata (FEA)</h2>
<div class="signature-section">
    <p style="font-size:9pt;">Il presente contratto è stato firmato elettronicamente ai sensi dell'art. 26 del Regolamento eIDAS (UE) 910/2014 tramite OTP inviato al dispositivo registrato del Cliente.</p>
    <table style="margin-top:8px;">
        <tr>
            <td width="35%"><strong>Data e ora firma</strong></td>
            <td>{{ $signedAt->format('d/m/Y H:i:s') }} (UTC+1)</td>
        </tr>
        <tr>
            <td><strong>IP del firmatario</strong></td>
            <td>{{ $signerIp }}</td>
        </tr>
        <tr>
            <td><strong>Hash documento (SHA-256)</strong></td>
            <td style="font-size:7pt; font-family:monospace;">{{ $contract->pdf_hash_sha256 }}</td>
        </tr>
    </table>
    <p style="margin-top:8px; font-size:8pt; color:#555;">
        <strong>DOCUMENTO FIRMATO DIGITALMENTE</strong> — La firma è verificabile tramite il codice hash SHA-256 sopra riportato.
    </p>
</div>
@else
<h2>5. Firma</h2>
<div class="signature-section">
    <p style="font-size:9pt; margin-bottom:8px;">Il/La sottoscritto/a dichiara di aver letto, compreso e accettato integralmente le condizioni sopra riportate.</p>
    <table>
        <tr>
            <td width="50%" style="padding-right:20px;">
                <p style="font-size:8pt; color:#666;">Data e luogo</p>
                <div class="signature-box"></div>
            </td>
            <td width="50%">
                <p style="font-size:8pt; color:#666;">Firma del Cliente</p>
                <div class="signature-box"></div>
            </td>
        </tr>
    </table>
    <p style="margin-top:10px; font-size:8pt; color:#888;">
        La firma del presente contratto avverrà tramite OTP (One Time Password) inviato al numero di cellulare registrato, ai sensi dell'art. 26 del Regolamento eIDAS (FEA).
    </p>
</div>
@endif

</div>

<div class="footer">
    {{ $company['ragione_sociale'] }} — P.IVA {{ $company['piva'] }} | {{ $company['indirizzo'] }}, {{ $company['cap'] }} {{ $company['citta'] }} | {{ $company['pec'] }}
    &nbsp;|&nbsp; Contratto n. {{ str_pad($contract->id, 8, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp; Generato il {{ $generatedAt->format('d/m/Y H:i') }}
</div>

</body>
</html>

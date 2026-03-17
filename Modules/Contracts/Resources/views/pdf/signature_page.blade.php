<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #222; padding: 20mm 15mm; }
    h2 { color: #1a3c5e; border-bottom: 2px solid #1a3c5e; padding-bottom: 6px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table td { padding: 6px 10px; border-bottom: 1px solid #eee; }
    table tr:nth-child(even) td { background: #f8f9fa; }
    .seal { border: 3px solid #1a3c5e; padding: 16px; border-radius: 6px; text-align: center; margin-top: 20px; }
    .seal-title { font-size: 14pt; font-weight: bold; color: #1a3c5e; }
    .hash { font-family: monospace; font-size: 7pt; color: #555; word-break: break-all; background: #f5f5f5; padding: 4px; border-radius: 2px; }
</style>
</head>
<body>

<h2>Registro Firma Elettronica Avanzata (FEA)</h2>

<p style="margin-bottom:12px; font-size:9pt;">
    Il presente documento attesta la firma elettronica del contratto di cui sopra ai sensi dell'art. 26 del Regolamento UE eIDAS n. 910/2014 e delle delibere AgID.
</p>

<table>
    <tr>
        <td width="40%"><strong>Contratto n.</strong></td>
        <td>{{ str_pad($contract->id, 8, '0', STR_PAD_LEFT) }}</td>
    </tr>
    <tr>
        <td><strong>Cliente</strong></td>
        <td>{{ $customer->full_name }}</td>
    </tr>
    <tr>
        <td><strong>Data e ora firma</strong></td>
        <td>{{ $signedAt->format('d/m/Y \a\l\l\e H:i:s') }} (ora locale Europa/Roma)</td>
    </tr>
    <tr>
        <td><strong>Indirizzo IP firmatario</strong></td>
        <td>{{ $signerIp }}</td>
    </tr>
    <tr>
        <td><strong>Metodo autenticazione</strong></td>
        <td>OTP (One Time Password) su dispositivo mobile registrato</td>
    </tr>
    <tr>
        <td><strong>Normativa applicabile</strong></td>
        <td>Art. 26 Reg. UE 910/2014 (eIDAS) — FEA</td>
    </tr>
</table>

<p style="font-size:9pt; margin-bottom:8px;"><strong>Hash SHA-256 documento pre-firma:</strong></p>
<div class="hash">{{ $docHash }}</div>

<div class="seal" style="margin-top:24px;">
    <div class="seal-title">✓ DOCUMENTO FIRMATO ELETTRONICAMENTE</div>
    <p style="font-size:8pt; margin-top:6px; color:#555;">
        La validità di questo documento può essere verificata confrontando l'hash SHA-256 con il documento originale.<br>
        Conservato per 10 anni ai sensi della normativa vigente.
    </p>
</div>

</body>
</html>

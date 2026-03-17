@php
$map = [
    'pending'   => ['label' => 'In attesa',   'color' => 'secondary'],
    'sent'      => ['label' => 'Inviata',      'color' => 'primary'],
    'delivered' => ['label' => 'Consegnata',   'color' => 'warning'],
    'accepted'  => ['label' => 'Accettata',    'color' => 'success'],
    'rejected'  => ['label' => 'Rifiutata',    'color' => 'danger'],
    'error'     => ['label' => 'Errore',       'color' => 'danger'],
];
$s = $map[$status] ?? ['label' => $status, 'color' => 'secondary'];
@endphp
<span class="badge bg-label-{{ $s['color'] }}">{{ $s['label'] }}</span>

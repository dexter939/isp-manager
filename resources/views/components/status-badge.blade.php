@props(['status', 'label' => null])

@php
$val = is_object($status) ? $status->value : (string) $status;
$map = [
    'attivo'         => 'success',
    'active'         => 'success',
    'attiva'         => 'success',
    'sospeso'        => 'warning',
    'suspended'      => 'warning',
    'terminato'      => 'secondary',
    'terminated'     => 'secondary',
    'in_attivazione' => 'info',
    'pagata'         => 'success',
    'paid'           => 'success',
    'scaduta'        => 'danger',
    'overdue'        => 'danger',
    'inviata'        => 'primary',
    'emessa'         => 'primary',
    'annullata'      => 'secondary',
    'draft'          => 'light',
    'aperto'         => 'danger',
    'open'           => 'danger',
    'in_lavorazione' => 'warning',
    'in_progress'    => 'warning',
    'risolto'        => 'success',
    'resolved'       => 'success',
    'chiuso'         => 'secondary',
    'closed'         => 'secondary',
    'pending'        => 'info',
];
$bsColor = $map[$val] ?? 'secondary';
$display = $label
    ?? (is_object($status) && method_exists($status, 'label') ? $status->label() : ucfirst(str_replace('_', ' ', $val)));
@endphp

<span class="badge bg-{{ $bsColor }}">{{ $display }}</span>

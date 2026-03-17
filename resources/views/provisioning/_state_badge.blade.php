@php
$stateMap = [
    'draft'        => ['label' => 'Bozza',          'color' => 'secondary'],
    'sent'         => ['label' => 'Inviato',         'color' => 'primary'],
    'accepted'     => ['label' => 'Acquisito',       'color' => 'info'],
    'scheduled'    => ['label' => 'Pianificato',     'color' => 'indigo'],
    'in_progress'  => ['label' => 'In lavorazione',  'color' => 'warning'],
    'completed'    => ['label' => 'Completato',      'color' => 'success'],
    'ko'           => ['label' => 'KO',              'color' => 'danger'],
    'cancelled'    => ['label' => 'Annullato',       'color' => 'danger'],
    'suspended'    => ['label' => 'Sospeso',         'color' => 'warning'],
    'retry_failed' => ['label' => 'Retry fallito',   'color' => 'danger'],
];
$s = $stateMap[$state] ?? ['label' => $state, 'color' => 'secondary'];
@endphp
<span class="badge bg-label-{{ $s['color'] }}">{{ $s['label'] }}</span>

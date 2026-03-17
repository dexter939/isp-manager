@php
$map = [
    'RC' => ['label' => 'RC – Consegna',          'color' => 'success', 'desc' => 'Ricevuta di Consegna'],
    'MC' => ['label' => 'MC – Mancata cons.',      'color' => 'warning', 'desc' => 'Mancata Consegna'],
    'NS' => ['label' => 'NS – Scarto',             'color' => 'danger',  'desc' => 'Notifica di Scarto'],
    'EC' => ['label' => 'EC – Esito committente',  'color' => 'info',    'desc' => 'Esito Committente'],
    'AT' => ['label' => 'AT – Attestazione',       'color' => 'success', 'desc' => 'Attestazione di Trasmissione'],
    'DT' => ['label' => 'DT – Dec. Termini',       'color' => 'success', 'desc' => 'Decorrenza Termini'],
    'SF' => ['label' => 'SF – Scarto fattura',     'color' => 'danger',  'desc' => 'Scarto Fattura'],
];
$n = $map[$code] ?? ['label' => $code, 'color' => 'secondary', 'desc' => ''];
@endphp
<span class="badge bg-label-{{ $n['color'] }}" title="{{ $n['desc'] }}" data-bs-toggle="tooltip">
  {{ $n['label'] }}
</span>

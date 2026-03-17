@props([
    'icon'  => 'ri-bar-chart-line',
    'color' => 'primary',
    'label' => '',
    'value' => '—',
    'href'  => null,
])

@php
$colorMap = [
    'primary' => ['bg' => 'rgba(105,108,255,.16)', 'icon' => '#696cff'],
    'success' => ['bg' => 'rgba(113,221,55,.16)',  'icon' => '#71dd37'],
    'warning' => ['bg' => 'rgba(255,171,0,.16)',   'icon' => '#ffab00'],
    'danger'  => ['bg' => 'rgba(255,62,29,.16)',   'icon' => '#ff3e1d'],
    'info'    => ['bg' => 'rgba(3,195,236,.16)',    'icon' => '#03c3ec'],
];
$c = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="card kpi-card h-100{{ $href ? ' kpi-card--link' : '' }}">
  @if($href)
    <a href="{{ $href }}" class="stretched-link"></a>
  @endif
  <div class="card-body d-flex align-items-center gap-3">
    <div class="kpi-icon" style="background:{{ $c['bg'] }}">
      <i class="{{ $icon }}" style="color:{{ $c['icon'] }}"></i>
    </div>
    <div>
      <p class="kpi-label">{{ $label }}</p>
      <span class="kpi-value">{{ $value }}</span>
    </div>
  </div>
</div>

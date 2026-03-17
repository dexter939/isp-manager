@extends('layouts.contentNavbarLayout')
@section('title', 'Report Ticket SLA')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reporting.index') }}">Reporting</a></li>
  <li class="breadcrumb-item active">Ticket & SLA</li>
@endsection

@section('page-content')

  <x-page-header title="Report Ticket & SLA">
    <x-slot name="action">
      <form class="d-flex gap-2 align-items-center">
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="30"  @selected($days == 30)>Ultimi 30 giorni</option>
          <option value="90"  @selected($days == 90)>Ultimi 90 giorni</option>
          <option value="180" @selected($days == 180)>Ultimi 180 giorni</option>
        </select>
      </form>
    </x-slot>
  </x-page-header>

  {{-- SLA per priorità --}}
  <div class="row g-3 mb-4">
    @foreach($byPriority as $p)
      @php
        $sla    = $slaByPriority->firstWhere('priority', $p->priority);
        $slaRate = ($sla && $sla->total > 0) ? round($sla->within_sla / $sla->total * 100, 1) : null;
        $color  = match($p->priority) { 'critical' => 'danger', 'high' => 'danger', 'medium' => 'warning', default => 'secondary' };
      @endphp
      <div class="col-6 col-sm-3">
        <div class="card text-center">
          <div class="card-body py-3">
            <div class="small text-muted mb-1">{{ ucfirst($p->priority) }}</div>
            <div class="fs-4 fw-bold">{{ $p->total }}</div>
            <div class="small text-muted">{{ $p->resolved }} risolti</div>
            @if($slaRate !== null)
              <span class="badge bg-{{ $slaRate >= 90 ? 'success' : ($slaRate >= 70 ? 'warning' : 'danger') }} mt-1">
                SLA {{ $slaRate }}%
              </span>
            @endif
            @if($p->avg_hours)
              <div class="small text-muted mt-1">~{{ round($p->avg_hours, 1) }}h media</div>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Trend giornaliero --}}
  <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Trend giornaliero — ultimi {{ $days }} giorni</h6></div>
    <div class="card-body"><div id="chartTrend"></div></div>
  </div>

  <div class="row g-4">

    {{-- Top tecnici --}}
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Top tecnici per ticket assegnati</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tecnico</th>
                  <th class="text-end">Totale</th>
                  <th class="text-end">Risolti</th>
                  <th class="text-end">% Risolti</th>
                  <th class="text-end">Media ore</th>
                </tr>
              </thead>
              <tbody>
                @forelse($byTechnician as $t)
                  <tr>
                    <td class="small fw-semibold">{{ $t->name }}</td>
                    <td class="text-end small">{{ $t->total }}</td>
                    <td class="text-end small text-success">{{ $t->resolved }}</td>
                    <td class="text-end small">
                      @if($t->total > 0)
                        {{ round($t->resolved / $t->total * 100) }}%
                      @else
                        —
                      @endif
                    </td>
                    <td class="text-end small text-muted">
                      {{ $t->avg_hours ? round($t->avg_hours, 1) . 'h' : '—' }}
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3 small">Nessun ticket assegnato nel periodo.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Per tipo --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header"><h6 class="mb-0">Distribuzione per tipo</h6></div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div id="chartType" style="width:100%"></div>
        </div>
      </div>
    </div>

  </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
new ApexCharts(document.getElementById('chartTrend'), {
  chart: { type: 'line', height: 260, toolbar: { show: false } },
  series: [
    { name: 'Aperti', data: @json($trend->pluck('opened')) },
  ],
  xaxis: { categories: @json($trend->pluck('day')), labels: { rotate: -45 } },
  colors: ['#696cff'],
  stroke: { curve: 'smooth', width: 2 },
  dataLabels: { enabled: false },
  yaxis: { labels: { formatter: v => Math.round(v) } },
}).render();

new ApexCharts(document.getElementById('chartType'), {
  chart: { type: 'donut', height: 220 },
  series: @json($byType->values()),
  labels: @json($byType->keys()->map(fn($k) => ucfirst($k ?? 'other'))),
  legend: { position: 'bottom' },
}).render();
</script>
@endpush

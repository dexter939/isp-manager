@extends('layouts.contentNavbarLayout')
@section('title', 'Reporting Provvigioni Agenti')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reporting.index') }}">Analytics</a></li>
  <li class="breadcrumb-item active">Provvigioni agenti</li>
@endsection

@section('page-content')

<x-page-header title="Reportistica Agenti" subtitle="Analisi provvigioni, liquidazioni e rete agenti">
  <form method="GET" class="d-flex gap-2 align-items-center">
    <label class="form-label mb-0 text-muted small">Anno</label>
    <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      @foreach($years as $y)
        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
      @endforeach
    </select>
  </form>
</x-page-header>

{{-- KPI globali --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-user-star-line" color="primary" label="Agenti attivi"
      :value="$activeAgentsCount" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-money-euro-circle-line" color="info" label="Totale provvigioni {{ $year }}"
      :value="'€ ' . number_format(($globalTotals->total_cents ?? 0) / 100, 2, ',', '.')" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-time-line" color="warning" label="In attesa"
      :value="'€ ' . number_format(($globalTotals->pending_cents ?? 0) / 100, 2, ',', '.')" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Liquidate"
      :value="'€ ' . number_format(($globalTotals->paid_cents ?? 0) / 100, 2, ',', '.')" />
  </div>
</div>

<div class="row g-4 mb-4">

  {{-- Trend mensile --}}
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Andamento mensile provvigioni {{ $year }}</h6>
      </div>
      <div class="card-body">
        <div id="chartMonthlyTrend"></div>
      </div>
    </div>
  </div>

  {{-- Liquidazioni per stato --}}
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Liquidazioni per stato</h6>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <div id="chartLiqStatus" style="width:100%"></div>
      </div>
    </div>
  </div>

</div>

<div class="row g-4">

  {{-- Top agenti --}}
  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Top 10 agenti per provvigioni ({{ $year }})</h6>
        <a href="{{ route('admin.agents.index') }}" class="btn btn-sm btn-outline-primary">Tutti gli agenti</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Agente</th>
              <th>Aliquota</th>
              <th>Contratti</th>
              <th>Totale</th>
              <th>In attesa</th>
              <th>Liquidate</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topAgents as $i => $ag)
              <tr>
                <td class="text-muted small">{{ $i + 1 }}</td>
                <td>
                  <a href="{{ route('admin.agents.show', $ag->id) }}" class="fw-semibold text-decoration-none">
                    {{ $ag->business_name }}
                  </a>
                  <br><span class="font-monospace text-muted" style="font-size:.72rem">{{ $ag->code }}</span>
                </td>
                <td><span class="badge bg-label-info">{{ $ag->commission_rate }}%</span></td>
                <td>{{ $ag->contracts_count }}</td>
                <td class="fw-semibold">€ {{ number_format($ag->total_cents / 100, 2, ',', '.') }}</td>
                <td class="text-warning">€ {{ number_format($ag->pending_cents / 100, 2, ',', '.') }}</td>
                <td class="text-success">€ {{ number_format($ag->paid_cents / 100, 2, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">Nessun dato provvigioni per {{ $year }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Ultime liquidazioni --}}
  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Ultime liquidazioni</h6>
        <div class="d-flex gap-2">
          @php
            $draftCount    = $liquidationStats->get('draft')?->cnt    ?? 0;
            $approvedCount = $liquidationStats->get('approved')?->cnt  ?? 0;
            $paidCount     = $liquidationStats->get('paid')?->cnt      ?? 0;
          @endphp
          @if($draftCount)
            <span class="badge bg-label-secondary">{{ $draftCount }} bozze</span>
          @endif
          @if($approvedCount)
            <span class="badge bg-label-warning">{{ $approvedCount }} da pagare</span>
          @endif
          @if($paidCount)
            <span class="badge bg-label-success">{{ $paidCount }} pagate</span>
          @endif
        </div>
      </div>
      <div class="table-responsive" style="max-height:420px;overflow-y:auto">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light sticky-top">
            <tr>
              <th>Agente</th>
              <th>Periodo</th>
              <th>Importo</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($liquidations as $liq)
              @php
                $liqColors = ['draft' => 'secondary', 'approved' => 'warning', 'paid' => 'success'];
                $liqLabels = ['draft' => 'Bozza', 'approved' => 'Approvata', 'paid' => 'Pagata'];
                $lc = $liqColors[$liq->status] ?? 'secondary';
                $ll = $liqLabels[$liq->status] ?? $liq->status;
              @endphp
              <tr>
                <td>
                  <a href="{{ route('admin.agents.show', $liq->id) }}" class="text-decoration-none small fw-semibold">
                    {{ $liq->business_name }}
                  </a>
                  <br><span class="text-muted font-monospace" style="font-size:.72rem">{{ $liq->code }}</span>
                </td>
                <td><span class="small">{{ \Carbon\Carbon::parse($liq->period_month)->format('M Y') }}</span></td>
                <td class="fw-semibold small">€ {{ number_format($liq->total_amount_cents / 100, 2, ',', '.') }}</td>
                <td><span class="badge bg-label-{{ $lc }}">{{ $ll }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-3 text-muted small">Nessuna liquidazione</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
@php
  $trendTotal  = [];
  $trendPaid   = [];
  $monthLabels = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
  foreach ($months as $m) {
      $row = $monthlyTrend->get($m);
      $trendTotal[] = round(($row->total_cents ?? 0) / 100, 2);
      $trendPaid[]  = round(($row->paid_cents  ?? 0) / 100, 2);
  }
@endphp

new ApexCharts(document.getElementById('chartMonthlyTrend'), {
  chart: { type: 'bar', height: 280, toolbar: { show: false } },
  series: [
    { name: 'Maturate', data: @json($trendTotal) },
    { name: 'Liquidate', data: @json($trendPaid) },
  ],
  xaxis: { categories: @json($monthLabels) },
  colors: ['#696cff', '#71dd37'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  yaxis: { labels: { formatter: v => '€' + v.toLocaleString('it') } },
  tooltip: { y: { formatter: v => '€ ' + v.toLocaleString('it', { minimumFractionDigits: 2 }) } },
}).render();

@php
  $liqStatuses = ['draft' => 'Bozze', 'approved' => 'Approvate', 'paid' => 'Pagate'];
  $liqSeries   = [];
  $liqLabels   = [];
  foreach ($liqStatuses as $k => $label) {
      $liqSeries[] = (int) ($liquidationStats->get($k)?->cnt ?? 0);
      $liqLabels[] = $label;
  }
@endphp

new ApexCharts(document.getElementById('chartLiqStatus'), {
  chart: { type: 'donut', height: 220 },
  series: @json($liqSeries),
  labels: @json($liqLabels),
  colors: ['#a8aaae', '#ffab00', '#71dd37'],
  legend: { position: 'bottom' },
  dataLabels: { enabled: true },
}).render();
</script>
@endpush

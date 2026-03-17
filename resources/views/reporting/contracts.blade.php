@extends('layouts.contentNavbarLayout')
@section('title', 'Report Contratti')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reporting.index') }}">Reporting</a></li>
  <li class="breadcrumb-item active">Contratti</li>
@endsection

@section('page-content')

  <x-page-header title="Report Contratti">
    <x-slot name="action">
      <form class="d-flex gap-2 align-items-center">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
          @foreach($years as $y)
            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
          @endforeach
        </select>
      </form>
    </x-slot>
  </x-page-header>

  {{-- Grafico nuovi vs disdette --}}
  <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Nuovi contratti vs Disdette — {{ $year }}</h6></div>
    <div class="card-body"><div id="chartMonthly"></div></div>
  </div>

  <div class="row g-4">

    {{-- Per piano di servizio --}}
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Contratti attivi per piano di servizio</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Piano</th>
                  <th>Carrier</th>
                  <th>Tech</th>
                  <th class="text-end">Contratti</th>
                  <th class="text-end">MRR</th>
                </tr>
              </thead>
              <tbody>
                @foreach($byPlan as $p)
                  <tr>
                    <td class="small fw-semibold">{{ $p->name }}</td>
                    <td class="small text-muted">{{ strtoupper($p->carrier) }}</td>
                    <td class="small text-muted">{{ strtoupper($p->technology ?? '—') }}</td>
                    <td class="text-end small">{{ $p->cnt }}</td>
                    <td class="text-end small text-success fw-semibold">€ {{ number_format($p->mrr / 100, 2, ',', '.') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4 d-flex flex-column gap-4">

      {{-- Per carrier --}}
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Contratti per carrier</h6></div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div id="chartCarrier" style="width:100%"></div>
        </div>
      </div>

      {{-- Per stato --}}
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Distribuzione per stato</h6></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @foreach($byStatus as $status => $cnt)
              @php
                $color = match($status) {
                  'active'     => 'success',
                  'pending'    => 'warning',
                  'suspended'  => 'warning',
                  'terminated' => 'danger',
                  default      => 'secondary',
                };
              @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="small">{{ ucfirst($status) }}</span>
                <span class="badge bg-{{ $color }}">{{ $cnt }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>

    </div>

  </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
@php
  $mLabels  = collect(range(1,12))->map(fn($m) => \Carbon\Carbon::create($year, $m)->translatedFormat('M'));
  $mNew     = collect(range(1,12))->map(fn($m) => $monthly[$m]->new_contracts ?? 0);
  $mChurn   = collect(range(1,12))->map(fn($m) => $churnByMonth[$m]->churned ?? 0);
@endphp
new ApexCharts(document.getElementById('chartMonthly'), {
  chart: { type: 'bar', height: 300, toolbar: { show: false } },
  series: [
    { name: 'Nuovi', data: @json($mNew) },
    { name: 'Disdette', data: @json($mChurn) },
  ],
  xaxis: { categories: @json($mLabels) },
  colors: ['#71dd37', '#ff3e1d'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  yaxis: { labels: { formatter: v => Math.round(v) } },
}).render();

new ApexCharts(document.getElementById('chartCarrier'), {
  chart: { type: 'donut', height: 200 },
  series: @json(array_values($byCarrier->toArray())),
  labels: @json($byCarrier->keys()->map(fn($k) => strtoupper($k))),
  legend: { position: 'bottom' },
  dataLabels: { enabled: true },
}).render();
</script>
@endpush

@extends('layouts.contentNavbarLayout')
@section('title', 'Report Revenue')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reporting.index') }}">Reporting</a></li>
  <li class="breadcrumb-item active">Revenue</li>
@endsection

@section('page-content')

  <x-page-header title="Report Revenue">
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

  {{-- Grafico mensile --}}
  <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Fatturato vs Incassato — {{ $year }}</h6></div>
    <div class="card-body"><div id="chartMonthly"></div></div>
  </div>

  <div class="row g-4">

    {{-- Top clienti --}}
    <div class="col-12 col-lg-7">
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Top 10 clienti per fatturato</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr><th>Cliente</th><th class="text-end">Fatturato</th><th class="text-end">Incassato</th><th class="text-end">Fatture</th></tr>
              </thead>
              <tbody>
                @foreach($topCustomers as $c)
                  <tr>
                    <td class="small">{{ $c->full_name }}</td>
                    <td class="text-end small fw-semibold">€ {{ number_format($c->total_invoiced / 100, 2, ',', '.') }}</td>
                    <td class="text-end small text-success">€ {{ number_format($c->total_paid / 100, 2, ',', '.') }}</td>
                    <td class="text-end small text-muted">{{ $c->invoice_count }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Per metodo pagamento --}}
    <div class="col-12 col-lg-5">
      <div class="card h-100">
        <div class="card-header"><h6 class="mb-0">Incassi per metodo di pagamento</h6></div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div id="chartMethod" style="width:100%"></div>
        </div>
      </div>
    </div>

  </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
@php
  $mLabels    = collect(range(1,12))->map(fn($m) => \Carbon\Carbon::create($year, $m)->translatedFormat('M'));
  $mInvoiced  = collect(range(1,12))->map(fn($m) => round(($monthly[$m]->invoiced ?? 0) / 100, 2));
  $mCollected = collect(range(1,12))->map(fn($m) => round(($monthly[$m]->collected ?? 0) / 100, 2));
@endphp
new ApexCharts(document.getElementById('chartMonthly'), {
  chart: { type: 'area', height: 300, toolbar: { show: false } },
  series: [
    { name: 'Fatturato', data: @json($mInvoiced) },
    { name: 'Incassato', data: @json($mCollected) },
  ],
  xaxis: { categories: @json($mLabels) },
  colors: ['#696cff','#71dd37'],
  fill: { type: 'gradient', gradient: { opacityFrom: .4, opacityTo: .05 } },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  yaxis: { labels: { formatter: v => '€' + v.toLocaleString('it') } },
}).render();

new ApexCharts(document.getElementById('chartMethod'), {
  chart: { type: 'donut', height: 220 },
  series: @json($byMethod->pluck('total')->map(fn($v) => round($v/100,2))),
  labels: @json($byMethod->pluck('method')->map(fn($m) => strtoupper($m))),
  legend: { position: 'bottom' },
}).render();
</script>
@endpush

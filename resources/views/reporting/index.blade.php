@extends('layouts.contentNavbarLayout')
@section('title', 'Reporting')

@section('breadcrumb')
  <li class="breadcrumb-item active">Reporting</li>
@endsection

@section('page-content')

  <x-page-header title="Reporting & Analytics" subtitle="KPI operativi e finanziari del tenant" />

  {{-- KPI principali --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-money-euro-circle-line" color="success" label="MRR"
        :value="'€ ' . number_format($mrr, 2, ',', '.')"
        :href="route('reporting.revenue')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-funds-line" color="primary" label="ARR"
        :value="'€ ' . number_format($arr, 2, ',', '.')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-file-text-line" color="info" label="Contratti attivi"
        :value="$activeContracts" :href="route('reporting.contracts')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-customer-service-2-line" color="warning" label="SLA compliance"
        :value="$slaRate . '%'" :href="route('reporting.tickets')" />
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-add-circle-line" color="success" label="Nuovi contratti (mese)"  :value="$newThisMonth" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-close-circle-line" color="danger"  label="Disdette (mese)"       :value="$churnedThisMonth" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Incassato (mese)"   :value="'€ ' . number_format($paidThisMonth, 2, ',', '.')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-error-warning-line" color="danger"  label="Arretrato"            :value="'€ ' . number_format($overdueAmount, 2, ',', '.')" />
    </div>
  </div>

  <div class="row g-4">

    {{-- Grafico revenue 12 mesi --}}
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Revenue ultimi 12 mesi</h6>
          <a href="{{ route('reporting.revenue') }}" class="btn btn-sm btn-outline-primary">Dettaglio</a>
        </div>
        <div class="card-body">
          <div id="chartRevenue"></div>
        </div>
      </div>
    </div>

    {{-- Contratti per carrier --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Contratti per carrier</h6>
          <a href="{{ route('reporting.contracts') }}" class="btn btn-sm btn-outline-primary">Dettaglio</a>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div id="chartCarrier" style="width:100%"></div>
        </div>
      </div>
    </div>

  </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
@php
  $months    = $revenueChart->pluck('month')->toArray();
  $revenues  = $revenueChart->pluck('revenue')->map(fn($v) => round($v / 100, 2))->toArray();
  $collected = $revenueChart->pluck('collected')->map(fn($v) => round($v / 100, 2))->toArray();
@endphp

new ApexCharts(document.getElementById('chartRevenue'), {
  chart: { type: 'bar', height: 280, toolbar: { show: false } },
  series: [
    { name: 'Fatturato', data: @json($revenues) },
    { name: 'Incassato', data: @json($collected) },
  ],
  xaxis: { categories: @json($months) },
  colors: ['#696cff', '#71dd37'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  yaxis: { labels: { formatter: v => '€' + v.toLocaleString('it') } },
  tooltip: { y: { formatter: v => '€ ' + v.toLocaleString('it', {minimumFractionDigits:2}) } },
}).render();

new ApexCharts(document.getElementById('chartCarrier'), {
  chart: { type: 'donut', height: 250 },
  series: @json($byCarrier->values()),
  labels: @json($byCarrier->keys()->map(fn($k) => strtoupper($k))),
  legend: { position: 'bottom' },
  dataLabels: { enabled: true },
}).render();
</script>
@endpush

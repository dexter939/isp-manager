@extends('layouts.agent-portal')
@section('title', 'Provvigioni')
@section('nav_commissions', 'active')

@section('content')

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h5 class="mb-0">Le mie provvigioni</h5>
      <p class="text-muted small mb-0">Storico movimenti e maturazione provvigioni</p>
    </div>
  </div>

  {{-- Grafico trend (ultimi 6 mesi) --}}
  @if($monthlyTotals->isNotEmpty())
  <div class="card portal-card mb-4">
    <div class="card-header bg-transparent fw-semibold small">
      <i class="ri-bar-chart-2-line me-1 text-success"></i>Trend provvigioni (ultimi 6 mesi)
    </div>
    <div class="card-body">
      <div id="commChart" style="height:180px"></div>
    </div>
  </div>
  @endif

  {{-- Filtri --}}
  <div class="card portal-card mb-4">
    <div class="card-body py-2">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small mb-1">Stato</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tutti</option>
            @foreach(['pending','accrued','paid'] as $s)
              <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small mb-1">Periodo (mese)</label>
          <input type="month" name="period" class="form-control form-control-sm"
                 value="{{ request('period') }}" onchange="this.form.submit()">
        </div>
        <div class="col-12 col-md-2">
          @if(request('status') || request('period'))
            <a href="{{ route('agent-portal.commissions') }}" class="btn btn-sm btn-outline-secondary w-100">
              <i class="ri-close-line me-1"></i>Reset
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <div class="card portal-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="small fw-semibold">Cliente</th>
              <th class="small fw-semibold">Contratto</th>
              <th class="small fw-semibold">Tipo</th>
              <th class="small fw-semibold">Periodo</th>
              <th class="small fw-semibold text-end">Importo</th>
              <th class="small fw-semibold">Stato</th>
              <th class="small fw-semibold">Liquidazione</th>
            </tr>
          </thead>
          <tbody>
            @forelse($commissions as $ce)
              <tr>
                <td class="small">
                  {{ $ce->ragione_sociale ?? trim($ce->nome . ' ' . $ce->cognome) }}
                </td>
                <td class="small"><code>{{ $ce->contract_number }}</code></td>
                <td class="small text-muted">
                  {{ $ce->offer_type ?? '—' }}
                  @if($ce->rate_type)
                    <span class="text-muted">({{ $ce->rate_type }})</span>
                  @endif
                </td>
                <td class="small">{{ \Carbon\Carbon::parse($ce->period_month)->format('M Y') }}</td>
                <td class="small text-end fw-semibold">
                  € {{ number_format($ce->amount_cents / 100, 2, ',', '.') }}
                </td>
                <td><span class="badge badge-{{ $ce->status }}">{{ ucfirst($ce->status) }}</span></td>
                <td class="small text-muted">
                  {{ $ce->liquidation_id ? '#' . $ce->liquidation_id : '—' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4 small">
                  <i class="ri-money-euro-circle-line d-block fs-3 mb-1"></i>
                  Nessuna provvigione trovata.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($commissions->hasPages())
      <div class="card-footer bg-transparent">
        {{ $commissions->links() }}
      </div>
    @endif
  </div>

@endsection

@push('scripts')
@if($monthlyTotals->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>
<script>
new ApexCharts(document.getElementById('commChart'), {
  chart: { type: 'bar', height: 180, toolbar: { show: false }, sparkline: { enabled: false } },
  series: [{ name: 'Provvigioni (€)', data: @json($monthlyTotals->pluck('total')->map(fn($v) => round($v / 100, 2))) }],
  xaxis: { categories: @json($monthlyTotals->pluck('month')) },
  yaxis: { labels: { formatter: v => '€ ' + v.toFixed(2) } },
  colors: ['#28a745'],
  dataLabels: { enabled: false },
  plotOptions: { bar: { borderRadius: 4 } },
  tooltip: { y: { formatter: v => '€ ' + v.toFixed(2) } },
}).render();
</script>
@endif
@endpush

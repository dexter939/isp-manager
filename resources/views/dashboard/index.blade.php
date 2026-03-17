@extends('layouts.contentNavbarLayout')

@section('title', 'Dashboard')

@section('breadcrumb')
  <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Dashboard</h4>
    <p class="text-muted mb-0">Panoramica ISP — {{ now()->format('d/m/Y') }}</p>
  </div>

  {{-- KPI row --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-file-text-line" color="primary" label="Contratti attivi"
        :value="$stats['active_contracts'] ?? '—'"
        :href="route('contracts.index')" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-bill-line" color="success" label="Fatturato mese"
        :value="'€ ' . number_format($stats['monthly_revenue'] ?? 0, 2, ',', '.')" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-customer-service-2-line" color="warning" label="Ticket aperti"
        :value="$stats['open_tickets'] ?? '—'"
        :href="route('tickets.index', ['status' => 'open'])" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-alarm-warning-line" color="danger" label="Allarmi rete"
        :value="$stats['network_alerts'] ?? '—'"
        :href="route('monitoring.alerts')" />
    </div>
  </div>

  <div class="row g-3 mb-4">

    {{-- Contratti per stato --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Contratti per stato</span>
          <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-outline-primary">Vedi tutti</a>
        </div>
        <div class="card-body">
          <div id="chartContractsByStatus"></div>
        </div>
      </div>
    </div>

    {{-- Fatture scadute --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Fatture scadute (top 5)</span>
          <a href="{{ route('billing.invoices.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-outline-danger">Vedi tutte</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Numero</th>
                  <th>Cliente</th>
                  <th class="text-end">Importo</th>
                  <th>Scadenza</th>
                </tr>
              </thead>
              <tbody>
                @forelse($overdueInvoices ?? [] as $inv)
                  <tr>
                    <td><a href="{{ route('billing.invoices.show', $inv) }}">{{ $inv->number }}</a></td>
                    <td>{{ $inv->contract->customer->full_name ?? '—' }}</td>
                    <td class="text-end text-danger fw-semibold">€ {{ number_format($inv->total_amount / 100, 2, ',', '.') }}</td>
                    <td><span class="badge bg-danger">{{ $inv->due_date->format('d/m/Y') }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">Nessuna fattura scaduta</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- Ultimi ticket --}}
  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Ticket recenti</span>
          <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">Vedi tutti</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Numero</th>
                  <th>Oggetto</th>
                  <th>Cliente</th>
                  <th>Priorità</th>
                  <th>Stato</th>
                  <th>Aperto il</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentTickets ?? [] as $ticket)
                  <tr>
                    <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td>
                    <td>{{ Str::limit($ticket->subject, 50) }}</td>
                    <td>{{ $ticket->contract->customer->full_name ?? '—' }}</td>
                    <td>
                      <span class="badge bg-{{ $ticket->priority->badgeColor() }}">
                        {{ $ticket->priority->label() }}
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-secondary">{{ $ticket->status->label() }}</span>
                    </td>
                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted py-3">Nessun ticket recente</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  // Contracts by status donut chart
  const statusData = @json($stats['contracts_by_status'] ?? []);
  const labels = Object.keys(statusData);
  const series = Object.values(statusData);

  if (labels.length > 0) {
    new ApexCharts(document.getElementById('chartContractsByStatus'), {
      chart: { type: 'donut', height: 260 },
      labels: labels,
      series: series,
      colors: ['#696cff', '#71dd37', '#ffab00', '#ff3e1d', '#03c3ec'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: true },
      plotOptions: { pie: { donut: { size: '60%' } } },
    }).render();
  }
</script>
@endpush

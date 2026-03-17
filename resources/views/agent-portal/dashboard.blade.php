@extends('layouts.agent-portal')
@section('title', 'Dashboard')
@section('nav_dashboard', 'active')

@section('content')

  <div class="row g-3 mb-4">
    <div class="col-12">
      <h5 class="mb-0">Benvenuto, <strong>{{ auth('agent')->user()->display_name }}</strong></h5>
      <p class="text-muted small">Riepilogo del tuo account agente · Codice: <code>{{ auth('agent')->user()->code }}</code></p>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card portal-card stat-card h-100 p-3" style="border-color:#0d6efd">
        <div class="text-primary fs-3"><i class="ri-file-text-line"></i></div>
        <div class="fw-bold fs-3">{{ $contractsCount }}</div>
        <div class="text-muted small">Contratti assegnati</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card portal-card stat-card h-100 p-3" style="border-color:#ffc107">
        <div class="text-warning fs-3"><i class="ri-time-line"></i></div>
        <div class="fw-bold fs-3">€ {{ number_format($pendingCents / 100, 2, ',', '.') }}</div>
        <div class="text-muted small">Provvigioni in attesa</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card portal-card stat-card h-100 p-3" style="border-color:#0dcaf0">
        <div class="text-info fs-3"><i class="ri-checkbox-circle-line"></i></div>
        <div class="fw-bold fs-3">€ {{ number_format($accruedCents / 100, 2, ',', '.') }}</div>
        <div class="text-muted small">Approvate (da liquidare)</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card portal-card stat-card h-100 p-3" style="border-color:#28a745">
        <div class="text-success fs-3"><i class="ri-bank-card-line"></i></div>
        <div class="fw-bold fs-3">€ {{ number_format($paidCents / 100, 2, ',', '.') }}</div>
        <div class="text-muted small">Incassato totale</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- Ultimi contratti --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="ri-file-text-line me-2 text-primary"></i>Contratti recenti</span>
          <a href="{{ route('agent-portal.contracts') }}" class="btn btn-sm btn-outline-primary">Tutti</a>
        </div>
        <div class="card-body p-0">
          @forelse($recentContracts as $c)
            <div class="d-flex align-items-start gap-3 p-3 border-bottom">
              <div class="flex-grow-1">
                <div class="fw-semibold small">
                  {{ $c->ragione_sociale ?? trim($c->nome . ' ' . $c->cognome) }}
                </div>
                <div class="text-muted" style="font-size:.75rem">
                  {{ $c->contract_number }} · {{ $c->plan_name ?? '—' }}
                </div>
                @if($c->activation_date)
                  <div class="text-muted" style="font-size:.75rem">
                    Attivo dal {{ \Carbon\Carbon::parse($c->activation_date)->format('d/m/Y') }}
                  </div>
                @endif
              </div>
              <span class="badge badge-{{ $c->status }}">{{ ucfirst($c->status) }}</span>
            </div>
          @empty
            <p class="text-center text-muted py-4 small">Nessun contratto assegnato.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Ultimi movimenti provvigioni --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="ri-money-euro-circle-line me-2 text-success"></i>Provvigioni recenti</span>
          <a href="{{ route('agent-portal.commissions') }}" class="btn btn-sm btn-outline-success">Tutte</a>
        </div>
        <div class="card-body p-0">
          @forelse($recentCommissions as $ce)
            <div class="d-flex align-items-center gap-3 p-3 border-bottom">
              <div class="flex-grow-1">
                <div class="fw-semibold small">
                  {{ $ce->ragione_sociale ?? trim($ce->nome . ' ' . $ce->cognome) }}
                </div>
                <div class="text-muted" style="font-size:.75rem">
                  {{ $ce->contract_number }} · {{ \Carbon\Carbon::parse($ce->period_month)->format('M Y') }}
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold small">€ {{ number_format($ce->amount_cents / 100, 2, ',', '.') }}</div>
                <span class="badge badge-{{ $ce->status }}">{{ ucfirst($ce->status) }}</span>
              </div>
            </div>
          @empty
            <p class="text-center text-muted py-4 small">Nessuna provvigione registrata.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Ultima liquidazione --}}
    @if($lastLiquidation)
    <div class="col-12">
      <div class="card portal-card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="ri-bank-card-line me-2 text-info"></i>Ultima liquidazione</span>
          <a href="{{ route('agent-portal.liquidations') }}" class="btn btn-sm btn-outline-info">Storico</a>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <div class="text-muted small">Periodo</div>
              <div class="fw-semibold">{{ \Carbon\Carbon::parse($lastLiquidation->period_month)->format('F Y') }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small">Importo</div>
              <div class="fw-semibold">€ {{ number_format($lastLiquidation->total_amount_cents / 100, 2, ',', '.') }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small">Stato</div>
              <span class="badge badge-{{ $lastLiquidation->status }}">{{ ucfirst($lastLiquidation->status) }}</span>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-muted small">Pagato il</div>
              <div class="fw-semibold">
                {{ $lastLiquidation->paid_at ? \Carbon\Carbon::parse($lastLiquidation->paid_at)->format('d/m/Y') : '—' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

  </div>

@endsection

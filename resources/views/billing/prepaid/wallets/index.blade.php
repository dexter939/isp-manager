@extends('layouts.contentNavbarLayout')
@section('title', 'Portafogli Prepaid')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="#">Fatturazione</a></li>
  <li class="breadcrumb-item active">Portafogli Prepaid</li>
@endsection

@section('page-content')

<x-page-header title="Portafogli Prepaid" subtitle="Gestione saldi e ricariche clienti">
  <a href="{{ route('billing.prepaid.products.index') }}" class="btn btn-outline-secondary">
    <i class="ri-price-tag-3-line me-1"></i>Prodotti ricarica
  </a>
</x-page-header>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- KPI --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-wallet-3-line" color="primary" label="Totale portafogli"
      :value="number_format($kpis->total)" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-check-double-line" color="success" label="Attivi"
      :value="number_format($kpis->active_count)" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-money-euro-circle-line" color="info" label="Saldo totale"
      :value="'€ ' . number_format(($kpis->total_balance ?? 0) / 100, 2, ',', '.')" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-error-warning-line" color="warning" label="Saldo basso"
      :value="number_format($kpis->low_count)" />
  </div>
</div>

{{-- Filtri --}}
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-5">
        <label class="form-label small mb-1">Ricerca cliente</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
               placeholder="Nome, ragione sociale…">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Stato</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Tutti</option>
          <option value="active"    @selected(request('status') === 'active')>Attivo</option>
          <option value="suspended" @selected(request('status') === 'suspended')>Sospeso</option>
          <option value="closed"    @selected(request('status') === 'closed')>Chiuso</option>
        </select>
      </div>
      <div class="col-6 col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-fill">
          <i class="ri-search-line me-1"></i>Filtra
        </button>
        @if(request()->anyFilled(['q','status']))
          <a href="{{ route('billing.prepaid.wallets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-refresh-line"></i>
          </a>
        @endif
      </div>
    </form>
  </div>
</div>

{{-- Tabella --}}
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Cliente</th>
          <th>Saldo</th>
          <th>Soglia basso</th>
          <th>Sospensione auto</th>
          <th>Stato</th>
          <th>Creato il</th>
          <th class="text-end">Azioni</th>
        </tr>
      </thead>
      <tbody>
        @forelse($wallets as $w)
          @php
            $isLow = $w->balance_amount <= $w->low_balance_threshold_amount;
            $statusColors = ['active' => 'success', 'suspended' => 'warning', 'closed' => 'secondary'];
            $sc = $statusColors[$w->status] ?? 'secondary';
          @endphp
          <tr>
            <td>
              <a href="{{ route('customers.show', $w->customer_id) }}" class="fw-semibold text-decoration-none">
                {{ $w->customer_name }}
              </a>
            </td>
            <td>
              <span class="fw-bold {{ $isLow ? 'text-warning' : 'text-success' }}">
                @if($isLow)<i class="ri-error-warning-line me-1"></i>@endif
                € {{ number_format($w->balance_amount / 100, 2, ',', '.') }}
              </span>
            </td>
            <td class="text-muted small">€ {{ number_format($w->low_balance_threshold_amount / 100, 2, ',', '.') }}</td>
            <td>
              @if($w->auto_suspend_on_zero)
                <span class="badge bg-label-warning"><i class="ri-pause-circle-line me-1"></i>Sì</span>
              @else
                <span class="badge bg-label-secondary">No</span>
              @endif
            </td>
            <td><span class="badge bg-label-{{ $sc }}">{{ ucfirst($w->status) }}</span></td>
            <td class="text-muted small">{{ \Carbon\Carbon::parse($w->created_at)->format('d/m/Y') }}</td>
            <td class="text-end">
              <a href="{{ route('billing.prepaid.wallets.show', $w->id) }}"
                 class="btn btn-sm btn-icon btn-outline-secondary" title="Dettaglio">
                <i class="ri-eye-line"></i>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="ri-wallet-3-line fs-1 d-block mb-2 opacity-25"></i>
              Nessun portafoglio prepaid trovato.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($wallets->hasPages())
    <div class="card-footer">
      {{ $wallets->links() }}
    </div>
  @endif
</div>

@endsection

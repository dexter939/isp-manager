@extends('layouts.contentNavbarLayout')

@section('title', 'Proforma')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('billing.invoices.index') }}">Fatturazione</a></li>
  <li class="breadcrumb-item active">Proforma</li>
@endsection

@section('page-content')

  <x-page-header title="Proforma" subtitle="Gestione documenti proforma">
    <x-slot:action>
      <a href="#" class="btn btn-primary"><i class="ri-add-line me-1"></i>Nuovo proforma</a>
    </x-slot:action>
  </x-page-header>

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-file-list-3-line" color="warning" label="In attesa" :value="$stats['pending'] ?? '—'" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-time-line" color="danger" label="Scadute oggi" :value="$stats['expired_today'] ?? '—'" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-check-double-line" color="success" label="Convertite questo mese" :value="$stats['converted_month'] ?? '—'" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('billing.proforma.index')">
    <div class="col-12 col-sm-3">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="pending"   @selected(request('status') === 'pending')>In attesa</option>
        <option value="expired"   @selected(request('status') === 'expired')>Scadute</option>
        <option value="converted" @selected(request('status') === 'converted')>Convertite</option>
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Data da</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Data a</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#Proforma</th>
              <th>Cliente</th>
              <th class="text-end">Importo €</th>
              <th>Scadenza</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($proformas ?? [] as $pf)
              <tr>
                <td><a href="#">{{ $pf->number }}</a></td>
                <td>{{ $pf->contract->customer->full_name ?? '—' }}</td>
                <td class="text-end fw-semibold">
                  € {{ number_format(($pf->total_cents ?? $pf->total_amount ?? 0) / 100, 2, ',', '.') }}
                </td>
                <td class="{{ $pf->expires_at?->isPast() ? 'text-danger fw-semibold' : '' }}">
                  {{ $pf->expires_at?->format('d/m/Y') ?? '—' }}
                </td>
                <td><x-status-badge :status="$pf->invoice_type ?? 'proforma'" /></td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    @if(($pf->invoice_type ?? '') === 'proforma' && !$pf->converted_at)
                      <form method="POST" action="#" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success"
                                data-confirm="Convertire in fattura?">
                          <i class="ri-exchange-line"></i>
                        </button>
                      </form>
                    @endif
                    <a href="#" class="btn btn-sm btn-outline-info" title="Invia WhatsApp">
                      <i class="ri-whatsapp-line"></i>
                    </a>
                    <form method="POST" action="#" class="d-inline">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"
                              data-confirm="Eliminare questo proforma?">
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun proforma trovato" icon="ri-file-list-3-line" colspan="6" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($proformas) && $proformas->hasPages())
      <div class="card-footer">{{ $proformas->links() }}</div>
    @endif
  </div>

@endsection

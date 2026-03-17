@extends('layouts.contentNavbarLayout')
@section('title', 'SEPA SDD — Addebiti Diretti')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="#">Fatturazione</a></li>
  <li class="breadcrumb-item active">SEPA SDD</li>
@endsection

@section('page-content')

<x-page-header title="SEPA SDD — Addebiti Diretti" subtitle="Generazione pain.008 e import R-transaction pain.002" />

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- KPI --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-bank-card-line" color="primary" label="Mandati attivi"
      :value="number_format($kpis->active_mandates)" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-file-text-line" color="warning" label="Batch in attesa"
      :value="number_format($kpis->pending_files)" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-money-euro-circle-line" color="info" label="Fatture da addebitare"
      :value="number_format($kpis->due_invoices)" />
  </div>
  <div class="col-6 col-sm-3">
    <x-kpi-card icon="ri-money-euro-circle-line" color="success" label="Totale da addebitare"
      :value="'€ ' . number_format($kpis->due_total, 2, ',', '.')" />
  </div>
</div>

<div class="row g-4">

  {{-- Fatture eleggibili + azioni --}}
  <div class="col-12 col-lg-8">

    {{-- Fatture SDD eleggibili --}}
    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="ri-list-check me-1"></i>Fatture eleggibili per addebito SDD
          <span class="badge bg-label-{{ $dueSddInvoices->isEmpty() ? 'secondary' : 'warning' }} ms-1">
            {{ $dueSddInvoices->count() }}
          </span>
        </h6>
        @if($dueSddInvoices->isNotEmpty())
          <form method="POST" action="{{ route('billing.sepa.generate') }}"
                onsubmit="return confirm('Generare il batch SEPA pain.008 per {{ $dueSddInvoices->count() }} fatture (totale € {{ number_format($kpis->due_total, 2, \',\', \'.\') }})?')">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ri-send-plane-line me-1"></i>Genera batch pain.008
            </button>
          </form>
        @endif
      </div>
      <div class="table-responsive" style="max-height: 350px; overflow-y:auto">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light sticky-top">
            <tr>
              <th>Fattura</th>
              <th>Cliente</th>
              <th>Emissione</th>
              <th>Scadenza</th>
              <th class="text-end">Importo</th>
            </tr>
          </thead>
          <tbody>
            @forelse($dueSddInvoices as $inv)
              @php $overdue = \Carbon\Carbon::parse($inv->due_date)->isPast(); @endphp
              <tr class="{{ $overdue ? 'table-warning' : '' }}">
                <td>
                  <a href="{{ route('billing.invoices.show', $inv->id) }}" class="small fw-semibold font-monospace text-decoration-none">
                    {{ $inv->number }}
                  </a>
                </td>
                <td class="small">{{ $inv->customer_name }}</td>
                <td class="small text-muted">{{ \Carbon\Carbon::parse($inv->issue_date)->format('d/m/Y') }}</td>
                <td class="small {{ $overdue ? 'text-danger fw-semibold' : '' }}">
                  {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                  @if($overdue)<i class="ri-alert-line ms-1"></i>@endif
                </td>
                <td class="text-end fw-semibold small">€ {{ number_format($inv->total, 2, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="ri-checkbox-circle-line text-success me-1"></i>
                  Nessuna fattura SDD in attesa di addebito.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Storico batch generati --}}
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-history-line me-1"></i>Storico batch SEPA</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Message ID</th>
              <th>Tipo</th>
              <th>Transazioni</th>
              <th>Totale</th>
              <th>Data addebito</th>
              <th>Stato</th>
              <th>Generato il</th>
            </tr>
          </thead>
          <tbody>
            @forelse($sepaFiles as $sf)
              @php
                $sfColors = [
                  'generated'          => 'secondary',
                  'submitted'          => 'info',
                  'accepted'           => 'success',
                  'rejected'           => 'danger',
                  'partially_rejected' => 'warning',
                ];
                $sfc = $sfColors[$sf->status] ?? 'secondary';
              @endphp
              <tr>
                <td class="font-monospace small">{{ $sf->message_id }}</td>
                <td><span class="badge bg-label-primary">{{ strtoupper($sf->type) }}</span></td>
                <td>{{ $sf->transaction_count }}</td>
                <td class="fw-semibold">€ {{ number_format($sf->control_sum, 2, ',', '.') }}</td>
                <td class="small">{{ \Carbon\Carbon::parse($sf->settlement_date)->format('d/m/Y') }}</td>
                <td><span class="badge bg-label-{{ $sfc }}">{{ ucfirst(str_replace('_',' ', $sf->status)) }}</span></td>
                <td class="small text-muted">{{ \Carbon\Carbon::parse($sf->created_at)->format('d/m/Y H:i') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">Nessun batch SEPA generato.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($sepaFiles->hasPages())
        <div class="card-footer">{{ $sepaFiles->links() }}</div>
      @endif
    </div>

  </div>

  {{-- Sidebar: azioni e mandati --}}
  <div class="col-12 col-lg-4">

    {{-- Import file ritorno --}}
    <div class="card shadow-sm mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-upload-2-line me-1"></i>Import R-transaction (pain.002)</h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">
          Carica il file XML di risposta CBI con gli esiti degli addebiti
          (AC04 conto chiuso, AM04 fondi insufficienti, MD01 mandato non trovato…)
        </p>
        <form method="POST" action="{{ route('billing.sepa.import') }}" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label small">File pain.002 (XML)</label>
            <input type="file" name="return_file" class="form-control form-control-sm @error('return_file') is-invalid @enderror"
                   accept=".xml" required>
            @error('return_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="btn btn-outline-warning btn-sm w-100">
            <i class="ri-file-upload-line me-1"></i>Elabora file ritorno
          </button>
        </form>
      </div>
    </div>

    {{-- Stato mandati --}}
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-bank-card-line me-1"></i>Stato mandati SDD</h6>
      </div>
      <ul class="list-group list-group-flush">
        @php
          $mandateColors = ['active' => 'success', 'suspended' => 'warning', 'cancelled' => 'secondary', 'revoked' => 'danger'];
          $mandateLabels = ['active' => 'Attivi', 'suspended' => 'Sospesi', 'cancelled' => 'Annullati', 'revoked' => 'Revocati'];
        @endphp
        @foreach($mandateColors as $status => $color)
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span class="small">{{ $mandateLabels[$status] }}</span>
            <span class="badge bg-{{ $color }}">{{ $mandateStats->get($status)?->cnt ?? 0 }}</span>
          </li>
        @endforeach
      </ul>
      <div class="card-footer">
        <div class="alert alert-info small mb-0 py-2">
          <i class="ri-information-line me-1"></i>
          Il batch pain.008 include solo fatture con mandato in stato <strong>Attivo</strong>.
        </div>
      </div>
    </div>

  </div>
</div>

@endsection

@extends('layouts.contentNavbarLayout')

@section('title', 'Fatture')

@section('breadcrumb')
  <li class="breadcrumb-item">Fatturazione</li>
  <li class="breadcrumb-item active">Fatture</li>
@endsection

@section('page-content')

  <x-page-header title="Fatture" subtitle="Registro fatture elettroniche" />

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
          <label class="form-label small">Cerca</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Numero fattura, cliente..." value="{{ request('search') }}">
        </div>
        <div class="col-6 col-sm-2">
          <label class="form-label small">Stato</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="draft"   @selected(request('status') === 'draft')>Bozza</option>
            <option value="issued"  @selected(request('status') === 'issued')>Emessa</option>
            <option value="paid"    @selected(request('status') === 'paid')>Pagata</option>
            <option value="overdue" @selected(request('status') === 'overdue')>Scaduta</option>
          </select>
        </div>
        <div class="col-6 col-sm-2">
          <label class="form-label small">Mese</label>
          <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') }}">
        </div>
        <div class="col-12 col-sm-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          <a href="{{ route('billing.invoices.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Numero</th>
              <th>Cliente</th>
              <th>Emissione</th>
              <th>Scadenza</th>
              <th class="text-end">Imponibile</th>
              <th class="text-end">IVA</th>
              <th class="text-end">Totale</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($invoices as $invoice)
              @php $isOverdue = $invoice->status->value === 'overdue'; @endphp
              <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                <td class="font-monospace small">{{ $invoice->number }}</td>
                <td>{{ $invoice->contract->customer->full_name ?? '—' }}</td>
                <td class="small">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                <td class="small {{ $isOverdue ? 'text-danger fw-semibold' : '' }}">
                  {{ $invoice->due_date->format('d/m/Y') }}
                </td>
                <td class="text-end">€ {{ number_format($invoice->subtotal / 100, 2, ',', '.') }}</td>
                <td class="text-end text-muted small">€ {{ number_format($invoice->tax_amount / 100, 2, ',', '.') }}</td>
                <td class="text-end fw-semibold">€ {{ number_format($invoice->total_amount / 100, 2, ',', '.') }}</td>
                <td><x-status-badge :status="$invoice->status" /></td>
                <td class="text-end">
                  <a href="{{ route('billing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                  <a href="{{ route('billing.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="ri-file-pdf-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessuna fattura trovata" icon="ri-bill-line" colspan="9" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($invoices->hasPages())
      <div class="card-footer">{{ $invoices->links() }}</div>
    @endif
  </div>

@endsection

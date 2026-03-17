@extends('layouts.contentNavbarLayout')
@section('title', 'Trasmetti fatture SDI')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('billing.sdi.index') }}" class="btn btn-outline-secondary">
      <i class="ri-arrow-left-line"></i>
    </a>
    <div>
      <h4 class="fw-bold mb-0">Trasmissione fatture al SDI</h4>
      <p class="text-muted mb-0 small">Seleziona le fatture da inviare al Sistema di Interscambio</p>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
      @foreach($errors->all() as $error)
        <div><i class="ri-error-warning-line me-1"></i>{{ $error }}</div>
      @endforeach
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">Ricerca</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                 placeholder="Numero fattura, cliente…">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Anno</label>
          <select name="year" class="form-select form-select-sm">
            <option value="">Tutti</option>
            @foreach($years as $y)
              <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          @if(request()->anyFilled(['q','year']))
            <a href="{{ route('billing.sdi.batch') }}" class="btn btn-outline-secondary btn-sm">
              <i class="ri-refresh-line"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Batch form --}}
  <form method="POST" action="{{ route('billing.sdi.batch.post') }}" id="batchForm">
    @csrf

    <div class="card shadow-sm">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <div class="form-check mb-0">
            <input type="checkbox" class="form-check-input" id="selectAll">
            <label class="form-check-label fw-semibold" for="selectAll">Seleziona tutte</label>
          </div>
          <span class="badge bg-label-secondary" id="selectedCount">0 selezionate</span>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
          <i class="ri-send-plane-line me-1"></i>Trasmetti selezionate
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px"></th>
              <th>Fattura</th>
              <th>Cliente</th>
              <th>Data emissione</th>
              <th class="text-end">Importo</th>
              <th>Stato fattura</th>
              <th>Ultima trasmissione SDI</th>
            </tr>
          </thead>
          <tbody>
            @forelse($invoices as $inv)
              <tr>
                <td>
                  <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input invoice-check" name="invoice_ids[]"
                           value="{{ $inv->id }}">
                  </div>
                </td>
                <td>
                  <a href="{{ route('billing.invoices.show', $inv->id) }}" class="fw-semibold text-decoration-none small">
                    {{ $inv->number }}
                  </a>
                </td>
                <td class="small">{{ $inv->company_name ?: $inv->customer_name }}</td>
                <td class="small text-muted">
                  {{ \Carbon\Carbon::parse($inv->issue_date)->format('d/m/Y') }}
                </td>
                <td class="text-end fw-semibold small">
                  € {{ number_format($inv->total / 100, 2, ',', '.') }}
                </td>
                <td>
                  @php
                    $invStatusColors = ['issued'=>'info','paid'=>'success','overdue'=>'danger','suspended'=>'warning'];
                    $invStatusLabels = ['issued'=>'Emessa','paid'=>'Pagata','overdue'=>'Scaduta'];
                  @endphp
                  <span class="badge bg-label-{{ $invStatusColors[$inv->invoice_status] ?? 'secondary' }}">
                    {{ $invStatusLabels[$inv->invoice_status] ?? ucfirst($inv->invoice_status) }}
                  </span>
                </td>
                <td>
                  @if($inv->last_sdi_status)
                    @include('billing.sdi._status_badge', ['status' => $inv->last_sdi_status])
                    <span class="text-muted small ms-1">(non accettata)</span>
                  @else
                    <span class="badge bg-label-secondary">Mai trasmessa</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ri-checkbox-circle-line fs-1 d-block mb-2 opacity-25 text-success"></i>
                  Tutte le fatture risultano già trasmesse e accettate dall'SDI.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($invoices->hasPages())
        <div class="card-footer">{{ $invoices->links() }}</div>
      @endif
    </div>

  </form>

  {{-- Info card --}}
  <div class="card shadow-sm mt-4">
    <div class="card-body small text-muted">
      <h6 class="fw-semibold text-body mb-2"><i class="ri-information-line me-1"></i>Come funziona</h6>
      <ul class="mb-0 ps-3">
        <li>Vengono mostrate solo le fatture senza una trasmissione accettata (RC/EC/AT/DT).</li>
        <li>Le fatture in stato <strong>errore</strong> o <strong>rifiutate</strong> riappaiono automaticamente per ritrasmetterle.</li>
        <li>La trasmissione genera il file XML FatturaPA 1.2 e lo invia via <strong>{{ strtoupper(config('sdi.channel', 'aruba')) }}</strong>.</li>
        <li>Le notifiche SDI (RC, MC, NS, EC…) arrivano via webhook e aggiornano lo stato automaticamente.</li>
      </ul>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  const checkboxes = document.querySelectorAll('.invoice-check');
  const selectAll  = document.getElementById('selectAll');
  const submitBtn  = document.getElementById('submitBtn');
  const countLabel = document.getElementById('selectedCount');

  function updateCount() {
    const n = document.querySelectorAll('.invoice-check:checked').length;
    countLabel.textContent = n + ' selezionat' + (n === 1 ? 'a' : 'e');
    submitBtn.disabled = n === 0;
    selectAll.indeterminate = n > 0 && n < checkboxes.length;
    selectAll.checked = n === checkboxes.length && checkboxes.length > 0;
  }

  selectAll.addEventListener('change', () => {
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateCount();
  });

  checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

  document.getElementById('batchForm').addEventListener('submit', function(e) {
    const n = document.querySelectorAll('.invoice-check:checked').length;
    if (n === 0) { e.preventDefault(); return; }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Trasmissione in corso…';
  });
</script>
@endpush

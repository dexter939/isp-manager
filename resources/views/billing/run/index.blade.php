@extends('layouts.contentNavbarLayout')
@section('title', 'Generazione Fatture')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="#">Fatturazione</a></li>
  <li class="breadcrumb-item active">Generazione manuale</li>
@endsection

@section('page-content')

<x-page-header title="Generazione fatture" subtitle="Avvia la fatturazione mensile per un periodo specifico" />

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="ri-checkbox-circle-line me-1"></i>{!! nl2br(e(session('success'))) !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="row g-4">

  {{-- Form generazione --}}
  <div class="col-12 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-play-circle-line me-2"></i>Avvia generazione</h6>
      </div>
      <div class="card-body">

        <div class="alert alert-warning small mb-4">
          <i class="ri-alert-line me-1"></i>
          <strong>Attenzione:</strong> la generazione crea fatture reali per tutti i contratti attivi del mese selezionato.
          Usa la modalità <strong>Simulazione</strong> per verificare il risultato senza creare fatture.
        </div>

        <form method="POST" action="{{ route('billing.run.generate') }}" id="runForm">
          @csrf

          <div class="mb-4">
            <label class="form-label fw-semibold" for="month">Mese di competenza</label>
            <select name="month" id="month" class="form-select @error('month') is-invalid @enderror" required>
              @foreach($months as $value => $label)
                <option value="{{ $value }}" @selected(old('month', now()->format('Y-m')) === $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">
              Verranno fatturati tutti i <strong>{{ number_format($activeContracts) }} contratti attivi</strong> per il mese selezionato.
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Modalità</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="dry_run" id="modeLive" value="0" checked>
                <label class="form-check-label" for="modeLive">
                  <span class="fw-semibold text-danger">Produzione</span>
                  <small class="d-block text-muted">Crea fatture reali nel DB</small>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="dry_run" id="modeDry" value="1">
                <label class="form-check-label" for="modeDry">
                  <span class="fw-semibold text-info">Simulazione</span>
                  <small class="d-block text-muted">Solo anteprima, nessuna scrittura</small>
                </label>
              </div>
            </div>
          </div>

          <button type="submit" id="btnRun" class="btn btn-danger w-100"
                  onclick="return confirmRun()">
            <i class="ri-play-circle-line me-1"></i>Avvia generazione
          </button>
        </form>
      </div>
    </div>

    {{-- Info --}}
    <div class="card shadow-sm mt-4">
      <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-2"></i>Come funziona</h6></div>
      <div class="list-group list-group-flush">
        <div class="list-group-item small">
          <i class="ri-number-1 text-primary me-2"></i>
          Seleziona il mese e la modalità, poi clicca <strong>Avvia</strong>.
        </div>
        <div class="list-group-item small">
          <i class="ri-number-2 text-primary me-2"></i>
          Il sistema cicla su tutti i contratti attivi e genera una fattura per ciascuno se non ne esiste già una per quel mese.
        </div>
        <div class="list-group-item small">
          <i class="ri-number-3 text-primary me-2"></i>
          La fatturazione automatica avviene il <strong>1° di ogni mese alle 00:30</strong> tramite <code>BillingCycleJob</code>. Questo pannello serve per ri-generazioni manuali o mesi precedenti.
        </div>
        <div class="list-group-item small">
          <i class="ri-number-4 text-primary me-2"></i>
          I contratti con fattura già presente per il mese vengono saltati automaticamente (nessun duplicato).
        </div>
      </div>
    </div>
  </div>

  {{-- Storico run --}}
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-history-line me-2"></i>Storico generazioni (ultimi 12 mesi)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Mese</th>
              <th>Fatture</th>
              <th>Totale fatturato</th>
              <th>Ultima esecuzione</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentRuns as $run)
              <tr>
                <td class="fw-semibold">{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)?->translatedFormat('F Y') ?? $run->month }}</td>
                <td>
                  <span class="badge bg-label-primary">{{ number_format($run->invoice_count) }}</span>
                </td>
                <td class="fw-semibold">€ {{ number_format($run->total_amount, 2, ',', '.') }}</td>
                <td class="text-muted small">
                  {{ \Carbon\Carbon::parse($run->run_at)->format('d/m/Y H:i') }}
                </td>
                <td class="text-end">
                  <a href="{{ route('billing.invoices.index', ['month' => $run->month]) }}"
                     class="btn btn-sm btn-outline-secondary">
                    <i class="ri-eye-line me-1"></i>Fatture
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="ri-file-text-line fs-1 d-block mb-2 opacity-25"></i>
                  Nessuna generazione trovata.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

@endsection

@push('scripts')
<script>
function confirmRun() {
  const month  = document.getElementById('month').value;
  const dryRun = document.querySelector('[name=dry_run]:checked').value === '1';

  if (dryRun) return true; // simulazione: nessuna conferma necessaria

  const monthLabel = document.querySelector(`#month option[value="${month}"]`).textContent.trim();
  return confirm(
    `Stai per generare le fatture di ${monthLabel} in modalità PRODUZIONE.\n\n` +
    `Verranno create fatture reali per tutti i contratti attivi.\n\n` +
    `Confermare?`
  );
}

// Aggiorna il colore del bottone in base alla modalità
document.querySelectorAll('[name=dry_run]').forEach(el => {
  el.addEventListener('change', () => {
    const btn = document.getElementById('btnRun');
    if (el.value === '1' && el.checked) {
      btn.className = 'btn btn-info w-100';
      btn.innerHTML = '<i class="ri-test-tube-line me-1"></i>Avvia simulazione';
    } else if (el.value === '0' && el.checked) {
      btn.className = 'btn btn-danger w-100';
      btn.innerHTML = '<i class="ri-play-circle-line me-1"></i>Avvia generazione';
    }
  });
});
</script>
@endpush

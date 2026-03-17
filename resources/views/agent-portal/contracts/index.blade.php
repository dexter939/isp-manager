@extends('layouts.agent-portal')
@section('title', 'I miei contratti')
@section('nav_contracts', 'active')

@section('content')

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h5 class="mb-0">I miei contratti</h5>
      <p class="text-muted small mb-0">Contratti assegnati al tuo codice agente</p>
    </div>
  </div>

  {{-- Filtri --}}
  <div class="card portal-card mb-4">
    <div class="card-body py-2">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">Stato contratto</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tutti</option>
            @foreach(['active','suspended','terminated','pending'] as $s)
              <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2">
          @if(request('status'))
            <a href="{{ route('agent-portal.contracts') }}" class="btn btn-sm btn-outline-secondary w-100">
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
              <th class="small fw-semibold">Piano</th>
              <th class="small fw-semibold">Canone</th>
              <th class="small fw-semibold">Stato</th>
              <th class="small fw-semibold">Attivazione</th>
              <th class="small fw-semibold">Assegnato il</th>
            </tr>
          </thead>
          <tbody>
            @forelse($contracts as $c)
              <tr>
                <td class="small">
                  <div class="fw-semibold">{{ $c->ragione_sociale ?? trim($c->nome . ' ' . $c->cognome) }}</div>
                  <div class="text-muted" style="font-size:.75rem">{{ $c->customer_email }}</div>
                </td>
                <td class="small">
                  <code>{{ $c->contract_number }}</code>
                </td>
                <td class="small">{{ $c->plan_name ?? '—' }}</td>
                <td class="small">
                  @if($c->price_cents)
                    € {{ number_format($c->price_cents / 100, 2, ',', '.') }}/mese
                  @else
                    —
                  @endif
                </td>
                <td><span class="badge badge-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
                <td class="small text-muted">
                  {{ $c->activation_date ? \Carbon\Carbon::parse($c->activation_date)->format('d/m/Y') : '—' }}
                </td>
                <td class="small text-muted">
                  {{ \Carbon\Carbon::parse($c->assigned_at)->format('d/m/Y') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4 small">
                  <i class="ri-file-text-line d-block fs-3 mb-1"></i>
                  Nessun contratto assegnato.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($contracts->hasPages())
      <div class="card-footer bg-transparent">
        {{ $contracts->links() }}
      </div>
    @endif
  </div>

@endsection

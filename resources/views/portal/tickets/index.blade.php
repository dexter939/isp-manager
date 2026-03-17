@extends('layouts.portal')
@section('title', 'Le mie richieste')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="ri-customer-service-2-line me-2"></i>Le mie richieste di assistenza</h5>
    <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary btn-sm">
      <i class="ri-add-line me-1"></i>Nuova richiesta
    </a>
  </div>

  <div class="card portal-card mb-3">
    <div class="card-body py-2">
      <form class="row g-2">
        <div class="col-auto">
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti gli stati</option>
            <option value="open"       @selected(request('status') === 'open')>Aperto</option>
            <option value="in_progress" @selected(request('status') === 'in_progress')>In lavorazione</option>
            <option value="resolved"   @selected(request('status') === 'resolved')>Risolto</option>
            <option value="closed"     @selected(request('status') === 'closed')>Chiuso</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-sm btn-primary">Filtra</button>
          <a href="{{ route('portal.tickets') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
              <th>Numero</th>
              <th>Titolo</th>
              <th>Tipo</th>
              <th>Priorità</th>
              <th>Aperto il</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $t)
              <tr>
                <td>
                  <a href="{{ route('portal.tickets.show', $t->ticket_number) }}" class="fw-semibold font-monospace text-decoration-none small">
                    {{ $t->ticket_number }}
                  </a>
                </td>
                <td class="small">{{ $t->title }}</td>
                <td class="small text-muted">{{ ucfirst($t->type ?? '—') }}</td>
                <td>
                  @php $pc = match($t->priority) { 'critical','high' => 'danger', 'medium' => 'warning', default => 'secondary' }; @endphp
                  <span class="badge bg-{{ $pc }}">{{ ucfirst($t->priority) }}</span>
                </td>
                <td class="small text-muted">{{ \Carbon\Carbon::parse($t->opened_at)->format('d/m/Y') }}</td>
                <td><span class="badge badge-status-{{ $t->status }}">{{ ucfirst($t->status) }}</span></td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">Nessuna richiesta trovata.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($tickets->hasPages())
      <div class="card-footer">{{ $tickets->links() }}</div>
    @endif
  </div>

@endsection

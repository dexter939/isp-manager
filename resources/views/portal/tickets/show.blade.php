@extends('layouts.portal')
@section('title', $ticket->ticket_number)

@section('content')
  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.tickets') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line"></i></a>
    <h5 class="mb-0">{{ $ticket->ticket_number }}</h5>
    <span class="badge badge-status-{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span>
    @php $pc = match($ticket->priority) { 'critical','high' => 'danger', 'medium' => 'warning', default => 'secondary' }; @endphp
    <span class="badge bg-{{ $pc }}">{{ ucfirst($ticket->priority) }}</span>
  </div>

  <div class="row g-4">
    <div class="col-12 col-md-4">
      <div class="card portal-card">
        <div class="card-header bg-transparent fw-semibold small">Informazioni</div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-5 text-muted">Tipo</dt>
            <dd class="col-7">{{ ucfirst($ticket->type ?? '—') }}</dd>
            <dt class="col-5 text-muted">Aperto il</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($ticket->opened_at)->format('d/m/Y H:i') }}</dd>
            @if($ticket->resolved_at)
            <dt class="col-5 text-muted">Risolto il</dt>
            <dd class="col-7 text-success">{{ \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y H:i') }}</dd>
            @endif
            @if($ticket->due_at)
            <dt class="col-5 text-muted">Entro il</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($ticket->due_at)->format('d/m/Y H:i') }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-8">
      <div class="card portal-card mb-3">
        <div class="card-header bg-transparent fw-semibold small">{{ $ticket->title }}</div>
        <div class="card-body small" style="white-space:pre-wrap">{{ $ticket->description }}</div>
      </div>

      @if($notes->count() > 0)
        <div class="card portal-card">
          <div class="card-header bg-transparent fw-semibold small">Aggiornamenti dal supporto</div>
          <div class="card-body p-0">
            @foreach($notes as $note)
              <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between mb-1">
                  <span class="fw-semibold small">Supporto</span>
                  <span class="text-muted small">{{ \Carbon\Carbon::parse($note->created_at)->format('d/m/Y H:i') }}</span>
                </div>
                <div class="small" style="white-space:pre-wrap">{{ $note->body }}</div>
              </div>
            @endforeach
          </div>
        </div>
      @else
        <div class="text-center text-muted py-3 small">
          <i class="ri-time-line d-block fs-3 mb-1"></i>
          Il team di supporto prenderà in carico la richiesta al più presto.
        </div>
      @endif

      @if($ticket->resolution_notes && in_array($ticket->status, ['resolved','closed']))
        <div class="alert alert-success mt-3 small">
          <strong><i class="ri-checkbox-circle-line me-1"></i>Risoluzione:</strong>
          {{ $ticket->resolution_notes }}
        </div>
      @endif
    </div>
  </div>

@endsection

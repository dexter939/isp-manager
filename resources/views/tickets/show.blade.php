@extends('layouts.contentNavbarLayout')
@section('title', 'Ticket ' . $ticket->ticket_number)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Ticket</a></li>
  <li class="breadcrumb-item active">{{ $ticket->ticket_number }}</li>
@endsection

@section('page-content')

  {{-- Header --}}
  @php
    $isOpen = !in_array($ticket->status, ['resolved','closed','cancelled']);
    $isOverdue = $ticket->due_at && \Carbon\Carbon::parse($ticket->due_at)->isPast() && $isOpen;
    $priorityColor = match($ticket->priority) {
        'critical' => 'danger', 'high' => 'danger', 'medium' => 'warning', default => 'secondary'
    };
    $statusLabel = match($ticket->status) {
        'open' => 'Aperto', 'in_progress' => 'In lavorazione', 'pending' => 'In attesa',
        'resolved' => 'Risolto', 'closed' => 'Chiuso', 'cancelled' => 'Annullato', default => $ticket->status
    };
  @endphp

  <div class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="font-monospace fw-bold fs-5">{{ $ticket->ticket_number }}</span>
        <span class="badge bg-{{ $priorityColor }}">{{ ucfirst($ticket->priority) }}</span>
        <span class="badge bg-label-{{ match($ticket->status) {
          'open' => 'primary', 'in_progress' => 'info', 'pending' => 'warning',
          'resolved' => 'success', 'closed' => 'secondary', default => 'secondary'
        } }}">{{ $statusLabel }}</span>
        @if($isOverdue)
          <span class="badge bg-danger"><i class="ri-alarm-warning-line me-1"></i>SLA scaduto</span>
        @endif
      </div>
      <h5 class="mb-0">{{ $ticket->title }}</h5>
    </div>

    {{-- Quick actions --}}
    @if($isOpen)
      <div class="d-flex gap-2 flex-wrap">
        @foreach($transitions as $t)
          @if(in_array($t->value, ['resolved', 'closed', 'in_progress', 'pending']))
            <button type="button" class="btn btn-sm btn-outline-{{ match($t->value) {
              'resolved' => 'success', 'closed' => 'secondary', 'in_progress' => 'primary', default => 'warning'
            } }}" data-bs-toggle="modal" data-bs-target="#transitionModal"
              data-status="{{ $t->value }}" data-label="{{ $t->label() }}">
              {{ $t->label() }}
            </button>
          @endif
        @endforeach
      </div>
    @endif
  </div>

  <div class="row g-4">

    {{-- Colonna principale --}}
    <div class="col-12 col-xl-8">

      {{-- Descrizione --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">Descrizione</div>
        <div class="card-body small" style="white-space:pre-wrap">{{ $ticket->description }}</div>
      </div>

      {{-- Note & timeline --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">Timeline</div>
        <div class="card-body p-0">
          @forelse($notes as $note)
            @php
              $noteColor = match($note->type) {
                'status_change' => 'info', 'assignment' => 'primary',
                'sla_breach' => 'danger', 'sla_escalation' => 'danger',
                'system' => 'secondary', default => $note->is_internal ? 'warning' : 'light'
              };
              $noteIcon = match($note->type) {
                'status_change' => 'ri-refresh-line', 'assignment' => 'ri-user-shared-line',
                'sla_breach' => 'ri-alarm-warning-line', 'sla_escalation' => 'ri-arrow-up-circle-line',
                default => 'ri-chat-3-line'
              };
            @endphp
            <div class="d-flex gap-3 p-3 border-bottom {{ $note->is_internal ? 'bg-light' : '' }}">
              <div class="flex-shrink-0">
                <span class="avatar avatar-sm bg-label-{{ $noteColor }} rounded-circle">
                  <i class="{{ $noteIcon }}"></i>
                </span>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between mb-1">
                  <span class="fw-semibold small">{{ $note->author_name ?? 'Sistema' }}</span>
                  <div class="d-flex align-items-center gap-2">
                    @if($note->is_internal)
                      <span class="badge bg-label-warning small">Interno</span>
                    @endif
                    <span class="text-muted small">{{ \Carbon\Carbon::parse($note->created_at)->format('d/m/Y H:i') }}</span>
                  </div>
                </div>
                <div class="small" style="white-space:pre-wrap">{{ $note->body }}</div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4 small">
              <i class="ri-chat-3-line d-block fs-3 mb-1"></i>Nessuna attività registrata.
            </div>
          @endforelse
        </div>
      </div>

      {{-- Aggiungi nota --}}
      @if($isOpen)
        <div class="card">
          <div class="card-header fw-semibold small">Aggiungi nota</div>
          <div class="card-body">
            <form method="POST" action="{{ route('tickets.note', $ticket->id) }}">
              @csrf
              <textarea name="body" class="form-control form-control-sm mb-2" rows="3"
                        placeholder="Scrivi una nota..." required></textarea>
              <div class="d-flex justify-content-between align-items-center">
                <div class="form-check form-check-sm">
                  <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                  <label class="form-check-label small" for="is_internal">Solo interno (non visibile al cliente)</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="ri-send-plane-line me-1"></i>Invia
                </button>
              </div>
            </form>
          </div>
        </div>
      @endif

      {{-- Note di risoluzione --}}
      @if($ticket->resolution_notes && !$isOpen)
        <div class="alert alert-success mt-3 small">
          <strong><i class="ri-checkbox-circle-line me-1"></i>Risoluzione:</strong>
          {{ $ticket->resolution_notes }}
        </div>
      @endif

    </div>

    {{-- Colonna info --}}
    <div class="col-12 col-xl-4">

      {{-- SLA Card --}}
      <div class="card mb-4 {{ $isOverdue ? 'border-danger' : '' }}">
        <div class="card-header fw-semibold small {{ $isOverdue ? 'bg-danger text-white' : '' }}">
          <i class="ri-timer-line me-1"></i>SLA
        </div>
        <div class="card-body small">

          {{-- Risoluzione --}}
          <div class="mb-3">
            <div class="text-muted mb-1">Scadenza risoluzione</div>
            @if($due)
              @if($isOverdue)
                <div class="text-danger fw-bold">
                  <i class="ri-alarm-warning-line me-1"></i>
                  Scaduto {{ $due->diffForHumans() }}
                </div>
                <div class="text-muted small">{{ $due->format('d/m/Y H:i') }}</div>
              @else
                <div class="fw-semibold" id="sla-countdown" data-due="{{ $due->toIso8601String() }}">
                  {{ $due->diffForHumans() }}
                </div>
                <div class="text-muted small">{{ $due->format('d/m/Y H:i') }}</div>
                @php $pct = max(0, min(100, 100 - ($due->diffInMinutes(now(), false) / ($slaResolutionH * 60)) * 100)); @endphp
                <div class="progress mt-1" style="height:4px">
                  <div class="progress-bar {{ $pct > 80 ? 'bg-danger' : ($pct > 50 ? 'bg-warning' : 'bg-success') }}"
                       style="width:{{ $pct }}%"></div>
                </div>
              @endif
            @else
              <span class="text-muted">Non impostata</span>
            @endif
          </div>

          {{-- Prima risposta --}}
          <div>
            <div class="text-muted mb-1">Prima risposta (SLA {{ $slaFirstResponseH }}h)</div>
            @if($ticket->first_response_at)
              <span class="text-success small">
                <i class="ri-checkbox-circle-line me-1"></i>
                {{ \Carbon\Carbon::parse($ticket->first_response_at)->format('d/m/Y H:i') }}
              </span>
            @elseif($firstResponseDue->isPast())
              <span class="text-danger small fw-semibold">
                <i class="ri-alarm-warning-line me-1"></i>Scaduta
              </span>
            @else
              <span class="text-warning small">
                Entro {{ $firstResponseDue->format('d/m/Y H:i') }}
              </span>
            @endif
          </div>

          @if($ticket->sla_type)
            <hr class="my-2">
            <div class="text-muted small">
              Piano: {{ $ticket->plan_name }} —
              SLA <strong>{{ strtoupper($ticket->sla_type) }}</strong>
              @if($ticket->mtr_hours) (MTR {{ $ticket->mtr_hours }}h) @endif
            </div>
          @endif
        </div>
      </div>

      {{-- Info ticket --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">Informazioni</div>
        <div class="card-body small">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">Cliente</dt>
            <dd class="col-7">{{ $ticket->customer_full_name ?? '—' }}</dd>
            @if($ticket->contract_number)
            <dt class="col-5 text-muted">Contratto</dt>
            <dd class="col-7 font-monospace">{{ $ticket->contract_number }}</dd>
            @endif
            <dt class="col-5 text-muted">Tipo</dt>
            <dd class="col-7">{{ ucfirst($ticket->type ?? '—') }}</dd>
            <dt class="col-5 text-muted">Sorgente</dt>
            <dd class="col-7">{{ ucfirst($ticket->source ?? '—') }}</dd>
            <dt class="col-5 text-muted">Aperto il</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($ticket->opened_at)->format('d/m/Y H:i') }}</dd>
            @if($ticket->resolved_at)
            <dt class="col-5 text-muted">Risolto il</dt>
            <dd class="col-7 text-success">{{ \Carbon\Carbon::parse($ticket->resolved_at)->format('d/m/Y H:i') }}</dd>
            @endif
            @if($ticket->carrier)
            <dt class="col-5 text-muted">Carrier</dt>
            <dd class="col-7">{{ strtoupper($ticket->carrier) }}</dd>
            @endif
            @if($ticket->carrier_ticket_id)
            <dt class="col-5 text-muted">ID Carrier</dt>
            <dd class="col-7 font-monospace small">{{ $ticket->carrier_ticket_id }}</dd>
            @endif
          </dl>
        </div>
      </div>

      {{-- Assegnazione --}}
      @if($isOpen)
        <div class="card">
          <div class="card-header fw-semibold small">Assegnazione</div>
          <div class="card-body">
            <form method="POST" action="{{ route('tickets.assign', $ticket->id) }}" class="d-flex gap-2">
              @csrf
              <select name="assigned_to" class="form-select form-select-sm">
                <option value="">— Non assegnato —</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}" @selected($ticket->assigned_to == $u->id)>{{ $u->name }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="ri-user-shared-line"></i>
              </button>
            </form>
          </div>
        </div>
      @else
        <div class="card">
          <div class="card-header fw-semibold small">Assegnato a</div>
          <div class="card-body small">{{ $ticket->assigned_name ?? '—' }}</div>
        </div>
      @endif

    </div>
  </div>

  {{-- Modal transizione stato --}}
  @if($isOpen)
  <div class="modal fade" id="transitionModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('tickets.transition', $ticket->id) }}" class="modal-content">
        @csrf
        <input type="hidden" name="status" id="transitionStatus">
        <div class="modal-header">
          <h5 class="modal-title" id="transitionTitle">Cambia stato</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="resolutionNotesWrapper" style="display:none">
            <label class="form-label">Note di risoluzione</label>
            <textarea name="resolution_notes" class="form-control" rows="3"
                      placeholder="Descrivi come è stato risolto il problema..."></textarea>
          </div>
          <p id="transitionConfirmText" class="mb-0 text-muted small"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Conferma</button>
        </div>
      </form>
    </div>
  </div>
  @endif

@endsection

@push('scripts')
<script>
// Transition modal population
document.getElementById('transitionModal')?.addEventListener('show.bs.modal', function(e) {
  const btn    = e.relatedTarget;
  const status = btn.dataset.status;
  const label  = btn.dataset.label;
  document.getElementById('transitionStatus').value    = status;
  document.getElementById('transitionTitle').textContent = 'Imposta: ' + label;
  document.getElementById('transitionConfirmText').textContent =
    'Cambia lo stato del ticket in "' + label + '"';
  const showNotes = ['resolved','closed'].includes(status);
  document.getElementById('resolutionNotesWrapper').style.display = showNotes ? '' : 'none';
});

// SLA live countdown
const countdownEl = document.getElementById('sla-countdown');
if (countdownEl) {
  const due = new Date(countdownEl.dataset.due);
  function updateCountdown() {
    const diff = due - Date.now();
    if (diff <= 0) { countdownEl.textContent = 'Scaduto'; return; }
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    countdownEl.textContent = h > 0 ? h + 'h ' + m + 'm' : m + 'm';
  }
  updateCountdown();
  setInterval(updateCountdown, 60000);
}
</script>
@endpush

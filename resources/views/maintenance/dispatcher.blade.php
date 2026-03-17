@extends('layouts.contentNavbarLayout')

@section('title', 'Dispatcher')

@section('breadcrumb')
  <li class="breadcrumb-item">Field Service</li>
  <li class="breadcrumb-item active">Dispatcher</li>
@endsection

@section('page-content')

  <x-page-header title="Dispatcher interventi" subtitle="Pianificazione e assegnazione tecnici">
    <x-slot:action>
      <a href="#" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nuovo intervento
      </a>
    </x-slot:action>
  </x-page-header>

  {{-- Filtro tecnico --}}
  <x-filter-bar :resetRoute="route('maintenance.dispatcher.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Tecnico</label>
      <select name="technician_id" class="form-select form-select-sm">
        <option value="">Tutti i tecnici</option>
        @foreach($technicians ?? [] as $tech)
          <option value="{{ $tech->id }}" @selected(request('technician_id') == $tech->id)>
            {{ $tech->name }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Data</label>
      <input type="date" name="date" class="form-control form-control-sm"
             value="{{ request('date', today()->toDateString()) }}">
    </div>
  </x-filter-bar>

  {{-- Conflitti rilevati --}}
  @if(!empty($conflicts))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="ri-error-warning-line fs-5"></i>
      <strong>{{ count($conflicts) }} conflitti rilevati</strong> — verifica le assegnazioni evidenziate.
    </div>
  @endif

  <div class="row g-3 mb-4">

    {{-- Timeline --}}
    <div class="col-12 col-xl-8">
      <div class="card">
        <div class="card-header">
          <i class="ri-time-line me-2"></i>Timeline giornaliera
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="min-width:700px">
              <thead class="table-light">
                <tr>
                  <th style="min-width:130px">Tecnico</th>
                  @foreach(range(8, 18) as $hour)
                    <th class="text-center small" style="min-width:55px">{{ sprintf('%02d:00', $hour) }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @forelse($timeline ?? [] as $techName => $slots)
                  <tr>
                    <td class="small fw-medium">{{ $techName }}</td>
                    @foreach(range(8, 18) as $hour)
                      @php $slot = $slots[$hour] ?? null; @endphp
                      <td class="p-1 text-center">
                        @if($slot)
                          <span class="badge bg-primary" style="font-size:.7rem" title="{{ $slot['title'] ?? '' }}">
                            {{ Str::limit($slot['title'] ?? '', 8) }}
                          </span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @empty
                  <tr>
                    <td colspan="12" class="text-center text-muted py-3 small">
                      Nessun intervento programmato
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Non assegnati --}}
    <div class="col-12 col-xl-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span><i class="ri-question-mark me-2"></i>Non assegnati</span>
          <span class="badge bg-warning text-dark rounded-pill">{{ count($unassigned ?? []) }}</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:360px;overflow-y:auto">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Intervento</th>
                  <th>Priorità</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($unassigned ?? [] as $intervention)
                  <tr>
                    <td class="small">
                      <span class="fw-medium">{{ $intervention->ticket_number ?? $intervention->id }}</span>
                      <br><small class="text-muted">{{ Str::limit($intervention->subject ?? '', 30) }}</small>
                    </td>
                    <td><x-status-badge :status="$intervention->priority" /></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#modalAssign"
                              data-id="{{ $intervention->id }}"
                              data-label="{{ $intervention->ticket_number ?? $intervention->id }} — {{ Str::limit($intervention->subject ?? '', 30) }}"
                              title="Assegna">
                        <i class="ri-user-add-line"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <x-empty-state message="Tutti assegnati" icon="ri-checkbox-circle-line" colspan="3" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

{{-- Modal: assegna intervento --}}
<div class="modal fade" id="modalAssign" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-user-add-line me-2"></i>Assegna intervento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('maintenance.dispatcher.assignments.store') }}">
        @csrf
        <input type="hidden" name="intervention_id" id="assignInterventionId">
        <div class="modal-body">
          <div class="alert alert-light border small mb-3" id="assignLabel"></div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Tecnico *</label>
            <select name="user_id" class="form-select" required>
              <option value="">— Seleziona tecnico —</option>
              @foreach($technicians ?? [] as $tech)
                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-7">
              <label class="form-label fw-semibold small">Data e ora inizio *</label>
              <input type="datetime-local" name="scheduled_start" id="assignStart" class="form-control" required>
            </div>
            <div class="col-5">
              <label class="form-label fw-semibold small">Durata (min) *</label>
              <input type="number" name="estimated_duration_minutes" class="form-control"
                     min="15" max="480" value="60" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Note</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary"><i class="ri-user-add-line me-1"></i>Assegna</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('modalAssign')?.addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  document.getElementById('assignInterventionId').value = btn?.dataset?.id ?? '';
  document.getElementById('assignLabel').textContent =
    btn?.dataset?.label ? `Intervento: ${btn.dataset.label}` : 'Intervento selezionato';
  // Pre-fill datetime with selected date from filter
  const date = document.querySelector('[name="date"]')?.value ?? new Date().toISOString().slice(0, 10);
  document.getElementById('assignStart').value = date + 'T09:00';
});
</script>
@endpush

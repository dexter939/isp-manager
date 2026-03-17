@extends('layouts.contentNavbarLayout')
@section('title', 'Ottimizzatore percorsi')

@section('breadcrumb')
  <li class="breadcrumb-item">Field Service</li>
  <li class="breadcrumb-item active">Ottimizzatore percorsi</li>
@endsection

@section('page-content')

  <x-page-header title="Ottimizzatore percorsi" subtitle="Pianificazione e ottimizzazione giri tecnici">
    <x-slot:action>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPlanModal">
        <i class="ri-route-line me-1"></i>Nuovo piano
      </button>
    </x-slot:action>
  </x-page-header>

  {{-- Filtri --}}
  <x-filter-bar :resetRoute="route('maintenance.route-optimizer.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Tecnico</label>
      <select name="technician_id" class="form-select form-select-sm">
        <option value="">Tutti i tecnici</option>
        @foreach($technicians as $tech)
          <option value="{{ $tech->id }}" @selected(request('technician_id') == $tech->id)>
            {{ $tech->name }}
            @if($tech->daily_capacity_hours)
              ({{ $tech->daily_capacity_hours }}h/g)
            @endif
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Data piano</label>
      <input type="date" name="plan_date" class="form-control form-control-sm"
             value="{{ request('plan_date') }}">
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="draft"     @selected(request('status') === 'draft')>Bozza</option>
        <option value="active"    @selected(request('status') === 'active')>Attivo</option>
        <option value="completed" @selected(request('status') === 'completed')>Completato</option>
      </select>
    </div>
  </x-filter-bar>

  {{-- KPI rapidi --}}
  @php
    $totalPlans     = $routePlans->total();
    $activePlans    = $routePlans->getCollection()->where('status', 'active')->count();
    $avgDistKm      = $routePlans->getCollection()->whereNotNull('total_distance_km')->avg('total_distance_km');
    $avgDurationMin = $routePlans->getCollection()->whereNotNull('total_duration_minutes')->avg('total_duration_minutes');
  @endphp

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-primary">{{ $totalPlans }}</div>
          <div class="small text-muted">Piani totali</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-success">{{ $activePlans }}</div>
          <div class="small text-muted">Piani attivi (pagina)</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-info">
            {{ $avgDistKm ? number_format($avgDistKm, 1) . ' km' : '—' }}
          </div>
          <div class="small text-muted">Distanza media</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-warning">
            {{ $avgDurationMin ? round($avgDurationMin / 60, 1) . ' h' : '—' }}
          </div>
          <div class="small text-muted">Durata media</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabella piani --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="small fw-semibold">Tecnico</th>
              <th class="small fw-semibold">Data piano</th>
              <th class="small fw-semibold">Partenza</th>
              <th class="small fw-semibold text-center">Tappe</th>
              <th class="small fw-semibold text-end">Distanza</th>
              <th class="small fw-semibold text-end">Durata est.</th>
              <th class="small fw-semibold">Stato</th>
              <th class="small fw-semibold">Creato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($routePlans as $plan)
              @php
                $stops = is_array($plan->optimized_order)
                  ? count($plan->optimized_order)
                  : count(json_decode($plan->optimized_order ?? '[]', true));
                $statusColor = match($plan->status) {
                  'active'    => 'success',
                  'completed' => 'secondary',
                  default     => 'warning',
                };
                $statusLabel = match($plan->status) {
                  'active'    => 'Attivo',
                  'completed' => 'Completato',
                  default     => 'Bozza',
                };
              @endphp
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-label-primary rounded-circle">
                      {{ strtoupper(substr($plan->tech_name, 0, 1)) }}
                    </span>
                    <span class="small fw-semibold">{{ $plan->tech_name }}</span>
                  </div>
                </td>
                <td class="small">
                  {{ \Carbon\Carbon::parse($plan->plan_date)->format('d/m/Y') }}
                  <div class="text-muted" style="font-size:.72rem">
                    {{ \Carbon\Carbon::parse($plan->plan_date)->isoFormat('dddd') }}
                  </div>
                </td>
                <td class="small text-muted">
                  @if($plan->start_address)
                    <i class="ri-map-pin-line me-1 text-danger"></i>{{ $plan->start_address }}
                  @else
                    <span class="text-muted">{{ number_format($plan->start_lat, 5) }},
                    {{ number_format($plan->start_lon, 5) }}</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-label-primary">{{ $stops }}</span>
                </td>
                <td class="small text-end">
                  {{ $plan->total_distance_km ? number_format($plan->total_distance_km, 1) . ' km' : '—' }}
                </td>
                <td class="small text-end">
                  @if($plan->total_duration_minutes)
                    @php
                      $h = intdiv($plan->total_duration_minutes, 60);
                      $m = $plan->total_duration_minutes % 60;
                    @endphp
                    {{ $h > 0 ? "{$h}h " : '' }}{{ $m }}min
                  @else
                    —
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-{{ $statusColor }}">{{ $statusLabel }}</span>
                </td>
                <td class="small text-muted">
                  {{ \Carbon\Carbon::parse($plan->created_at)->format('d/m/Y') }}
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-info"
                          onclick="showPlanDetail({{ json_encode($plan) }})"
                          title="Vedi dettaglio tappe">
                    <i class="ri-road-map-line"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  <i class="ri-route-line d-block fs-1 mb-2 opacity-25"></i>
                  Nessun piano di percorso trovato.
                  @if(!$routePlans->total())
                    <div class="mt-2">
                      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                        <i class="ri-add-line me-1"></i>Crea il primo piano
                      </button>
                    </div>
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($routePlans->hasPages())
      <div class="card-footer bg-transparent">
        {{ $routePlans->links() }}
      </div>
    @endif
  </div>

  {{-- Pannello tecnici disponibili --}}
  @if($technicians->isNotEmpty())
  <div class="card mt-4">
    <div class="card-header fw-semibold small">
      <i class="ri-user-location-line me-1 text-primary"></i>Tecnici disponibili
    </div>
    <div class="card-body">
      <div class="row g-3">
        @foreach($technicians as $tech)
          @php
            $techPlansToday = $routePlans->getCollection()
              ->where('technician_id', $tech->id)
              ->where('plan_date', today()->toDateString());
          @endphp
          <div class="col-12 col-md-4 col-xl-3">
            <div class="d-flex align-items-center gap-3 p-3 border rounded">
              <span class="avatar avatar-md bg-label-primary rounded-circle fw-bold">
                {{ strtoupper(substr($tech->name, 0, 1)) }}
              </span>
              <div>
                <div class="fw-semibold small">{{ $tech->name }}</div>
                <div class="text-muted" style="font-size:.75rem">
                  <i class="ri-time-line me-1"></i>{{ $tech->daily_capacity_hours }}h/giorno
                </div>
                @if($techPlansToday->isNotEmpty())
                  <span class="badge bg-label-success mt-1" style="font-size:.65rem">
                    Piano oggi
                  </span>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

@endsection

{{-- Modal: dettaglio tappe piano --}}
<div class="modal fade" id="planDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ri-road-map-line me-2 text-primary"></i>
          Dettaglio piano — <span id="modalPlanDate"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3" id="modalKpis"></div>
        <hr>
        <h6 class="small fw-semibold text-muted mb-3">ORDINE TAPPE OTTIMIZZATO</h6>
        <div id="modalStops"></div>
      </div>
    </div>
  </div>
</div>

{{-- Modal: nuovo piano --}}
<div class="modal fade" id="newPlanModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-route-line me-2"></i>Nuovo piano di percorso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('maintenance.route-optimizer.generate') }}">
        @csrf
      <div class="modal-body">
        <div class="alert alert-info small">
          <i class="ri-information-line me-1"></i>
          Il piano viene creato in bozza. L'ottimizzazione del percorso viene calcolata in background sugli interventi pianificati per il tecnico.
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Tecnico *</label>
          <select name="technician_id" class="form-select" required>
            <option value="">— Seleziona tecnico —</option>
            @foreach($technicians as $tech)
              <option value="{{ $tech->id }}">
                {{ $tech->name }} ({{ $tech->daily_capacity_hours }}h/g)
              </option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Data *</label>
          <input type="date" name="plan_date" class="form-control"
                 value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Indirizzo di partenza</label>
          <input type="text" name="start_address" class="form-control"
                 placeholder="Via Roma 1, Milano">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
        <button type="submit" class="btn btn-primary">
          <i class="ri-route-line me-1"></i>Genera piano
        </button>
      </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
function showPlanDetail(plan) {
  // Popola header
  document.getElementById('modalPlanDate').textContent =
    new Date(plan.plan_date).toLocaleDateString('it-IT', {weekday:'long', day:'2-digit', month:'long', year:'numeric'})
    + ' — ' + plan.tech_name;

  // KPI
  const h = plan.total_duration_minutes ? Math.floor(plan.total_duration_minutes / 60) : null;
  const m = plan.total_duration_minutes ? plan.total_duration_minutes % 60 : null;
  document.getElementById('modalKpis').innerHTML = `
    <div class="col-4 text-center">
      <div class="fw-bold fs-4 text-primary">${countStops(plan.optimized_order)}</div>
      <div class="text-muted small">Tappe</div>
    </div>
    <div class="col-4 text-center">
      <div class="fw-bold fs-4 text-info">${plan.total_distance_km ? parseFloat(plan.total_distance_km).toFixed(1) + ' km' : '—'}</div>
      <div class="text-muted small">Distanza totale</div>
    </div>
    <div class="col-4 text-center">
      <div class="fw-bold fs-4 text-warning">${h !== null ? (h > 0 ? h + 'h ' : '') + m + 'min' : '—'}</div>
      <div class="text-muted small">Durata stimata</div>
    </div>
  `;

  // Tappe
  let stops = plan.optimized_order;
  if (typeof stops === 'string') {
    try { stops = JSON.parse(stops); } catch(e) { stops = []; }
  }

  const stopsEl = document.getElementById('modalStops');
  if (!stops || stops.length === 0) {
    stopsEl.innerHTML = '<p class="text-muted small text-center py-3">Nessuna tappa definita.</p>';
  } else {
    stopsEl.innerHTML = stops.map((stop, idx) => `
      <div class="d-flex align-items-start gap-3 mb-3">
        <div class="d-flex flex-column align-items-center">
          <span class="badge bg-primary rounded-circle" style="width:28px;height:28px;line-height:20px;font-size:.8rem">${idx + 1}</span>
          ${idx < stops.length - 1 ? '<div style="width:2px;height:24px;background:#dee2e6;margin:4px auto"></div>' : ''}
        </div>
        <div class="flex-grow-1 border rounded p-2">
          <div class="fw-semibold small">${stop.address || stop.label || ('Tappa ' + (idx + 1))}</div>
          ${stop.ticket_number ? `<div class="text-muted" style="font-size:.75rem"><i class="ri-ticket-line me-1"></i>${stop.ticket_number}</div>` : ''}
          ${stop.customer_name ? `<div class="text-muted" style="font-size:.75rem"><i class="ri-user-line me-1"></i>${stop.customer_name}</div>` : ''}
          ${stop.estimated_duration_minutes ? `<div class="text-muted" style="font-size:.75rem"><i class="ri-time-line me-1"></i>${stop.estimated_duration_minutes} min</div>` : ''}
        </div>
      </div>
    `).join('');
  }

  new bootstrap.Modal(document.getElementById('planDetailModal')).show();
}

function countStops(order) {
  if (!order) return 0;
  if (Array.isArray(order)) return order.length;
  try { return JSON.parse(order).length; } catch(e) { return 0; }
}

</script>
@endpush

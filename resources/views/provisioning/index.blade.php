@extends('layouts.contentNavbarLayout')

@section('title', 'Provisioning Carrier')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Provisioning Carrier</h4>
      <p class="text-muted mb-0">Gestione ordini di attivazione, modifica e disattivazione verso OpenFiber e FiberCop</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('provisioning.vlan-pool') }}" class="btn btn-outline-secondary">
        <i class="ri-stack-line me-1"></i>Pool VLAN
      </a>
      <a href="{{ route('provisioning.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nuovo ordine
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- KPI cards --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md flex-shrink-0" style="background:rgba(105,108,255,.15)">
            <i class="ri-signal-tower-line fs-4" style="color:#696cff"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold">{{ number_format($kpis->active) }}</div>
            <div class="text-muted small">Ordini attivi</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md flex-shrink-0" style="background:rgba(113,221,55,.15)">
            <i class="ri-file-edit-line fs-4" style="color:#71dd37"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold">{{ number_format($kpis->draft) }}</div>
            <div class="text-muted small">Bozze (da inviare)</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md flex-shrink-0" style="background:rgba(255,62,29,.15)">
            <i class="ri-close-circle-line fs-4" style="color:#ff3e1d"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold">{{ number_format($kpis->ko_count) }}</div>
            <div class="text-muted small">KO / Retry fallito</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md flex-shrink-0" style="background:rgba(3,195,236,.15)">
            <i class="ri-checkbox-circle-line fs-4" style="color:#03c3ec"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold">{{ number_format($kpis->completed_today) }}</div>
            <div class="text-muted small">Completati oggi</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small mb-1">Ricerca</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                 placeholder="Codice ordine, cliente…">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Stato</label>
          <select name="state" class="form-select form-select-sm">
            <option value="">Tutti</option>
            @foreach($states as $s)
              <option value="{{ $s->value }}" @selected(request('state') === $s->value)>
                {{ ucfirst(str_replace('_', ' ', $s->value)) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Carrier</label>
          <select name="carrier" class="form-select form-select-sm">
            <option value="">Tutti</option>
            @foreach($carriers as $car)
              <option value="{{ $car }}" @selected(request('carrier') === $car)>{{ ucfirst($car) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipo</label>
          <select name="order_type" class="form-select form-select-sm">
            <option value="">Tutti</option>
            @foreach($orderTypes as $t)
              <option value="{{ $t->value }}" @selected(request('order_type') === $t->value)>
                {{ ucfirst($t->value) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          @if(request()->anyFilled(['q','state','carrier','order_type']))
            <a href="{{ route('provisioning.index') }}" class="btn btn-outline-secondary btn-sm">
              <i class="ri-refresh-line"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Codice OLO</th>
            <th>Carrier</th>
            <th>Tipo</th>
            <th>Contratto / Cliente</th>
            <th>Stato</th>
            <th>Data piano</th>
            <th>Inviato il</th>
            <th>Retry</th>
            <th class="text-end">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
            <tr>
              <td>
                <a href="{{ route('provisioning.show', $order->id) }}" class="fw-semibold text-decoration-none font-monospace small">
                  {{ $order->codice_ordine_olo }}
                </a>
                @if($order->codice_ordine_of)
                  <br><span class="text-muted font-monospace" style="font-size:0.75rem">{{ $order->codice_ordine_of }}</span>
                @endif
              </td>
              <td>
                @php
                  $carrierColors = ['openfiber' => 'primary', 'fibercop' => 'warning', 'fastweb' => 'info'];
                  $cc = $carrierColors[$order->carrier] ?? 'secondary';
                @endphp
                <span class="badge bg-label-{{ $cc }}">{{ ucfirst($order->carrier) }}</span>
              </td>
              <td>
                @php
                  $typeIcons = ['activation' => 'ri-add-circle-line', 'deactivation' => 'ri-subtract-line', 'change' => 'ri-edit-line', 'migration' => 'ri-arrow-right-circle-line'];
                  $typeColors = ['activation' => 'success', 'deactivation' => 'danger', 'change' => 'warning', 'migration' => 'info'];
                  $tc = $typeColors[$order->order_type] ?? 'secondary';
                  $ti = $typeIcons[$order->order_type] ?? 'ri-circle-line';
                @endphp
                <span class="badge bg-label-{{ $tc }}">
                  <i class="{{ $ti }} me-1"></i>{{ ucfirst($order->order_type) }}
                </span>
              </td>
              <td>
                <a href="{{ route('contracts.show', $order->contract_id) }}" class="text-decoration-none small fw-semibold">
                  {{ $order->contract_number }}
                </a>
                <br>
                <span class="text-muted small">
                  {{ $order->company_name ?: $order->customer_name }}
                </span>
              </td>
              <td>
                @include('provisioning._state_badge', ['state' => $order->state])
                @if($order->last_error && in_array($order->state, ['ko','retry_failed']))
                  <i class="ri-information-line text-danger ms-1" data-bs-toggle="tooltip"
                     title="{{ Str::limit($order->last_error, 100) }}"></i>
                @endif
              </td>
              <td>
                @if($order->scheduled_date)
                  <span class="small">{{ \Carbon\Carbon::parse($order->scheduled_date)->format('d/m/Y H:i') }}</span>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
              <td>
                @if($order->sent_at)
                  <span class="small">{{ \Carbon\Carbon::parse($order->sent_at)->format('d/m/Y H:i') }}</span>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
              <td>
                @if($order->retry_count > 0)
                  <span class="badge bg-label-warning">{{ $order->retry_count }}/3</span>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <a href="{{ route('provisioning.show', $order->id) }}"
                     class="btn btn-sm btn-icon btn-outline-secondary" title="Dettaglio">
                    <i class="ri-eye-line"></i>
                  </a>
                  @if($order->state === 'draft')
                    <form method="POST" action="{{ route('provisioning.send', $order->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-primary" title="Invia al carrier"
                              onclick="return confirm('Inviare l\'ordine {{ $order->codice_ordine_olo }} al carrier?')">
                        <i class="ri-send-plane-line"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="ri-signal-tower-line fs-1 d-block mb-2 opacity-25"></i>
                Nessun ordine di provisioning trovato.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($orders->hasPages())
      <div class="card-footer">
        {{ $orders->links() }}
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });
</script>
@endpush

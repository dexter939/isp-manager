@extends('layouts.contentNavbarLayout')

@section('title', 'Allarmi di rete')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('monitoring.index') }}">Monitoraggio</a></li>
  <li class="breadcrumb-item active">Allarmi</li>
@endsection

@section('page-content')

  <x-page-header title="Allarmi di rete" subtitle="Monitoraggio eventi e anomalie" />

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-error-warning-fill" color="danger" label="Critici attivi" :value="$stats['critical'] ?? 0" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-alert-line" color="warning" label="Warning attivi" :value="$stats['warning'] ?? 0" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-shield-check-line" color="info" label="Soppressi" :value="$stats['suppressed'] ?? 0" />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Risolti oggi" :value="$stats['resolved_today'] ?? 0" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('monitoring.alerts')">
    <div class="col-12 col-sm-3">
      <label class="form-label small">Stato</label>
      <select name="state" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="active"     @selected(request('state') === 'active')>Attivo</option>
        <option value="suppressed" @selected(request('state') === 'suppressed')>Soppresso</option>
        <option value="resolved"   @selected(request('state') === 'resolved')>Risolto</option>
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Severità</label>
      <select name="severity" class="form-select form-select-sm">
        <option value="">Tutte</option>
        <option value="critical" @selected(request('severity') === 'critical')>Critica</option>
        <option value="warning"  @selected(request('severity') === 'warning')>Warning</option>
        <option value="info"     @selected(request('severity') === 'info')>Info</option>
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Device</label>
      <input type="text" name="device" class="form-control form-control-sm"
             placeholder="Hostname o IP..." value="{{ request('device') }}">
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Device</th>
              <th>Tipo allarme</th>
              <th>Severità</th>
              <th>Soppresso</th>
              <th>Iniziato il</th>
              <th>Durata</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($alerts ?? [] as $alert)
              <tr class="{{ $alert->severity === 'critical' && !$alert->suppressed ? 'table-danger' : '' }}">
                <td>
                  <span class="fw-medium">{{ $alert->device->hostname ?? '—' }}</span>
                  <br><small class="text-muted font-monospace">{{ $alert->device->ip_address ?? '' }}</small>
                </td>
                <td>{{ $alert->alert_type }}</td>
                <td>
                  @php
                    $sevColor = match($alert->severity ?? '') {
                      'critical' => 'danger',
                      'warning'  => 'warning',
                      default    => 'info',
                    };
                  @endphp
                  <span class="badge bg-{{ $sevColor }}">{{ ucfirst($alert->severity ?? 'info') }}</span>
                </td>
                <td>
                  @if($alert->suppressed)
                    <span class="badge bg-warning text-dark">Soppresso</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="small text-muted">{{ $alert->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="small">
                  @if($alert->started_at)
                    {{ $alert->started_at->diffForHumans(short: true) }}
                  @else
                    —
                  @endif
                </td>
                <td class="text-end">
                  @unless($alert->suppressed || ($alert->resolved_at ?? false))
                    <form method="POST" action="#" class="d-inline">
                      @csrf @method('PATCH')
                      <button class="btn btn-sm btn-outline-success" title="Acknowledgeack">
                        <i class="ri-check-line"></i>
                      </button>
                    </form>
                  @endunless
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun allarme trovato" icon="ri-shield-check-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($alerts) && $alerts->hasPages())
      <div class="card-footer">{{ $alerts->links() }}</div>
    @endif
  </div>

@endsection

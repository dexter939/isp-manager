@extends('layouts.contentNavbarLayout')

@section('title', 'Siti di rete')

@section('breadcrumb')
  <li class="breadcrumb-item">Infrastruttura</li>
  <li class="breadcrumb-item active">Siti di rete</li>
@endsection

@section('page-content')

  <x-page-header title="Siti di rete" subtitle="Gestione POP, torri e cabinet">
    <x-slot:action>
      <a href="#" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nuovo sito
      </a>
    </x-slot:action>
  </x-page-header>

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-map-pin-2-line" color="primary" label="Totale siti" :value="$stats['total'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Online" :value="$stats['online'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-error-warning-line" color="danger" label="Con problemi" :value="$stats['offline'] ?? 0" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('network.sites.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Nome sito, indirizzo..." value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Tipo</label>
      <select name="type" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="pop"        @selected(request('type') === 'pop')>POP</option>
        <option value="tower"      @selected(request('type') === 'tower')>Torre</option>
        <option value="cabinet"    @selected(request('type') === 'cabinet')>Cabinet</option>
        <option value="datacenter" @selected(request('type') === 'datacenter')>Datacenter</option>
      </select>
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="active"   @selected(request('status') === 'active')>Attivo</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inattivo</option>
        <option value="planned"  @selected(request('status') === 'planned')>Pianificato</option>
      </select>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nome sito</th>
              <th>Tipo</th>
              <th>Indirizzo</th>
              <th>Dispositivi</th>
              <th>Clienti</th>
              <th>Ultima modifica</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($sites ?? [] as $site)
              <tr>
                <td>
                  <span class="fw-medium">{{ $site->name }}</span>
                  @if($site->notes)
                    <br><small class="text-muted">{{ Str::limit($site->notes, 40) }}</small>
                  @endif
                </td>
                <td>
                  <span class="badge bg-light text-dark border">{{ strtoupper($site->type->value ?? $site->type) }}</span>
                </td>
                <td class="text-muted small">{{ $site->address ?? '—' }}</td>
                <td>
                  @php
                    $online  = $site->hardware_online  ?? 0;
                    $total   = $site->hardware_total   ?? 0;
                    $color   = $total > 0 && $online === $total ? 'success' : ($online > 0 ? 'warning' : 'secondary');
                  @endphp
                  <span class="badge bg-{{ $color }} rounded-pill">{{ $online }}/{{ $total }}</span>
                </td>
                <td>
                  <span class="badge bg-primary rounded-pill">{{ $site->clients_count ?? 0 }}</span>
                </td>
                <td class="text-muted small">{{ $site->updated_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('network.sites.show', $site) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun sito trovato" icon="ri-map-pin-2-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($sites) && $sites->hasPages())
      <div class="card-footer">{{ $sites->links() }}</div>
    @endif
  </div>

@endsection

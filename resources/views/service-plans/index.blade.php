@extends('layouts.contentNavbarLayout')

@section('title', 'Piani di servizio')

@section('breadcrumb')
  <li class="breadcrumb-item active">Piani di servizio</li>
@endsection

@section('page-content')

  <x-page-header title="Piani di servizio" subtitle="Gestione offerte commerciali e tariffe">
    <x-slot name="action">
      <a href="{{ route('service-plans.create') }}" class="btn btn-primary btn-sm">
        <i class="ri-add-line me-1"></i>Nuovo piano
      </a>
    </x-slot>
  </x-page-header>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-4">
      <x-kpi-card icon="ri-price-tag-3-line" color="primary" label="Piani totali"   :value="$stats['total']"    />
    </div>
    <div class="col-6 col-sm-4">
      <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Piani attivi" :value="$stats['active']"   />
    </div>
    <div class="col-6 col-sm-4">
      <x-kpi-card icon="ri-building-2-line" color="info"    label="Carrier"         :value="$stats['carriers']" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('service-plans.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Nome piano…" value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-3">
      <label class="form-label small">Carrier</label>
      <select name="carrier" class="form-select form-select-sm">
        <option value="">Tutti</option>
        @foreach($carriers as $c)
          <option value="{{ $c }}" @selected(request('carrier') === $c)>{{ strtoupper($c) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-6 col-sm-3 d-flex align-items-end">
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="active_only" value="1" id="activeOnly"
               @checked(request('active_only'))>
        <label class="form-check-label small" for="activeOnly">Solo attivi</label>
      </div>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nome piano</th>
              <th>Carrier</th>
              <th>Tecnologia</th>
              <th class="text-end">Banda (DL/UL)</th>
              <th class="text-end">Canone mensile</th>
              <th class="text-end">Attivazione</th>
              <th>SLA</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($plans ?? [] as $plan)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $plan->name }}</div>
                  @if($plan->description)
                    <div class="text-muted small text-truncate" style="max-width:200px">{{ $plan->description }}</div>
                  @endif
                </td>
                <td><span class="badge bg-label-secondary">{{ strtoupper($plan->carrier) }}</span></td>
                <td><span class="badge bg-label-info">{{ $plan->technology }}</span></td>
                <td class="text-end small font-monospace">
                  {{ $plan->bandwidth_dl }}/{{ $plan->bandwidth_ul }} Mbps
                </td>
                <td class="text-end fw-semibold">
                  € {{ number_format($plan->price_monthly, 2, ',', '.') }}
                </td>
                <td class="text-end small text-muted">
                  @if($plan->activation_fee > 0)
                    € {{ number_format($plan->activation_fee, 2, ',', '.') }}
                  @else
                    <span class="text-success">Gratis</span>
                  @endif
                </td>
                <td class="small text-muted">
                  {{ $plan->sla_type ?? 'Best effort' }}
                  @if($plan->mtr_hours)
                    <span class="text-muted">({{ $plan->mtr_hours }}h)</span>
                  @endif
                </td>
                <td>
                  <form method="POST" action="{{ route('service-plans.toggle', $plan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="badge border-0 bg-{{ $plan->is_active ? 'success' : 'secondary' }}"
                            style="cursor:pointer" title="Clicca per {{ $plan->is_active ? 'disattivare' : 'attivare' }}">
                      {{ $plan->is_active ? 'Attivo' : 'Inattivo' }}
                    </button>
                  </form>
                  @if(!$plan->is_public)
                    <span class="badge bg-label-warning ms-1">Privato</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('service-plans.edit', $plan->id) }}"
                     class="btn btn-sm btn-outline-primary me-1" title="Modifica">
                    <i class="ri-pencil-line"></i>
                  </a>
                  <form method="POST" action="{{ route('service-plans.destroy', $plan->id) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Eliminare il piano {{ $plan->name }}?" title="Elimina">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun piano di servizio trovato" icon="ri-price-tag-3-line" colspan="9" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($plans) && $plans->hasPages())
      <div class="card-footer">{{ $plans->links() }}</div>
    @endif
  </div>

@endsection

@extends('layouts.contentNavbarLayout')

@section('title', 'Contratti')

@section('breadcrumb')
  <li class="breadcrumb-item active">Contratti</li>
@endsection

@section('page-content')

  <x-page-header title="Contratti" subtitle="Gestione contratti di fornitura">
    <x-slot:action>
      <a href="{{ route('contracts.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nuovo contratto
      </a>
    </x-slot:action>
  </x-page-header>

  <x-filter-bar :resetRoute="route('contracts.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Nome, codice fiscale, ID..." value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="active"     @selected(request('status') === 'active')>Attivo</option>
        <option value="suspended"  @selected(request('status') === 'suspended')>Sospeso</option>
        <option value="terminated" @selected(request('status') === 'terminated')>Terminato</option>
        <option value="pending"    @selected(request('status') === 'pending')>In attesa</option>
      </select>
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Carrier</label>
      <select name="carrier" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="openfiber" @selected(request('carrier') === 'openfiber')>Open Fiber</option>
        <option value="fibercop"  @selected(request('carrier') === 'fibercop')>FiberCop</option>
        <option value="fwa"       @selected(request('carrier') === 'fwa')>FWA</option>
      </select>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Piano</th>
              <th>Carrier</th>
              <th>Stato</th>
              <th>Data attivazione</th>
              <th>Canone</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($contracts as $contract)
              <tr>
                <td class="text-muted small">{{ $contract->id }}</td>
                <td>
                  <a href="{{ route('customers.show', $contract->customer) }}" class="fw-medium text-body">
                    {{ $contract->customer->full_name }}
                  </a>
                  <br><small class="text-muted">{{ $contract->customer->codice_fiscale }}</small>
                </td>
                <td>{{ $contract->servicePlan->name }}</td>
                <td>
                  @if($contract->carrier)
                    <span class="badge bg-light text-dark border">{{ strtoupper($contract->carrier) }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <x-status-badge :status="$contract->status" />
                </td>
                <td>{{ $contract->activated_at?->format('d/m/Y') ?? '—' }}</td>
                <td>€ {{ number_format($contract->servicePlan->monthly_fee / 100, 2, ',', '.') }}</td>
                <td class="text-end">
                  <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun contratto trovato" icon="ri-file-text-line" colspan="8" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($contracts->hasPages())
      <div class="card-footer">
        {{ $contracts->links() }}
      </div>
    @endif
  </div>

@endsection

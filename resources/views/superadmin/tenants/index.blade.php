@extends('layouts.contentNavbarLayout')
@section('title', 'Gestione Tenant')

@section('breadcrumb')
  <li class="breadcrumb-item active">Super Admin — Tenant</li>
@endsection

@section('page-content')

  <x-page-header title="Gestione Tenant" subtitle="Tutti gli ISP registrati sulla piattaforma">
    <x-slot name="action">
      <a href="{{ route('superadmin.tenants.create') }}" class="btn btn-primary btn-sm">
        <i class="ri-building-4-line me-1"></i>Nuovo tenant
      </a>
    </x-slot>
  </x-page-header>

  {{-- Global stats --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <div class="card text-center">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-primary">{{ $tenants->count() }}</div>
          <div class="small text-muted">Tenant totali</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-success">{{ $tenants->where('is_active', true)->count() }}</div>
          <div class="small text-muted">Attivi</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-info">{{ $tenants->sum('user_count') }}</div>
          <div class="small text-muted">Utenti totali</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-warning">€ {{ number_format($tenants->sum('mrr') / 100, 0, ',', '.') }}</div>
          <div class="small text-muted">MRR aggregato</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Tenant</th>
              <th>Slug / Dominio</th>
              <th class="text-center">Utenti</th>
              <th class="text-center">Contratti attivi</th>
              <th class="text-end">MRR</th>
              <th class="text-center">Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tenants as $t)
              <tr class="{{ !$t->is_active ? 'table-secondary' : '' }}">
                <td>
                  <div class="fw-semibold">{{ $t->name }}</div>
                  <div class="small text-muted">ID #{{ $t->id }}</div>
                </td>
                <td class="small">
                  <code class="me-1">{{ $t->slug }}</code>
                  @if($t->domain)
                    <br><span class="text-muted">{{ $t->domain }}</span>
                  @endif
                </td>
                <td class="text-center">{{ $t->user_count }}</td>
                <td class="text-center">
                  <span class="fw-semibold">{{ $t->active_contracts }}</span>
                  <span class="text-muted small">/{{ $t->contract_count }}</span>
                </td>
                <td class="text-end fw-semibold">€ {{ number_format($t->mrr / 100, 0, ',', '.') }}</td>
                <td class="text-center">
                  @if($t->is_active)
                    <span class="badge bg-success">Attivo</span>
                  @else
                    <span class="badge bg-secondary">Sospeso</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    <a href="{{ route('superadmin.tenants.show', $t->id) }}"
                       class="btn btn-sm btn-outline-info" title="Dettaglio">
                      <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('superadmin.tenants.edit', $t->id) }}"
                       class="btn btn-sm btn-outline-primary" title="Modifica">
                      <i class="ri-pencil-line"></i>
                    </a>
                    @if($t->is_active)
                      <form method="POST" action="{{ route('superadmin.tenants.impersonate', $t->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Impersona">
                          <i class="ri-user-shared-line"></i>
                        </button>
                      </form>
                      <form method="POST" action="{{ route('superadmin.tenants.toggle', $t->id) }}"
                            onsubmit="return confirm('Sospendere il tenant {{ addslashes($t->name) }}?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sospendi">
                          <i class="ri-pause-circle-line"></i>
                        </button>
                      </form>
                    @else
                      <form method="POST" action="{{ route('superadmin.tenants.toggle', $t->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Riattiva">
                          <i class="ri-play-circle-line"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">Nessun tenant registrato.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection

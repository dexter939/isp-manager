@extends('layouts.contentNavbarLayout')

@section('title', $site->name ?? 'Sito di rete')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('network.sites.index') }}">Siti di rete</a></li>
  <li class="breadcrumb-item active">{{ $site->name }}</li>
@endsection

@section('page-content')

  <x-page-header :title="$site->name" :subtitle="'Tipo: ' . strtoupper($site->type->value ?? $site->type)">
    <x-slot:action>
      <x-status-badge :status="$site->status" />
    </x-slot:action>
  </x-page-header>

  <div class="row g-3">

    {{-- Info sito --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <i class="ri-information-line me-2"></i>Informazioni sito
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted small">Tipo</dt>
            <dd class="col-7 small">{{ strtoupper($site->type->value ?? $site->type) }}</dd>

            <dt class="col-5 text-muted small">Indirizzo</dt>
            <dd class="col-7 small">{{ $site->address ?? '—' }}</dd>

            <dt class="col-5 text-muted small">Coordinate</dt>
            <dd class="col-7 small font-monospace">
              @if($site->latitude && $site->longitude)
                {{ number_format($site->latitude, 6) }}, {{ number_format($site->longitude, 6) }}
              @else
                —
              @endif
            </dd>

            <dt class="col-5 text-muted small">Creato il</dt>
            <dd class="col-7 small">{{ $site->created_at?->format('d/m/Y') ?? '—' }}</dd>

            @if($site->notes)
              <dt class="col-5 text-muted small">Note</dt>
              <dd class="col-7 small">{{ $site->notes }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    {{-- Dispositivi --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between">
          <span><i class="ri-router-line me-2"></i>Dispositivi collegati</span>
          <span class="badge bg-primary rounded-pill">{{ $site->hardware?->count() ?? 0 }}</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:320px;overflow-y:auto">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Hostname</th>
                  <th>IP</th>
                  <th>Stato</th>
                </tr>
              </thead>
              <tbody>
                @forelse($site->hardware ?? [] as $hw)
                  <tr>
                    <td class="small fw-medium">{{ $hw->hostname }}</td>
                    <td class="small font-monospace text-muted">{{ $hw->ip_address }}</td>
                    <td>
                      <span class="badge bg-{{ $hw->is_online ? 'success' : 'secondary' }}">
                        {{ $hw->is_online ? 'Online' : 'Offline' }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-center text-muted py-3 small">Nessun dispositivo</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Clienti serviti --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between">
          <span><i class="ri-user-3-line me-2"></i>Clienti serviti</span>
          <span class="badge bg-primary rounded-pill">{{ $site->customerServices?->count() ?? 0 }}</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:320px;overflow-y:auto">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Cliente</th>
                  <th>Piano</th>
                </tr>
              </thead>
              <tbody>
                @forelse($site->customerServices ?? [] as $cs)
                  <tr>
                    <td class="small">
                      <a href="{{ route('customers.show', $cs->contract->customer_id ?? '#') }}" class="text-body fw-medium">
                        {{ $cs->contract->customer->full_name ?? '—' }}
                      </a>
                    </td>
                    <td class="small text-muted">{{ $cs->contract->servicePlan->name ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="2" class="text-center text-muted py-3 small">Nessun cliente</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

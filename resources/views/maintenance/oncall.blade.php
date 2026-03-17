@extends('layouts.contentNavbarLayout')

@section('title', 'Reperibilità')

@section('breadcrumb')
  <li class="breadcrumb-item">Assistenza</li>
  <li class="breadcrumb-item active">Reperibilità</li>
@endsection

@section('page-content')

  <x-page-header title="Reperibilità" subtitle="Gestione turni e dispatch allarmi" />

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6">
      <x-kpi-card icon="ri-user-voice-line" color="success" label="Reperibile ora"
        :value="$currentOnCall['name'] ?? 'Nessuno'" />
    </div>
    <div class="col-12 col-sm-6">
      <x-kpi-card icon="ri-time-line" color="info" label="Prossimo cambio turno"
        :value="$nextShift ?? '—'" />
    </div>
  </div>

  <div class="row g-3 mb-4">

    {{-- Calendario settimana --}}
    <div class="col-12 col-xl-7">
      <div class="card h-100">
        <div class="card-header">
          <i class="ri-calendar-line me-2"></i>Calendario reperibilità — settimana corrente
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 text-center">
              <thead class="table-light">
                <tr>
                  <th style="min-width:60px">Livello</th>
                  @foreach(['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $day)
                    <th>{{ $day }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($weekSchedule ?? [] as $level => $days)
                  <tr>
                    <td class="fw-semibold small">{{ strtoupper($level) }}</td>
                    @foreach($days as $techName)
                      <td class="small {{ $techName ? '' : 'text-muted' }}">
                        {{ $techName ?? '—' }}
                      </td>
                    @endforeach
                  </tr>
                @else
                  <tr>
                    <td colspan="8" class="text-center text-muted py-3 small">
                      Nessun turno configurato per questa settimana
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-end">
          <a href="#" class="btn btn-sm btn-outline-primary">
            <i class="ri-add-line me-1"></i>Aggiungi turno
          </a>
        </div>
      </div>
    </div>

    {{-- Ultimi dispatch --}}
    <div class="col-12 col-xl-5">
      <div class="card h-100">
        <div class="card-header">
          <i class="ri-send-plane-line me-2"></i>Ultimi dispatch
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tecnico</th>
                  <th>Canale</th>
                  <th>Stato</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentDispatches ?? [] as $dispatch)
                  <tr>
                    <td class="small fw-medium">{{ $dispatch->technician->name ?? '—' }}</td>
                    <td class="small text-muted">{{ $dispatch->channel ?? '—' }}</td>
                    <td><x-status-badge :status="$dispatch->status" /></td>
                    <td class="small text-muted">{{ $dispatch->created_at?->format('d/m H:i') ?? '—' }}</td>
                  </tr>
                @empty
                  <x-empty-state message="Nessun dispatch recente" icon="ri-send-plane-line" colspan="4" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

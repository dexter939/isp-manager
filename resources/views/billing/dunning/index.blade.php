@extends('layouts.contentNavbarLayout')

@section('title', 'Gestione Solleciti')

@section('breadcrumb')
  <li class="breadcrumb-item">Fatturazione</li>
  <li class="breadcrumb-item active">Solleciti</li>
@endsection

@section('page-content')

  <x-page-header title="Solleciti & Dunning" subtitle="Gestione politiche di recupero crediti automatico" />

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-loop-right-line" color="warning" label="Solleciti attivi"
                  :value="$stats['active'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-pause-circle-line" color="danger" label="Sospesi oggi"
                  :value="$stats['suspended'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-checkbox-circle-line" color="success" label="Risolti questo mese"
                  :value="$stats['resolved'] ?? 0" />
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Politiche di sollecito</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nome politica</th>
              <th>Giorni grazia</th>
              <th>Azioni configurate</th>
              <th>Contratti associati</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($policies ?? [] as $policy)
              <tr>
                <td class="fw-semibold">{{ $policy->name }}</td>
                <td class="text-center">{{ $policy->grace_days ?? '—' }}</td>
                <td class="small text-muted">
                  @php
                    $steps = is_string($policy->steps ?? null)
                        ? json_decode($policy->steps, true)
                        : ($policy->steps ?? []);
                    $stepCount = is_array($steps) ? count($steps) : 0;
                  @endphp
                  {{ $stepCount }} step
                  @if($stepCount > 0)
                    @php
                      $actions = collect($steps)->pluck('action')->unique()->implode(', ');
                    @endphp
                    <span class="text-muted">({{ $actions }})</span>
                  @endif
                </td>
                <td class="text-center">{{ $policy->contracts_count ?? '—' }}</td>
                <td>
                  @if(($policy->is_active ?? true))
                    <span class="badge bg-success">Attiva</span>
                  @else
                    <span class="badge bg-secondary">Inattiva</span>
                  @endif
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessuna politica di sollecito configurata" icon="ri-loop-right-line" colspan="5" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Runs attivi --}}
  <div class="card mt-4">
    <div class="card-header">
      <h5 class="mb-0">Solleciti in corso</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Cliente / Contratto</th>
              <th>Politica</th>
              <th>Step corrente</th>
              <th>Ultima azione</th>
              <th>Prossima azione</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($activeRuns ?? [] as $run)
              <tr>
                <td class="small">
                  <div class="fw-semibold">{{ $run->customer_full_name ?? '—' }}</div>
                  <div class="text-muted font-monospace">{{ $run->contract_code ?? '' }}</div>
                </td>
                <td class="small">{{ $run->policy_name ?? '—' }}</td>
                <td class="text-center">
                  <span class="badge bg-label-warning">Step {{ $run->current_step ?? '?' }}</span>
                </td>
                <td class="small text-muted">
                  {{ $run->last_action ? ucfirst($run->last_action) : '—' }}
                  @if($run->updated_at)
                    <div style="font-size:.7rem">{{ \Carbon\Carbon::parse($run->updated_at)->format('d/m H:i') }}</div>
                  @endif
                </td>
                <td class="small text-muted">
                  {{ $run->next_action_at ? \Carbon\Carbon::parse($run->next_action_at)->format('d/m/Y') : '—' }}
                </td>
                <td>
                  @php
                    $runStatus = $run->status ?? 'running';
                    $sc = match($runStatus) {
                      'running' => 'warning', 'resolved' => 'success', 'failed' => 'danger', default => 'secondary'
                    };
                  @endphp
                  <span class="badge bg-{{ $sc }}">{{ ucfirst($runStatus) }}</span>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun sollecito attivo" icon="ri-checkbox-circle-line" colspan="6" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection

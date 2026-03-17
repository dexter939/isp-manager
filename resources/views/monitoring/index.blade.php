@extends('layouts.contentNavbarLayout')

@section('title', 'Monitoraggio')

@section('breadcrumb')
  <li class="breadcrumb-item active">Monitoraggio</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Monitoraggio rete</h4>
    <p class="text-muted mb-0">BTS stations, allarmi e test di linea</p>
  </div>

  {{-- Alert summary --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="card text-center border-danger">
        <div class="card-body">
          <div class="fs-2 fw-bold text-danger">{{ $alertCounts['critical'] ?? 0 }}</div>
          <div class="text-muted small">Critici</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center border-warning">
        <div class="card-body">
          <div class="fs-2 fw-bold text-warning">{{ $alertCounts['warning'] ?? 0 }}</div>
          <div class="text-muted small">Warning</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center border-info">
        <div class="card-body">
          <div class="fs-2 fw-bold text-info">{{ $alertCounts['info'] ?? 0 }}</div>
          <div class="text-muted small">Informativi</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-success">{{ $btsCount ?? 0 }}</div>
          <div class="text-muted small">BTS attive</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- Active alerts --}}
    <div class="col-12 col-xl-7">
      <div class="card">
        <div class="card-header">Allarmi attivi</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Severità</th>
                  <th>BTS / CPE</th>
                  <th>Tipo</th>
                  <th>Messaggio</th>
                  <th>Da</th>
                </tr>
              </thead>
              <tbody>
                @forelse($alerts ?? [] as $alert)
                  <tr>
                    <td>
                      <span class="badge bg-{{ match($alert->severity) {
                        'critical' => 'danger',
                        'warning'  => 'warning',
                        default    => 'info'
                      } }}">{{ ucfirst($alert->severity) }}</span>
                    </td>
                    <td class="small">{{ $alert->details['bts_name'] ?? $alert->details['cpe_mac'] ?? '—' }}</td>
                    <td class="small text-muted">{{ $alert->alert_type }}</td>
                    <td class="small">{{ Str::limit($alert->message, 60) }}</td>
                    <td class="small text-muted">{{ $alert->created_at->diffForHumans() }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-success py-3">
                    <i class="ri-shield-check-line me-1"></i>Nessun allarme attivo
                  </td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- BTS stations --}}
    <div class="col-12 col-xl-5">
      <div class="card">
        <div class="card-header">Stazioni BTS</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th>IP</th>
                  <th>Stato</th>
                  <th>Ultimo poll</th>
                </tr>
              </thead>
              <tbody>
                @forelse($btsStations ?? [] as $bts)
                  <tr>
                    <td class="fw-medium">{{ $bts->name }}</td>
                    <td class="font-monospace small">{{ $bts->ip_address ?? '—' }}</td>
                    <td>
                      <span class="badge bg-{{ $bts->is_active ? 'success' : 'secondary' }}">
                        {{ $bts->is_active ? 'Attiva' : 'Inattiva' }}
                      </span>
                    </td>
                    <td class="small text-muted">{{ $bts->last_polled_at?->diffForHumans() ?? 'Mai' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">Nessuna BTS configurata</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

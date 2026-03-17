@extends('layouts.contentNavbarLayout')

@section('title', 'RADIUS / PPPoE')

@section('breadcrumb')
  <li class="breadcrumb-item active">RADIUS / PPPoE</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Gestione RADIUS / PPPoE</h4>
    <p class="text-muted mb-0">Sessioni attive, utenti e accounting</p>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-primary">{{ number_format($stats['active_sessions'] ?? 0) }}</div>
          <div class="text-muted small">Sessioni attive</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-success">{{ number_format($stats['radius_users'] ?? 0) }}</div>
          <div class="text-muted small">Utenti RADIUS</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-warning">{{ number_format($stats['walled_garden'] ?? 0) }}</div>
          <div class="text-muted small">Walled Garden</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-info">{{ number_format($stats['coa_sent'] ?? 0) }}</div>
          <div class="text-muted small">CoA inviati oggi</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Active sessions --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Sessioni PPPoE attive</span>
      <small class="text-muted">Aggiornato {{ now()->format('H:i:s') }}</small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Username</th>
              <th>IP Assegnato</th>
              <th>NAS IP</th>
              <th>Sessione</th>
              <th class="text-end">↓ RX</th>
              <th class="text-end">↑ TX</th>
              <th>Inizio</th>
            </tr>
          </thead>
          <tbody>
            @forelse($activeSessions ?? [] as $session)
              <tr>
                <td class="font-monospace small">{{ $session->username }}</td>
                <td class="font-monospace small">{{ $session->framed_ip_address ?? '—' }}</td>
                <td class="font-monospace small text-muted">{{ $session->nas_ip_address }}</td>
                <td class="font-monospace small text-muted">{{ $session->acct_session_id }}</td>
                <td class="text-end small">{{ number_format(($session->acct_input_octets ?? 0) / 1024 / 1024, 1) }} MB</td>
                <td class="text-end small">{{ number_format(($session->acct_output_octets ?? 0) / 1024 / 1024, 1) }} MB</td>
                <td class="small text-muted">{{ $session->acct_start_time?->format('d/m H:i') ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-3">Nessuna sessione attiva</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($activeSessions) && $activeSessions->hasPages())
      <div class="card-footer">{{ $activeSessions->links() }}</div>
    @endif
  </div>

@endsection

@extends('layouts.contentNavbarLayout')

@section('title', 'CPE — ' . ($device->serial_number ?? $device->id))

@section('breadcrumb')
  <li class="breadcrumb-item">Monitoring</li>
  <li class="breadcrumb-item"><a href="{{ route('monitoring.cpe.index') }}">CPE / ACS</a></li>
  <li class="breadcrumb-item active">{{ $device->serial_number ?? $device->id }}</li>
@endsection

@section('page-content')

  <x-page-header :title="($device->model ?? 'CPE') . ' — ' . ($device->serial_number ?? $device->id)">
    <x-slot name="action">
      @php $isOnline = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gt(now()->subMinutes(15)); @endphp
      @if($isOnline)
        <span class="badge bg-success fs-6 px-3 py-2"><i class="ri-wifi-line me-1"></i>Online</span>
      @else
        <span class="badge bg-danger fs-6 px-3 py-2"><i class="ri-wifi-off-line me-1"></i>Offline</span>
      @endif
    </x-slot>
  </x-page-header>

  <div class="row g-4">

    {{-- ── Colonna sinistra: info + azioni ACS ─── --}}
    <div class="col-12 col-lg-4">

      {{-- Info dispositivo --}}
      <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Informazioni dispositivo</h6></div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Modello</dt>
            <dd class="col-7">{{ $device->model ?? '—' }}</dd>

            <dt class="col-5 text-muted">Produttore</dt>
            <dd class="col-7">{{ $device->manufacturer ?? '—' }}</dd>

            <dt class="col-5 text-muted">Seriale</dt>
            <dd class="col-7 font-monospace">{{ $device->serial_number ?? '—' }}</dd>

            <dt class="col-5 text-muted">MAC</dt>
            <dd class="col-7 font-monospace">{{ $device->mac_address ?? '—' }}</dd>

            <dt class="col-5 text-muted">Tecnologia</dt>
            <dd class="col-7">{{ strtoupper($device->technology ?? '—') }}</dd>

            <dt class="col-5 text-muted">Firmware</dt>
            <dd class="col-7">{{ $device->firmware_version ?? '—' }}</dd>

            <dt class="col-5 text-muted">WAN IP</dt>
            <dd class="col-7 font-monospace">{{ $device->wan_ip ?? '—' }}</dd>

            <dt class="col-5 text-muted">LAN IP</dt>
            <dd class="col-7 font-monospace">{{ $device->lan_ip ?? '—' }}</dd>

            <dt class="col-5 text-muted">Ultimo seen</dt>
            <dd class="col-7">
              {{ $device->last_seen_at ? \Carbon\Carbon::parse($device->last_seen_at)->format('d/m/Y H:i') : '—' }}
            </dd>
          </dl>
        </div>
      </div>

      {{-- Info ACS --}}
      @if($device->tr069_id)
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">ACS — TR-069</h6>
          <span class="badge bg-info">GenieACS</span>
        </div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">TR-069 ID</dt>
            <dd class="col-7 font-monospace text-break">{{ $device->tr069_id }}</dd>

            <dt class="col-5 text-muted">Inform IP</dt>
            <dd class="col-7 font-monospace">{{ $device->tr069_inform_ip ?? '—' }}</dd>

            <dt class="col-5 text-muted">Ultimo Inform</dt>
            <dd class="col-7">
              {{ $device->tr069_last_inform ? \Carbon\Carbon::parse($device->tr069_last_inform)->format('d/m/Y H:i') : '—' }}
            </dd>
          </dl>

          {{-- Azioni ACS --}}
          <hr class="my-3">
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="acsAction('{{ $device->id }}', 'refresh')"
                    title="Aggiorna parametri dal CPE">
              <i class="ri-refresh-line me-1"></i>Aggiorna parametri
            </button>
            <button type="button" class="btn btn-sm btn-outline-info"
                    onclick="acsAction('{{ $device->id }}', 'firmware')"
                    title="Leggi versione firmware">
              <i class="ri-file-code-line me-1"></i>Leggi firmware
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning"
                    data-confirm="Riavviare il CPE {{ $device->serial_number }}?"
                    onclick="acsReboot('{{ $device->id }}')"
                    title="Riavvia il CPE via TR-069">
              <i class="ri-restart-line me-1"></i>Reboot CPE
            </button>
          </div>
          <div id="acs-status" class="mt-2 small text-muted d-none"></div>
        </div>
      </div>
      @else
      <div class="card mb-4">
        <div class="card-body text-center text-muted py-4">
          <i class="ri-settings-3-line fs-2 d-block mb-2"></i>
          <div class="small">Nessun TR-069 ID configurato.<br>Il dispositivo non è gestito via ACS.</div>
        </div>
      </div>
      @endif

      {{-- Cliente --}}
      @if($device->customer_id)
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Cliente</h6></div>
        <div class="card-body small">
          <div class="fw-semibold">{{ $device->customer_full_name }}</div>
          @if($device->contract_code)
            <div class="text-muted">Contratto: <a href="#">{{ $device->contract_code }}</a></div>
          @endif
          <a href="{{ route('customers.show', $device->customer_id) }}" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="ri-user-line me-1"></i>Vai al cliente
          </a>
        </div>
      </div>
      @endif

    </div>

    {{-- ── Colonna destra: parametri TR-069 + alert ─── --}}
    <div class="col-12 col-lg-8">

      {{-- Parametri TR-069 --}}
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Parametri TR-069</h6>
          <span class="badge bg-label-secondary">{{ $tr069Params->count() }} parametri</span>
        </div>
        <div class="card-body p-0">
          @if($tr069Params->isEmpty())
            <div class="text-center text-muted py-5">
              <i class="ri-list-unordered fs-2 d-block mb-2"></i>
              <div class="small">Nessun parametro salvato.<br>
                @if($device->tr069_id)
                  Clicca "Aggiorna parametri" per recuperarli dal CPE.
                @else
                  Configura prima un TR-069 ID.
                @endif
              </div>
            </div>
          @else
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
              <table class="table table-sm mb-0">
                <thead class="table-light sticky-top">
                  <tr>
                    <th>Parametro</th>
                    <th>Valore</th>
                    <th>Tipo</th>
                    <th>Aggiornato</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($tr069Params as $param)
                    <tr>
                      <td class="font-monospace small text-break" style="max-width:320px">
                        {{ $param->parameter_path }}
                      </td>
                      <td class="small">{{ $param->value ?? '—' }}</td>
                      <td class="small text-muted">{{ $param->type ?? '—' }}</td>
                      <td class="small text-muted text-nowrap">
                        {{ $param->fetched_at ? \Carbon\Carbon::parse($param->fetched_at)->format('d/m H:i') : '—' }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      {{-- Alert recenti --}}
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Alert recenti</h6></div>
        <div class="card-body p-0">
          @if($recentAlerts->isEmpty())
            <div class="text-center text-muted py-4 small">Nessun alert registrato per questo dispositivo.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Severità</th>
                    <th>Messaggio</th>
                    <th>Inizio</th>
                    <th>Stato</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentAlerts as $alert)
                    <tr>
                      <td>
                        @php
                          $sColor = match($alert->severity ?? 'info') {
                            'critical' => 'danger', 'warning' => 'warning', default => 'secondary'
                          };
                        @endphp
                        <span class="badge bg-{{ $sColor }}">{{ ucfirst($alert->severity ?? '—') }}</span>
                      </td>
                      <td class="small">{{ $alert->message ?? '—' }}</td>
                      <td class="small text-muted text-nowrap">
                        {{ $alert->started_at ? \Carbon\Carbon::parse($alert->started_at)->format('d/m/Y H:i') : '—' }}
                      </td>
                      <td>
                        @if($alert->resolved_at)
                          <span class="badge bg-success">Risolto</span>
                        @else
                          <span class="badge bg-warning text-dark">Aperto</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

    </div>
  </div>

@endsection

@push('scripts')
<script>
const deviceId = '{{ $device->id }}';

async function acsAction(id, action) {
  const statusEl = document.getElementById('acs-status');
  statusEl.classList.remove('d-none');
  statusEl.textContent = 'Richiesta inviata…';

  const urls = {
    refresh:  `/api/v1/cpe/${id}/tr069/refresh`,
    firmware: `/api/v1/cpe/${id}/tr069/refresh`,
  };

  const bodies = {
    refresh:  { parameters: ['Device.DeviceInfo.SoftwareVersion', 'Device.LAN.IPAddress', 'Device.WAN.ExternalIPAddress'] },
    firmware: { parameters: ['Device.DeviceInfo.SoftwareVersion', 'InternetGatewayDevice.DeviceInfo.SoftwareVersion'] },
  };

  try {
    const res = await window.apiFetch(urls[action], {
      method: 'POST',
      body: JSON.stringify(bodies[action]),
    });
    const json = await res.json();
    statusEl.textContent = res.ok ? 'Completato — ricarica la pagina per vedere i valori aggiornati.' : (json.message ?? 'Errore.');
    statusEl.className = 'mt-2 small ' + (res.ok ? 'text-success' : 'text-danger');
  } catch (e) {
    statusEl.textContent = 'Errore di rete.';
    statusEl.className = 'mt-2 small text-danger';
  }
}

async function acsReboot(id) {
  if (!confirm('Riavviare il CPE via TR-069?')) return;
  const statusEl = document.getElementById('acs-status');
  statusEl.classList.remove('d-none');
  statusEl.textContent = 'Reboot inviato…';

  try {
    const res = await window.apiFetch(`/api/v1/cpe/${id}/tr069/reboot`, { method: 'POST' });
    const json = await res.json();
    statusEl.textContent = res.ok ? 'Reboot accodato su GenieACS.' : (json.message ?? 'Errore.');
    statusEl.className = 'mt-2 small ' + (res.ok ? 'text-success' : 'text-danger');
  } catch (e) {
    statusEl.textContent = 'Errore di rete.';
    statusEl.className = 'mt-2 small text-danger';
  }
}
</script>
@endpush

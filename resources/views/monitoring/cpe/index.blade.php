@extends('layouts.contentNavbarLayout')

@section('title', 'Gestione CPE / ACS')

@section('breadcrumb')
  <li class="breadcrumb-item">Monitoring</li>
  <li class="breadcrumb-item active">CPE / ACS</li>
@endsection

@section('page-content')

  <x-page-header title="Gestione CPE / ACS" subtitle="Dispositivi Customer Premises Equipment gestiti via TR-069 / GenieACS">
    <x-slot name="action">
      <span class="badge bg-label-info fs-6 px-3 py-2">
        <i class="ri-router-line me-1"></i>GenieACS
      </span>
    </x-slot>
  </x-page-header>

  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-router-line"       color="primary" label="Totale CPE"     :value="$stats['total']"    />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-wifi-line"          color="success" label="Online"         :value="$stats['online']"   />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-wifi-off-line"      color="danger"  label="Offline"        :value="$stats['offline']"  />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-settings-3-line"    color="info"    label="Con ACS (TR-069)" :value="$stats['with_acs']" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('monitoring.cpe.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Serial, MAC, modello, TR-069 ID…" value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="online"  @selected(request('status') === 'online')>Online</option>
        <option value="offline" @selected(request('status') === 'offline')>Offline</option>
      </select>
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">ACS (TR-069)</label>
      <select name="has_acs" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="1" @selected(request('has_acs') === '1')>Con ACS</option>
        <option value="0" @selected(request('has_acs') === '0')>Senza ACS</option>
      </select>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Dispositivo</th>
              <th>Cliente</th>
              <th>TR-069 ID</th>
              <th>WAN IP</th>
              <th>Firmware</th>
              <th>Ultimo Inform</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($devices ?? [] as $device)
              @php
                $isOnline   = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gt(now()->subMinutes(15));
                $hasAcs     = !empty($device->tr069_id);
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold small">{{ $device->model ?? '—' }}</div>
                  <div class="text-muted font-monospace" style="font-size:.75rem">
                    {{ $device->serial_number ?? '—' }}
                    @if($device->mac_address)
                      · {{ $device->mac_address }}
                    @endif
                  </div>
                </td>
                <td class="small">
                  @if($device->customer_id)
                    <a href="{{ route('customers.show', $device->customer_id) }}" class="text-body">
                      {{ $device->customer_full_name }}
                    </a>
                    @if($device->contract_code)
                      <div class="text-muted" style="font-size:.75rem">{{ $device->contract_code }}</div>
                    @endif
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="font-monospace small">
                  @if($hasAcs)
                    <span class="text-success">{{ $device->tr069_id }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="font-monospace small text-muted">{{ $device->wan_ip ?? '—' }}</td>
                <td class="small text-muted">{{ $device->firmware_version ?? '—' }}</td>
                <td class="small text-muted">
                  {{ $device->tr069_last_inform ? \Carbon\Carbon::parse($device->tr069_last_inform)->format('d/m/Y H:i') : '—' }}
                </td>
                <td>
                  @if($isOnline)
                    <span class="badge bg-success">Online</span>
                  @else
                    <span class="badge bg-danger">Offline</span>
                  @endif
                  @if($hasAcs)
                    <span class="badge bg-info ms-1">ACS</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('monitoring.cpe.show', $device->id) }}"
                     class="btn btn-sm btn-outline-primary" title="Dettaglio / ACS">
                    <i class="ri-settings-3-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun dispositivo CPE trovato" icon="ri-router-line" colspan="8" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($devices) && $devices->hasPages())
      <div class="card-footer">{{ $devices->links() }}</div>
    @endif
  </div>

@endsection

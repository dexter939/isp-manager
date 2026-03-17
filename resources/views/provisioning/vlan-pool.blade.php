@extends('layouts.contentNavbarLayout')

@section('title', 'Pool VLAN')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Pool VLAN</h4>
      <p class="text-muted mb-0">Gestione VLAN assegnate ai carrier — C-VLAN e S-VLAN</p>
    </div>
    <a href="{{ route('provisioning.index') }}" class="btn btn-outline-secondary">
      <i class="ri-arrow-left-line me-1"></i>Ordini
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Summary cards per carrier --}}
  @php
    $summaryByCarrier = $summary->groupBy('carrier');
    $carrierColors = ['openfiber' => 'primary', 'fibercop' => 'warning', 'fastweb' => 'info'];
  @endphp

  <div class="row g-3 mb-4">
    @foreach($carriers as $carrier)
      @php $rows = $summaryByCarrier[$carrier] ?? collect(); @endphp
      <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-3 d-flex align-items-center gap-2">
            <span class="badge bg-{{ $carrierColors[$carrier] ?? 'secondary' }} rounded-pill">
              {{ strtoupper($carrier) }}
            </span>
            <h6 class="mb-0 fw-semibold">{{ ucfirst($carrier) }}</h6>
          </div>
          <div class="card-body">
            @forelse($rows as $row)
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small fw-semibold">{{ $row->vlan_type }}</span>
                  <span class="small text-muted">
                    {{ number_format($row->free_count) }} libere / {{ number_format($row->total) }} totali
                  </span>
                </div>
                @php
                  $pct = $row->total > 0 ? round(($row->assigned_count / $row->total) * 100) : 0;
                  $barColor = $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'success');
                @endphp
                <div class="progress mb-1" style="height:6px">
                  <div class="progress-bar bg-{{ $barColor }}" style="width:{{ $pct }}%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem">
                  <span><span class="text-success fw-semibold">{{ number_format($row->free_count) }}</span> libere</span>
                  <span><span class="text-warning fw-semibold">{{ number_format($row->reserved_count) }}</span> riservate</span>
                  <span><span class="text-primary fw-semibold">{{ number_format($row->assigned_count) }}</span> assegnate</span>
                </div>
              </div>
            @empty
              <p class="text-muted small mb-0">Nessuna VLAN configurata.</p>
            @endforelse
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Carrier</label>
          <select name="carrier" class="form-select form-select-sm">
            <option value="">Tutti</option>
            @foreach($carriers as $car)
              <option value="{{ $car }}" @selected(request('carrier') === $car)>{{ ucfirst($car) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipo VLAN</label>
          <select name="vlan_type" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="C-VLAN" @selected(request('vlan_type') === 'C-VLAN')>C-VLAN</option>
            <option value="S-VLAN" @selected(request('vlan_type') === 'S-VLAN')>S-VLAN</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Stato</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="free"     @selected(request('status') === 'free')>Libere</option>
            <option value="assigned" @selected(request('status') === 'assigned')>Assegnate</option>
            <option value="reserved" @selected(request('status') === 'reserved')>Riservate</option>
          </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          @if(request()->anyFilled(['carrier','vlan_type','status']))
            <a href="{{ route('provisioning.vlan-pool') }}" class="btn btn-outline-secondary btn-sm">
              <i class="ri-refresh-line"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- VLAN table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Carrier</th>
            <th>Tipo</th>
            <th>VLAN ID</th>
            <th>Stato</th>
            <th>Contratto</th>
            <th>Cliente</th>
            <th>Assegnata il</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          @forelse($vlans as $v)
            <tr>
              <td>
                <span class="badge bg-label-{{ $carrierColors[$v->carrier] ?? 'secondary' }}">
                  {{ ucfirst($v->carrier) }}
                </span>
              </td>
              <td><span class="badge bg-label-secondary font-monospace">{{ $v->vlan_type }}</span></td>
              <td><span class="fw-bold font-monospace">{{ $v->vlan_id }}</span></td>
              <td>
                @php
                  $statusColor = ['free' => 'success', 'assigned' => 'primary', 'reserved' => 'warning'];
                  $sc = $statusColor[$v->status] ?? 'secondary';
                @endphp
                <span class="badge bg-label-{{ $sc }}">{{ ucfirst($v->status) }}</span>
              </td>
              <td>
                @if($v->contract_number)
                  <a href="{{ route('contracts.show', $v->contract_id ?? 0) }}" class="text-decoration-none small">
                    {{ $v->contract_number }}
                  </a>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
              <td>
                <span class="small">{{ $v->company_name ?: $v->customer_name ?: '—' }}</span>
              </td>
              <td>
                <span class="small">
                  {{ $v->assigned_at ? \Carbon\Carbon::parse($v->assigned_at)->format('d/m/Y') : '—' }}
                </span>
              </td>
              <td class="text-muted small">{{ $v->notes ? \Str::limit($v->notes, 40) : '' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="ri-stack-line fs-1 d-block mb-2 opacity-25"></i>
                Nessuna VLAN trovata con i filtri selezionati.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($vlans->hasPages())
      <div class="card-footer">
        {{ $vlans->links() }}
      </div>
    @endif
  </div>

</div>
@endsection

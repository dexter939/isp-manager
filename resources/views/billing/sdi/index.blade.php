@extends('layouts.contentNavbarLayout')
@section('title', 'Fatturazione Elettronica SDI')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Fatturazione Elettronica</h4>
      <p class="text-muted mb-0">Trasmissioni FatturaPA al Sistema di Interscambio (SDI)</p>
    </div>
    <a href="{{ route('billing.sdi.batch') }}" class="btn btn-primary">
      <i class="ri-send-plane-line me-1"></i>Trasmetti fatture
      @if($toTransmitCount > 0)
        <span class="badge bg-white text-primary ms-1">{{ $toTransmitCount }}</span>
      @endif
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
      <i class="ri-alert-line me-1"></i>{{ session('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- KPI cards --}}
  <div class="row g-3 mb-4">
    @php
      $kpiItems = [
        ['label' => 'Da trasmettere', 'value' => $toTransmitCount,          'color' => '#696cff', 'icon' => 'ri-timer-line'],
        ['label' => 'In attesa/Inviate', 'value' => $kpis->pending_count + $kpis->sent_count, 'color' => '#03c3ec', 'icon' => 'ri-send-plane-line'],
        ['label' => 'Consegnate',     'value' => $kpis->delivered_count,    'color' => '#ffab00', 'icon' => 'ri-mail-check-line'],
        ['label' => 'Accettate',      'value' => $kpis->accepted_count,     'color' => '#71dd37', 'icon' => 'ri-checkbox-circle-line'],
        ['label' => 'Rifiutate',      'value' => $kpis->rejected_count,     'color' => '#ff3e1d', 'icon' => 'ri-close-circle-line'],
        ['label' => 'Errori',         'value' => $kpis->error_count,        'color' => '#ff3e1d', 'icon' => 'ri-error-warning-line'],
      ];
    @endphp
    @foreach($kpiItems as $k)
      <div class="col-6 col-md-2">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex align-items-center gap-2 p-3">
            <div class="avatar avatar-sm flex-shrink-0" style="background:{{ $k['color'] }}1a">
              <i class="{{ $k['icon'] }}" style="color:{{ $k['color'] }}"></i>
            </div>
            <div>
              <div class="fs-5 fw-bold lh-1">{{ number_format($k['value']) }}</div>
              <div class="text-muted" style="font-size:.7rem">{{ $k['label'] }}</div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Stato</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="pending"   @selected(request('status') === 'pending')>In attesa</option>
            <option value="sent"      @selected(request('status') === 'sent')>Inviata</option>
            <option value="delivered" @selected(request('status') === 'delivered')>Consegnata</option>
            <option value="accepted"  @selected(request('status') === 'accepted')>Accettata</option>
            <option value="rejected"  @selected(request('status') === 'rejected')>Rifiutata</option>
            <option value="error"     @selected(request('status') === 'error')>Errore</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Canale</label>
          <select name="channel" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="aruba" @selected(request('channel') === 'aruba')>Aruba</option>
            <option value="pec"   @selected(request('channel') === 'pec')>PEC</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Dal</label>
          <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Al</label>
          <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          @if(request()->anyFilled(['status','channel','from','to']))
            <a href="{{ route('billing.sdi.index') }}" class="btn btn-outline-secondary btn-sm">
              <i class="ri-refresh-line"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Fattura</th>
            <th>Cliente</th>
            <th>Filename SDI</th>
            <th>Canale</th>
            <th>Stato</th>
            <th>Cod. notifica</th>
            <th>Retry</th>
            <th>Inviata il</th>
            <th class="text-end">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transmissions as $tx)
            <tr>
              <td>
                <a href="{{ route('billing.invoices.show', $tx->invoice_id) }}" class="fw-semibold text-decoration-none small">
                  {{ $tx->invoice_number }}
                </a>
                <br>
                <span class="text-muted" style="font-size:.72rem">
                  € {{ number_format($tx->total / 100, 2, ',', '.') }}
                  · {{ \Carbon\Carbon::parse($tx->issue_date)->format('d/m/Y') }}
                </span>
              </td>
              <td class="small">{{ $tx->company_name ?: $tx->customer_name }}</td>
              <td>
                <span class="font-monospace small">{{ $tx->filename ?? '—' }}</span>
              </td>
              <td>
                <span class="badge bg-label-{{ $tx->channel === 'aruba' ? 'primary' : 'info' }}">
                  {{ strtoupper($tx->channel) }}
                </span>
              </td>
              <td>
                @include('billing.sdi._status_badge', ['status' => $tx->status])
                @if($tx->last_error && $tx->status === 'error')
                  <i class="ri-information-line text-danger ms-1" data-bs-toggle="tooltip"
                     title="{{ Str::limit($tx->last_error, 100) }}"></i>
                @endif
              </td>
              <td>
                @if($tx->notification_code)
                  @include('billing.sdi._notification_badge', ['code' => $tx->notification_code])
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
              <td>
                @if($tx->retry_count > 0)
                  <span class="badge bg-label-warning small">{{ $tx->retry_count }}/{{ config('sdi.max_retries', 3) }}</span>
                @else
                  <span class="text-muted small">0</span>
                @endif
              </td>
              <td class="small text-muted">
                {{ $tx->sent_at ? \Carbon\Carbon::parse($tx->sent_at)->format('d/m/Y H:i') : '—' }}
              </td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <a href="{{ route('billing.sdi.show', $tx->id) }}"
                     class="btn btn-sm btn-icon btn-outline-secondary" title="Dettaglio">
                    <i class="ri-eye-line"></i>
                  </a>
                  @if(! in_array($tx->status, ['accepted','rejected']) && $tx->retry_count < config('sdi.max_retries', 3))
                    <form method="POST" action="{{ route('billing.sdi.retry', $tx->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-warning" title="Ritrasmetti"
                              onclick="return confirm('Ritrasmettere questa fattura all\'SDI?')">
                        <i class="ri-restart-line"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="ri-file-transfer-line fs-1 d-block mb-2 opacity-25"></i>
                Nessuna trasmissione trovata.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($transmissions->hasPages())
      <div class="card-footer">{{ $transmissions->links() }}</div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush

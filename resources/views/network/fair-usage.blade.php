@extends('layouts.contentNavbarLayout')

@section('title', 'Fair Usage Policy')

@section('breadcrumb')
  <li class="breadcrumb-item">Rete</li>
  <li class="breadcrumb-item active">Fair Usage Policy</li>
@endsection

@section('page-content')

  <x-page-header title="Fair Usage Policy" subtitle="Monitoraggio consumo dati e soglie FUP" />

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-wifi-line" color="success" label="Utenti in cap normale" :value="$stats['normal'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-speed-line" color="warning" label="Utenti in throttling" :value="$stats['throttling'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-signal-wifi-error-line" color="danger" label="Cap esaurito" :value="$stats['exhausted'] ?? 0" />
    </div>
  </div>

  <x-filter-bar :resetRoute="route('network.fair-usage.index')">
    <div class="col-12 col-sm-3">
      <label class="form-label small">Stato FUP</label>
      <select name="fup_status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="normal"     @selected(request('fup_status') === 'normal')>Normale</option>
        <option value="throttling" @selected(request('fup_status') === 'throttling')>Throttling</option>
        <option value="exhausted"  @selected(request('fup_status') === 'exhausted')>Cap esaurito</option>
      </select>
    </div>
    <div class="col-12 col-sm-3">
      <label class="form-label small">Account PPPoE</label>
      <input type="text" name="pppoe_account" class="form-control form-control-sm"
             placeholder="Cerca account..." value="{{ request('pppoe_account') }}">
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Account PPPoE</th>
              <th>Cliente</th>
              <th class="text-end">Cap GB</th>
              <th style="min-width:200px">Utilizzo</th>
              <th>Stato FUP</th>
              <th>Reset il</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($usages ?? [] as $usage)
              @php
                $capGb    = $usage->cap_gb ?? 0;
                $usedGb   = round(($usage->bytes_total ?? 0) / 1073741824, 2);
                $pct      = $capGb > 0 ? min(round($usedGb / $capGb * 100), 100) : 0;
                $barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
              @endphp
              <tr>
                <td class="fw-semibold font-monospace small">{{ $usage->pppoe_account_id }}</td>
                <td class="small">{{ $usage->customer->full_name ?? '—' }}</td>
                <td class="text-end small">{{ number_format($capGb, 1) }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%"></div>
                    </div>
                    <small class="text-nowrap text-muted">{{ number_format($usedGb, 1) }} GB</small>
                  </div>
                </td>
                <td>
                  @php
                    $fupStatus = $usage->fup_status->value ?? $usage->fup_status ?? 'normal';
                    $fupColor  = match($fupStatus) {
                      'throttled', 'exhausted' => 'danger',
                      'warning' => 'warning',
                      default   => 'success',
                    };
                  @endphp
                  <span class="badge bg-{{ $fupColor }}">{{ ucfirst($fupStatus) }}</span>
                </td>
                <td class="small text-muted">{{ $usage->reset_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="text-end">
                  <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"
                            title="Acquista top-up">
                      <i class="ri-add-circle-line"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      @foreach($products ?? [] as $product)
                        <li>
                          <form method="POST" action="#">
                            @csrf
                            <input type="hidden" name="pppoe_account_id" value="{{ $usage->pppoe_account_id }}">
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <button type="submit" class="dropdown-item small">
                              {{ $product->name }} (+{{ $product->data_gb_added ?? '?' }} GB)
                              — € {{ number_format(($product->price_amount ?? 0) / 100, 2, ',', '.') }}
                            </button>
                          </form>
                        </li>
                      @endforeach
                      @if(empty($products) || count($products) === 0)
                        <li><span class="dropdown-item disabled small">Nessun top-up disponibile</span></li>
                      @endif
                    </ul>
                  </div>
                  <form method="POST" action="#" class="d-inline">
                    @csrf
                    <input type="hidden" name="pppoe_account_id" value="{{ $usage->pppoe_account_id }}">
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            title="Reset manuale contatore"
                            data-confirm="Reset manuale del contatore FUP per {{ $usage->pppoe_account_id }}?">
                      <i class="ri-restart-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun dato FUP trovato" icon="ri-wifi-off-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($usages) && $usages->hasPages())
      <div class="card-footer">{{ $usages->links() }}</div>
    @endif
  </div>

@endsection

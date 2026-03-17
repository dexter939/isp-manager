@extends('layouts.contentNavbarLayout')
@section('title', 'Portafoglio — ' . $wallet->customer_name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="#">Fatturazione</a></li>
  <li class="breadcrumb-item"><a href="{{ route('billing.prepaid.wallets.index') }}">Portafogli Prepaid</a></li>
  <li class="breadcrumb-item active">{{ $wallet->customer_name }}</li>
@endsection

@section('page-content')

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">
      {{ $wallet->customer_name }}
      @php
        $sc = ['active' => 'success', 'suspended' => 'warning', 'closed' => 'secondary'][$wallet->status] ?? 'secondary';
      @endphp
      <span class="badge bg-label-{{ $sc }} ms-2">{{ ucfirst($wallet->status) }}</span>
    </h4>
    <p class="text-muted mb-0">
      Portafoglio prepaid — ID: <code class="small">{{ $wallet->id }}</code>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('customers.show', $wallet->customer_id) }}" class="btn btn-outline-secondary">
      <i class="ri-user-line me-1"></i>Cliente
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
      <i class="ri-add-circle-line me-1"></i>Rettifica saldo
    </button>
  </div>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-sm-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="avatar avatar-md flex-shrink-0" style="background:rgba(113,221,55,.15)">
          <i class="ri-money-euro-circle-line fs-4" style="color:#71dd37"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold {{ $wallet->balance_amount <= $wallet->low_balance_threshold_amount ? 'text-warning' : '' }}">
            € {{ number_format($wallet->balance_amount / 100, 2, ',', '.') }}
          </div>
          <div class="text-muted small">Saldo attuale</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-sm-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="avatar avatar-md flex-shrink-0" style="background:rgba(255,171,0,.15)">
          <i class="ri-alarm-warning-line fs-4" style="color:#ffab00"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold">€ {{ number_format($wallet->low_balance_threshold_amount / 100, 2, ',', '.') }}</div>
          <div class="text-muted small">Soglia basso saldo</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-sm-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="avatar avatar-md flex-shrink-0" style="background:rgba(3,195,236,.15)">
          <i class="ri-exchange-dollar-line fs-4" style="color:#03c3ec"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold">{{ $transactions->total() }}</div>
          <div class="text-muted small">Transazioni totali</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-sm-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="avatar avatar-md flex-shrink-0" style="background:rgba(105,108,255,.15)">
          <i class="ri-shopping-cart-line fs-4" style="color:#696cff"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold">{{ $orders->count() }}</div>
          <div class="text-muted small">Ordini ricarica</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">

  {{-- Transazioni --}}
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0">Storico transazioni</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Tipo</th>
              <th>Importo</th>
              <th>Saldo prima</th>
              <th>Saldo dopo</th>
              <th>Descrizione</th>
              <th>Metodo</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transactions as $tx)
              @php
                $isCredit = $tx->direction === 'credit';
                $typeLabels = [
                  'topup'             => 'Ricarica',
                  'usage'             => 'Utilizzo',
                  'admin_adjustment'  => 'Rettifica admin',
                  'refund'            => 'Rimborso',
                  'bonus'             => 'Bonus',
                ];
                $typeLabel = $typeLabels[$tx->type] ?? ucfirst($tx->type);
              @endphp
              <tr>
                <td class="text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
                <td><span class="badge bg-label-secondary small">{{ $typeLabel }}</span></td>
                <td class="fw-semibold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                  {{ $isCredit ? '+' : '-' }}€ {{ number_format($tx->amount_amount / 100, 2, ',', '.') }}
                </td>
                <td class="text-muted small">€ {{ number_format($tx->balance_before_amount / 100, 2, ',', '.') }}</td>
                <td class="small">€ {{ number_format($tx->balance_after_amount / 100, 2, ',', '.') }}</td>
                <td class="small text-muted">{{ Str::limit($tx->description, 40) }}</td>
                <td class="small text-muted">{{ $tx->payment_method ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">Nessuna transazione</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($transactions->hasPages())
        <div class="card-footer">
          {{ $transactions->links() }}
        </div>
      @endif
    </div>
  </div>

  {{-- Sidebar: ordini + info --}}
  <div class="col-12 col-lg-4">

    {{-- Info portafoglio --}}
    <div class="card shadow-sm mb-4">
      <div class="card-header"><h6 class="mb-0">Dettagli portafoglio</h6></div>
      <div class="card-body">
        <dl class="row row-cols-2 g-2 mb-0 small">
          <dt class="col text-muted">Valuta</dt>
          <dd class="col fw-semibold">{{ $wallet->balance_currency }}</dd>
          <dt class="col text-muted">Sospensione auto</dt>
          <dd class="col">
            @if($wallet->auto_suspend_on_zero)
              <span class="badge bg-label-warning">Attiva</span>
            @else
              <span class="badge bg-label-secondary">Disattiva</span>
            @endif
          </dd>
          <dt class="col text-muted">Creato il</dt>
          <dd class="col">{{ \Carbon\Carbon::parse($wallet->created_at)->format('d/m/Y') }}</dd>
        </dl>
      </div>
    </div>

    {{-- Ultimi ordini ricarica --}}
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Ultimi ordini ricarica</h6></div>
      <ul class="list-group list-group-flush">
        @forelse($orders as $ord)
          @php
            $ordColors = ['pending' => 'warning', 'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary'];
            $oc = $ordColors[$ord->status] ?? 'secondary';
          @endphp
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
              <div class="small fw-semibold">{{ $ord->product_name }}</div>
              <div class="text-muted" style="font-size:.72rem">
                {{ \Carbon\Carbon::parse($ord->created_at)->format('d/m/Y H:i') }}
                · {{ $ord->payment_method }}
              </div>
            </div>
            <div class="text-end">
              <div class="fw-semibold small">€ {{ number_format($ord->amount_amount / 100, 2, ',', '.') }}</div>
              <span class="badge bg-label-{{ $oc }}" style="font-size:.65rem">{{ ucfirst($ord->status) }}</span>
            </div>
          </li>
        @empty
          <li class="list-group-item text-center text-muted small py-3">Nessun ordine</li>
        @endforelse
      </ul>
    </div>

  </div>
</div>

{{-- Modal rettifica saldo --}}
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('billing.prepaid.wallets.adjust', $wallet->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Rettifica saldo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Operazione</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="direction" id="dirCredit" value="credit" checked>
                <label class="form-check-label text-success" for="dirCredit">
                  <i class="ri-add-circle-line me-1"></i>Accredito
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="direction" id="dirDebit" value="debit">
                <label class="form-check-label text-danger" for="dirDebit">
                  <i class="ri-subtract-line me-1"></i>Addebito
                </label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Importo (centesimi)</label>
            <div class="input-group">
              <input type="number" name="amount" class="form-control" min="1" placeholder="es. 1000 = €10,00" required>
              <span class="input-group-text">¢</span>
            </div>
            <div class="form-text">Inserire in centesimi: €10 = 1000</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <input type="text" name="description" class="form-control" placeholder="Rettifica manuale — motivo…" required maxlength="255">
          </div>
          <div class="alert alert-info small mb-0">
            <i class="ri-information-line me-1"></i>
            Saldo attuale: <strong>€ {{ number_format($wallet->balance_amount / 100, 2, ',', '.') }}</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Applica rettifica</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

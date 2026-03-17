@extends('layouts.agent-portal')
@section('title', 'Liquidazioni')
@section('nav_liquidations', 'active')

@section('content')

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h5 class="mb-0">Le mie liquidazioni</h5>
      <p class="text-muted small mb-0">Storico liquidazioni mensili provvigioni</p>
    </div>
    <div class="text-end">
      <div class="text-muted small">Totale incassato</div>
      <div class="fw-bold fs-5 text-success">€ {{ number_format($totalPaidCents / 100, 2, ',', '.') }}</div>
    </div>
  </div>

  <div class="card portal-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="small fw-semibold">Periodo</th>
              <th class="small fw-semibold text-end">Importo</th>
              <th class="small fw-semibold">Stato</th>
              <th class="small fw-semibold">Approvata il</th>
              <th class="small fw-semibold">Pagata il</th>
              <th class="small fw-semibold">IBAN</th>
            </tr>
          </thead>
          <tbody>
            @forelse($liquidations as $liq)
              <tr>
                <td class="small fw-semibold">
                  {{ \Carbon\Carbon::parse($liq->period_month)->format('F Y') }}
                </td>
                <td class="small text-end fw-bold">
                  € {{ number_format($liq->total_amount_cents / 100, 2, ',', '.') }}
                </td>
                <td>
                  <span class="badge badge-{{ $liq->status }}">
                    @switch($liq->status)
                      @case('draft')    Bozza @break
                      @case('approved') Approvata @break
                      @case('paid')     Pagata @break
                      @default          {{ ucfirst($liq->status) }}
                    @endswitch
                  </span>
                </td>
                <td class="small text-muted">
                  {{ $liq->approved_at ? \Carbon\Carbon::parse($liq->approved_at)->format('d/m/Y') : '—' }}
                </td>
                <td class="small">
                  @if($liq->paid_at)
                    <span class="text-success fw-semibold">
                      {{ \Carbon\Carbon::parse($liq->paid_at)->format('d/m/Y') }}
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="small text-muted font-monospace" style="font-size:.7rem">
                  {{ substr($liq->iban, 0, 4) . '****' . substr($liq->iban, -4) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4 small">
                  <i class="ri-bank-card-line d-block fs-3 mb-1"></i>
                  Nessuna liquidazione disponibile.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($liquidations->hasPages())
      <div class="card-footer bg-transparent">
        {{ $liquidations->links() }}
      </div>
    @endif
  </div>

  {{-- Legenda stati --}}
  <div class="card portal-card mt-4">
    <div class="card-body">
      <div class="row g-3 text-center">
        <div class="col-4">
          <span class="badge badge-draft">Bozza</span>
          <div class="text-muted small mt-1">In elaborazione</div>
        </div>
        <div class="col-4">
          <span class="badge badge-approved">Approvata</span>
          <div class="text-muted small mt-1">In attesa di pagamento</div>
        </div>
        <div class="col-4">
          <span class="badge badge-paid">Pagata</span>
          <div class="text-muted small mt-1">Accreditata sul conto</div>
        </div>
      </div>
    </div>
  </div>

@endsection

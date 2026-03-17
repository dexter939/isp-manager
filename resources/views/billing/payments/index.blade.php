@extends('layouts.contentNavbarLayout')

@section('title', 'Pagamenti')

@section('breadcrumb')
  <li class="breadcrumb-item">Fatturazione</li>
  <li class="breadcrumb-item active">Pagamenti</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Pagamenti</h4>
    <p class="text-muted mb-0">Storico transazioni</p>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Riferimento</th>
              <th>Cliente</th>
              <th>Fattura</th>
              <th>Metodo</th>
              <th class="text-end">Importo</th>
              <th>Stato</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $payment)
              <tr>
                <td class="font-monospace small">{{ $payment->external_reference ?? $payment->id }}</td>
                <td>{{ $payment->invoice->contract->customer->full_name ?? '—' }}</td>
                <td>
                  <a href="{{ route('billing.invoices.show', $payment->invoice) }}">
                    {{ $payment->invoice->number }}
                  </a>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ strtoupper($payment->method) }}
                  </span>
                </td>
                <td class="text-end fw-semibold">€ {{ number_format($payment->amount / 100, 2, ',', '.') }}</td>
                <td>
                  <span class="badge bg-{{ $payment->status === 'completed' ? 'success' : 'danger' }}">
                    {{ ucfirst($payment->status) }}
                  </span>
                </td>
                <td class="text-muted small">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Nessun pagamento trovato</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($payments->hasPages())
      <div class="card-footer">{{ $payments->links() }}</div>
    @endif
  </div>

@endsection

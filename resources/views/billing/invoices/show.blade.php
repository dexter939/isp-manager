@extends('layouts.contentNavbarLayout')

@section('title', 'Fattura ' . $invoice->number)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('billing.invoices.index') }}">Fatture</a></li>
  <li class="breadcrumb-item active">{{ $invoice->number }}</li>
@endsection

@section('page-content')

  <div class="page-header d-flex justify-content-between align-items-start">
    <div>
      <h4>Fattura {{ $invoice->number }}</h4>
      <p class="text-muted mb-0">
        Emessa il {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }} —
        Scadenza {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('billing.invoices.pdf', $invoice->id) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="ri-file-pdf-line me-1"></i>PDF
      </a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header">Importi</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">Imponibile</dt>
            <dd class="col-7">€ {{ number_format($invoice->subtotal / 100, 2, ',', '.') }}</dd>
            <dt class="col-5 text-muted">IVA ({{ $invoice->tax_rate ?? 22 }}%)</dt>
            <dd class="col-7">€ {{ number_format($invoice->tax_amount / 100, 2, ',', '.') }}</dd>
            <dt class="col-5 fw-semibold">Totale</dt>
            <dd class="col-7 fw-semibold fs-5">€ {{ number_format($invoice->total_amount / 100, 2, ',', '.') }}</dd>
            <dt class="col-5 text-muted mt-2">Stato</dt>
            <dd class="col-7 mt-2">
              <span class="badge bg-{{ match($invoice->status) {
                'paid'    => 'success',
                'overdue' => 'danger',
                'issued'  => 'info',
                default   => 'secondary'
              } }}">{{ ucfirst($invoice->status) }}</span>
            </dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header">Pagamenti</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr><th>Metodo</th><th>Riferimento</th><th class="text-end">Importo</th><th>Data</th></tr>
              </thead>
              <tbody>
                @php
                  $payments = \Illuminate\Support\Facades\DB::table('payments')
                    ->where('invoice_id', $invoice->id)->get();
                @endphp
                @forelse($payments as $pmt)
                  <tr>
                    <td>{{ strtoupper($pmt->method) }}</td>
                    <td class="font-monospace small">{{ $pmt->external_reference ?? '—' }}</td>
                    <td class="text-end">€ {{ number_format($pmt->amount / 100, 2, ',', '.') }}</td>
                    <td class="small text-muted">{{ $pmt->paid_at ? \Carbon\Carbon::parse($pmt->paid_at)->format('d/m/Y') : '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">Nessun pagamento registrato</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

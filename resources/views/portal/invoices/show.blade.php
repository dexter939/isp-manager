@extends('layouts.portal')
@section('title', 'Fattura ' . $invoice->number)

@section('content')
  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="ri-arrow-left-line"></i>
    </a>
    <h5 class="mb-0">Fattura {{ $invoice->number }}</h5>
    <span class="badge badge-status-{{ $invoice->status }} ms-1">{{ ucfirst($invoice->status) }}</span>
    <div class="ms-auto d-flex gap-2">
      @if(in_array($invoice->status, ['issued', 'overdue']))
        <a href="{{ route('portal.invoices.pay', $invoice->id) }}" class="btn btn-sm btn-danger">
          <i class="ri-secure-payment-line me-1"></i>Paga ora
        </a>
      @endif
      @if($invoice->pdf_path)
        <a href="{{ route('portal.invoices.pdf', $invoice->id) }}" target="_blank" class="btn btn-sm btn-outline-danger">
          <i class="ri-file-pdf-line me-1"></i>PDF
        </a>
      @endif
    </div>
  </div>

  <div class="row g-4">
    <div class="col-12 col-md-5">
      <div class="card portal-card">
        <div class="card-header bg-transparent fw-semibold small">Dettagli fattura</div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-5 text-muted">Numero</dt>
            <dd class="col-7 fw-semibold">{{ $invoice->number }}</dd>
            <dt class="col-5 text-muted">Data emissione</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</dd>
            <dt class="col-5 text-muted">Scadenza</dt>
            <dd class="col-7 {{ $invoice->status === 'overdue' ? 'text-danger fw-bold' : '' }}">
              {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '—' }}
            </dd>
            @if($invoice->period_from)
            <dt class="col-5 text-muted">Periodo</dt>
            <dd class="col-7">
              {{ \Carbon\Carbon::parse($invoice->period_from)->format('d/m/Y') }} –
              {{ \Carbon\Carbon::parse($invoice->period_to)->format('d/m/Y') }}
            </dd>
            @endif
            <dt class="col-5 text-muted">Metodo pag.</dt>
            <dd class="col-7">{{ strtoupper($invoice->payment_method ?? '—') }}</dd>
            @if($invoice->paid_at)
            <dt class="col-5 text-muted">Pagato il</dt>
            <dd class="col-7 text-success">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y') }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-7">
      <div class="card portal-card">
        <div class="card-header bg-transparent fw-semibold small">Voci fattura</div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Descrizione</th>
                <th class="text-end">Qtà</th>
                <th class="text-end">Unitario</th>
                <th class="text-end">Totale</th>
              </tr>
            </thead>
            <tbody>
              @foreach($items as $item)
                <tr>
                  <td class="small">{{ $item->description }}</td>
                  <td class="text-end small">{{ $item->quantity }}</td>
                  <td class="text-end small">€ {{ number_format($item->unit_price / 100, 2, ',', '.') }}</td>
                  <td class="text-end small fw-semibold">€ {{ number_format($item->total / 100, 2, ',', '.') }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="3" class="text-end small text-muted">Imponibile</td>
                <td class="text-end small">€ {{ number_format($invoice->subtotal / 100, 2, ',', '.') }}</td>
              </tr>
              <tr>
                <td colspan="3" class="text-end small text-muted">IVA {{ $invoice->tax_rate }}%</td>
                <td class="text-end small">€ {{ number_format($invoice->tax_amount / 100, 2, ',', '.') }}</td>
              </tr>
              <tr>
                <td colspan="3" class="text-end fw-bold">Totale</td>
                <td class="text-end fw-bold">€ {{ number_format($invoice->total / 100, 2, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      @if($payments->count() > 0)
        <div class="card portal-card mt-3">
          <div class="card-header bg-transparent fw-semibold small">Pagamenti ricevuti</div>
          <div class="card-body p-0">
            @foreach($payments as $p)
              <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                  <div class="small fw-semibold">{{ strtoupper($p->method) }}</div>
                  <div class="text-muted" style="font-size:.75rem">
                    {{ \Carbon\Carbon::parse($p->processed_at)->format('d/m/Y H:i') }}
                  </div>
                </div>
                <span class="fw-bold text-success">€ {{ number_format($p->amount / 100, 2, ',', '.') }}</span>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

@endsection

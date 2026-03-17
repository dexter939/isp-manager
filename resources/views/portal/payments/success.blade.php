@extends('layouts.portal')
@section('title', 'Pagamento inviato')

@section('content')

  <div class="text-center py-5">
    <div class="mb-4">
      <div class="avatar avatar-xl mx-auto mb-3" style="background:rgba(113,221,55,.15);width:80px;height:80px">
        <i class="ri-checkbox-circle-line" style="font-size:2.5rem;color:#71dd37"></i>
      </div>
      <h4 class="fw-bold mb-1">Pagamento ricevuto!</h4>
      <p class="text-muted">
        Il tuo pagamento è stato inviato. Riceverai una conferma via email.
      </p>
    </div>

    @if($invoice)
      <div class="card portal-card mx-auto mb-4" style="max-width:400px">
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-5 text-muted text-start">Fattura</dt>
            <dd class="col-7 fw-semibold text-start">{{ $invoice->number }}</dd>
            <dt class="col-5 text-muted text-start">Importo</dt>
            <dd class="col-7 fw-semibold text-success text-start">€ {{ number_format($invoice->total / 100, 2, ',', '.') }}</dd>
            <dt class="col-5 text-muted text-start">Stato attuale</dt>
            <dd class="col-7 text-start">
              <span class="badge badge-status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
              <span class="text-muted small d-block mt-1">Lo stato verrà aggiornato a breve</span>
            </dd>
          </dl>
        </div>
      </div>
    @endif

    <div class="d-flex justify-content-center gap-3">
      <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline-secondary">
        <i class="ri-bill-line me-1"></i>Le mie fatture
      </a>
      <a href="{{ route('portal.dashboard') }}" class="btn btn-primary">
        <i class="ri-home-line me-1"></i>Dashboard
      </a>
    </div>
  </div>

@endsection

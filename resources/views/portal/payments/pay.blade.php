@extends('layouts.portal')
@section('title', 'Paga fattura ' . $invoice->number)

@section('content')

  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-secondary">
      <i class="ri-arrow-left-line"></i>
    </a>
    <h5 class="mb-0">Paga fattura {{ $invoice->number }}</h5>
  </div>

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4">
      <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Invoice summary --}}
  <div class="card portal-card mb-4">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col">
          <div class="text-muted small mb-1">Importo da pagare</div>
          <div class="display-6 fw-bold text-danger">
            € {{ number_format($invoice->total / 100, 2, ',', '.') }}
          </div>
          <div class="small text-muted mt-1">
            Fattura {{ $invoice->number }}
            @if($invoice->due_date)
              · Scadenza {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
              @if($invoice->status === 'overdue')
                <span class="text-danger fw-semibold">(scaduta)</span>
              @endif
            @endif
          </div>
        </div>
        <div class="col-auto">
          <div class="avatar avatar-lg" style="background:rgba(255,62,29,.1)">
            <i class="ri-bill-line fs-3 text-danger"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Saved payment methods --}}
  @if($methods->count() > 0)
    <div class="card portal-card mb-4">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="ri-bank-card-line me-2"></i>Paga con un metodo salvato
      </div>
      <div class="card-body p-0">
        @foreach($methods as $method)
          <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
              {{-- Card brand icon --}}
              <div class="avatar avatar-sm" style="background:#f0f1ff">
                @if($method->card_brand === 'visa')
                  <span class="fw-bold" style="font-size:0.75rem;color:#1a1f71">VISA</span>
                @elseif($method->card_brand === 'mastercard')
                  <i class="ri-bank-card-2-fill text-danger"></i>
                @else
                  <i class="ri-bank-card-line text-primary"></i>
                @endif
              </div>
              <div>
                <div class="fw-semibold small">
                  {{ ucfirst($method->card_brand ?? 'Carta') }} •••• {{ $method->card_last4 }}
                  @if($method->is_default)
                    <span class="badge bg-label-primary ms-1" style="font-size:0.65rem">Predefinita</span>
                  @endif
                </div>
                <div class="text-muted" style="font-size:0.75rem">
                  Scad. {{ $method->card_expiry }} · {{ strtoupper($method->gateway) }}
                </div>
              </div>
            </div>
            <form method="POST" action="{{ route('portal.invoices.charge', [$invoice->id, $method->id]) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-primary"
                      onclick="return confirm('Addebitare € {{ number_format($invoice->total / 100, 2, \',\', \'.\') }} su questa carta?')">
                <i class="ri-secure-payment-line me-1"></i>Paga ora
              </button>
            </form>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Pay with Stripe (new card) --}}
  <div class="card portal-card">
    <div class="card-header bg-transparent fw-semibold small">
      <i class="ri-secure-payment-line me-2"></i>Paga con carta di credito / debito
    </div>
    <div class="card-body">
      <p class="text-muted small mb-4">
        Verrai reindirizzato a una pagina di pagamento sicura. Dopo il pagamento tornerai automaticamente al portale.
      </p>
      <div class="d-flex flex-wrap gap-2 mb-4">
        <img src="https://cdn.simpleicons.org/visa/1A1F71" alt="Visa" height="24" style="opacity:.8">
        <img src="https://cdn.simpleicons.org/mastercard/EB001B" alt="Mastercard" height="24" style="opacity:.8">
        <img src="https://cdn.simpleicons.org/stripe/635BFF" alt="Stripe" height="20" style="opacity:.8">
      </div>
      <form method="POST" action="{{ route('portal.invoices.pay.initiate', $invoice->id) }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-100">
          <i class="ri-external-link-line me-2"></i>Procedi al pagamento sicuro
          <span class="ms-2 fw-bold">€ {{ number_format($invoice->total / 100, 2, ',', '.') }}</span>
        </button>
      </form>
    </div>
  </div>

  <div class="text-center mt-3">
    <div class="d-flex align-items-center justify-content-center gap-2 text-muted small">
      <i class="ri-lock-line"></i>
      <span>Pagamento protetto SSL · I tuoi dati sono al sicuro</span>
    </div>
  </div>

@endsection

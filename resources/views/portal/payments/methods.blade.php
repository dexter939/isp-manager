@extends('layouts.portal')
@section('title', 'Metodi di pagamento')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="ri-bank-card-line me-2"></i>Metodi di pagamento</h5>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($methods->count() > 0)
    <div class="card portal-card mb-4">
      <div class="card-body p-0">
        @foreach($methods as $method)
          <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
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
                  Scadenza {{ $method->card_expiry }}
                  · {{ strtoupper($method->gateway) }}
                  · Aggiunta il {{ \Carbon\Carbon::parse($method->created_at)->format('d/m/Y') }}
                </div>
              </div>
            </div>
            <form method="POST" action="{{ route('portal.payment-methods.destroy', $method->id) }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Rimuovere questo metodo di pagamento?')">
                <i class="ri-delete-bin-line"></i>
              </button>
            </form>
          </div>
        @endforeach
      </div>
    </div>
  @else
    <div class="card portal-card">
      <div class="card-body text-center py-5">
        <div class="avatar avatar-lg mx-auto mb-3" style="background:#f0f1ff">
          <i class="ri-bank-card-line fs-3 text-primary"></i>
        </div>
        <h6 class="fw-semibold mb-1">Nessun metodo di pagamento salvato</h6>
        <p class="text-muted small mb-0">
          I tuoi metodi di pagamento verranno salvati automaticamente dopo il primo pagamento.
        </p>
      </div>
    </div>
  @endif

  <div class="card portal-card mt-3">
    <div class="card-body">
      <h6 class="fw-semibold mb-2"><i class="ri-information-line me-1"></i>Come funziona</h6>
      <p class="text-muted small mb-0">
        I metodi di pagamento vengono aggiunti automaticamente quando paghi una fattura tramite carta.
        Puoi usarli per pagare le fatture future più rapidamente senza reinserire i dati.
        <strong>I dati della carta sono salvati in modo sicuro da Stripe/Nexi e non vengono mai memorizzati sui nostri server.</strong>
      </p>
    </div>
  </div>

@endsection

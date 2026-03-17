@extends('layouts.portal')
@section('title', 'Pagamento annullato')

@section('content')

  <div class="text-center py-5">
    <div class="mb-4">
      <div class="avatar avatar-xl mx-auto mb-3" style="background:rgba(255,171,0,.15);width:80px;height:80px">
        <i class="ri-close-circle-line" style="font-size:2.5rem;color:#ffab00"></i>
      </div>
      <h4 class="fw-bold mb-1">Pagamento annullato</h4>
      <p class="text-muted">
        Hai annullato il pagamento. Nessun importo è stato addebitato.<br>
        Puoi riprovare quando vuoi dalla pagina delle fatture.
      </p>
    </div>

    <div class="d-flex justify-content-center gap-3">
      <a href="{{ route('portal.invoices.index') }}" class="btn btn-primary">
        <i class="ri-bill-line me-1"></i>Torna alle fatture
      </a>
    </div>
  </div>

@endsection

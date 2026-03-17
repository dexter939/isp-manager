@extends('layouts.contentNavbarLayout')
@section('title', 'Trasmissione SDI ' . $transmission->filename)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('billing.sdi.index') }}">SDI</a></li>
      <li class="breadcrumb-item active font-monospace">{{ $transmission->filename ?? $transmission->uuid }}</li>
    </ol>
  </nav>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Header row --}}
  <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
    <div>
      <h4 class="fw-bold mb-1 font-monospace">{{ $transmission->filename ?? '(filename non generato)' }}</h4>
      <div class="d-flex gap-2 flex-wrap align-items-center">
        @include('billing.sdi._status_badge', ['status' => $transmission->status])
        <span class="badge bg-label-{{ $transmission->channel === 'aruba' ? 'primary' : 'info' }}">
          {{ strtoupper($transmission->channel) }}
        </span>
        @if($transmission->notification_code)
          @include('billing.sdi._notification_badge', ['code' => $transmission->notification_code])
        @endif
      </div>
    </div>
    @if($canRetry)
      <form method="POST" action="{{ route('billing.sdi.retry', $transmission->id) }}">
        @csrf
        <button type="submit" class="btn btn-warning"
                onclick="return confirm('Ritrasmettere questa fattura all\'SDI?')">
          <i class="ri-restart-line me-1"></i>Ritrasmetti
        </button>
      </form>
    @endif
  </div>

  <div class="row g-4">

    {{-- Left: details --}}
    <div class="col-12 col-lg-5">

      <div class="card shadow-sm mb-4">
        <div class="card-header py-3"><h6 class="mb-0"><i class="ri-bill-line me-2"></i>Fattura</h6></div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-5 text-muted">Numero</dt>
            <dd class="col-7">
              <a href="{{ route('billing.invoices.show', $transmission->invoice_id_link) }}" class="fw-semibold">
                {{ $transmission->invoice_number }}
              </a>
            </dd>
            <dt class="col-5 text-muted">Cliente</dt>
            <dd class="col-7">{{ $transmission->company_name ?: $transmission->customer_name }}</dd>
            <dt class="col-5 text-muted">Importo</dt>
            <dd class="col-7 fw-semibold">€ {{ number_format($transmission->invoice_total / 100, 2, ',', '.') }}</dd>
            <dt class="col-5 text-muted">Data emissione</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($transmission->issue_date)->format('d/m/Y') }}</dd>
          </dl>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-header py-3"><h6 class="mb-0"><i class="ri-information-line me-2"></i>Dati trasmissione</h6></div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-6 text-muted">UUID</dt>
            <dd class="col-6 font-monospace" style="font-size:.72rem">{{ $transmission->uuid }}</dd>

            <dt class="col-6 text-muted">Hash XML (SHA256)</dt>
            <dd class="col-6 font-monospace" style="font-size:.65rem;word-break:break-all">
              {{ $transmission->xml_hash ?? '—' }}
            </dd>

            <dt class="col-6 text-muted">Inviata il</dt>
            <dd class="col-6">
              {{ $transmission->sent_at ? \Carbon\Carbon::parse($transmission->sent_at)->format('d/m/Y H:i') : '—' }}
            </dd>

            <dt class="col-6 text-muted">Conservazione fino</dt>
            <dd class="col-6">
              {{ $transmission->conservazione_expires_at
                  ? \Carbon\Carbon::parse($transmission->conservazione_expires_at)->format('d/m/Y')
                  : '—' }}
            </dd>

            <dt class="col-6 text-muted">Retry</dt>
            <dd class="col-6">{{ $transmission->retry_count }}/{{ config('sdi.max_retries', 3) }}</dd>

            @if($transmission->last_error)
              <dt class="col-6 text-muted">Ultimo errore</dt>
              <dd class="col-6 text-danger small" style="word-break:break-word">{{ $transmission->last_error }}</dd>
            @endif
          </dl>
        </div>
      </div>

      {{-- XML content --}}
      @if($transmission->xml_content)
        <div class="card shadow-sm">
          <div class="card-header py-3"><h6 class="mb-0"><i class="ri-code-s-slash-line me-2"></i>XML FatturaPA</h6></div>
          <div class="card-body p-0">
            <div class="p-3">
              <button class="btn btn-sm btn-outline-secondary w-100 text-start" type="button"
                      data-bs-toggle="collapse" data-bs-target="#xmlContent">
                <i class="ri-arrow-down-s-line me-1"></i>Mostra XML completo
              </button>
              <div class="collapse mt-2" id="xmlContent">
                <pre class="bg-dark text-light p-3 rounded small" style="max-height:400px;overflow:auto;white-space:pre-wrap;word-break:break-all;font-size:.72rem">{{ $transmission->xml_content }}</pre>
              </div>
            </div>
          </div>
        </div>
      @endif

    </div>

    {{-- Right: notifications --}}
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="ri-notification-3-line me-2"></i>Notifiche SDI ricevute</h6>
          <span class="badge bg-label-secondary">{{ $notifications->count() }}</span>
        </div>
        @if($notifications->count() > 0)
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:140px">Ricevuta il</th>
                  <th style="width:80px">Codice</th>
                  <th>Descrizione</th>
                  <th style="width:70px">Processata</th>
                </tr>
              </thead>
              <tbody>
                @foreach($notifications as $notif)
                  <tr>
                    <td class="small font-monospace">
                      {{ \Carbon\Carbon::parse($notif->received_at)->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                      @include('billing.sdi._notification_badge', ['code' => $notif->notification_type])
                    </td>
                    <td>
                      @php
                        $descs = ['RC'=>'Ricevuta di Consegna','MC'=>'Mancata Consegna','NS'=>'Notifica di Scarto','EC'=>'Esito Committente','AT'=>'Attestazione di Trasmissione','DT'=>'Decorrenza Termini','SF'=>'Scarto Fattura'];
                      @endphp
                      <span class="small">{{ $descs[$notif->notification_type] ?? $notif->notification_type }}</span>
                    </td>
                    <td class="text-center">
                      @if($notif->processed)
                        <i class="ri-checkbox-circle-fill text-success"></i>
                      @else
                        <i class="ri-time-line text-warning"></i>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="card-body text-center py-5 text-muted">
            <i class="ri-notification-off-line fs-1 d-block mb-2 opacity-25"></i>
            Nessuna notifica ricevuta ancora.<br>
            <span class="small">Le notifiche arrivano dall'SDI dopo la trasmissione (da minuti a giorni).</span>
          </div>
        @endif
      </div>

      {{-- SDI Notification legend --}}
      <div class="card shadow-sm mt-4">
        <div class="card-header py-3"><h6 class="mb-0"><i class="ri-question-line me-2"></i>Legenda codici SDI</h6></div>
        <div class="card-body">
          <div class="row g-2 small">
            @foreach(['RC'=>['Ricevuta di Consegna','success'],'MC'=>['Mancata Consegna – retry','warning'],'NS'=>['Notifica di Scarto (formato)','danger'],'EC'=>['Esito Committente','info'],'AT'=>['Attestazione di Trasmissione','success'],'DT'=>['Decorrenza Termini (accettazione implicita)','success'],'SF'=>['Scarto Fattura (struttura)','danger']] as $code => $info)
              <div class="col-6">
                <span class="badge bg-label-{{ $info[1] }} me-1">{{ $code }}</span>
                <span class="text-muted">{{ $info[0] }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush

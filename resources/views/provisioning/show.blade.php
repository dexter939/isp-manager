@extends('layouts.contentNavbarLayout')

@section('title', 'Ordine ' . $order->codice_ordine_olo)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('provisioning.index') }}">Provisioning</a></li>
      <li class="breadcrumb-item active font-monospace">{{ $order->codice_ordine_olo }}</li>
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
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
      <h4 class="fw-bold mb-1 font-monospace">{{ $order->codice_ordine_olo }}</h4>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        @include('provisioning._state_badge', ['state' => $order->state])
        @php
          $carrierColors = ['openfiber' => 'primary', 'fibercop' => 'warning', 'fastweb' => 'info'];
          $cc = $carrierColors[$order->carrier] ?? 'secondary';
        @endphp
        <span class="badge bg-label-{{ $cc }}">{{ ucfirst($order->carrier) }}</span>
        <span class="badge bg-label-secondary">{{ ucfirst($order->order_type) }}</span>
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      {{-- Send --}}
      @if($canSend)
        <form method="POST" action="{{ route('provisioning.send', $order->id) }}">
          @csrf
          <button type="submit" class="btn btn-primary"
                  onclick="return confirm('Inviare l\'ordine al carrier?')">
            <i class="ri-send-plane-line me-1"></i>Invia al carrier
          </button>
        </form>
      @endif

      {{-- Reschedule --}}
      @if($canReschedule)
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalReschedule">
          <i class="ri-calendar-event-line me-1"></i>Rimodula
        </button>
      @endif

      {{-- Unsuspend --}}
      @if($canUnsuspend)
        <form method="POST" action="{{ route('provisioning.unsuspend', $order->id) }}">
          @csrf
          <button type="submit" class="btn btn-outline-warning"
                  onclick="return confirm('Inviare richiesta di desospensione?')">
            <i class="ri-play-circle-line me-1"></i>Desospensi
          </button>
        </form>
      @endif

      {{-- Cancel --}}
      @if($canCancel)
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancel">
          <i class="ri-close-circle-line me-1"></i>Annulla
        </button>
      @endif
    </div>
  </div>

  <div class="row g-4">

    {{-- Left column: order details --}}
    <div class="col-12 col-lg-5">

      {{-- Contract card --}}
      <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
          <h6 class="mb-0"><i class="ri-file-text-line me-2"></i>Contratto associato</h6>
        </div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Contratto</dt>
            <dd class="col-7">
              <a href="{{ route('contracts.show', $order->contract_id) }}" class="fw-semibold">
                {{ $order->contract_number }}
              </a>
            </dd>
            <dt class="col-5 text-muted">Cliente</dt>
            <dd class="col-7">{{ $order->company_name ?: $order->customer_name }}</dd>
          </dl>
        </div>
      </div>

      {{-- Order details card --}}
      <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
          <h6 class="mb-0"><i class="ri-information-line me-2"></i>Dettagli ordine</h6>
        </div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-6 text-muted">Codice OLO</dt>
            <dd class="col-6 font-monospace">{{ $order->codice_ordine_olo }}</dd>

            <dt class="col-6 text-muted">Codice carrier</dt>
            <dd class="col-6 font-monospace">{{ $order->codice_ordine_of ?: '—' }}</dd>

            <dt class="col-6 text-muted">Data pianificata</dt>
            <dd class="col-6">
              {{ $order->scheduled_date
                  ? \Carbon\Carbon::parse($order->scheduled_date)->format('d/m/Y H:i')
                  : '—' }}
            </dd>

            <dt class="col-6 text-muted">C-VLAN</dt>
            <dd class="col-6 font-monospace">{{ $order->cvlan ?: '—' }}</dd>

            <dt class="col-6 text-muted">GPON attestazione</dt>
            <dd class="col-6 font-monospace">{{ $order->gpon_attestazione ?: '—' }}</dd>

            <dt class="col-6 text-muted">Apparato consegnato</dt>
            <dd class="col-6">{{ $order->id_apparato_consegnato ?: '—' }}</dd>

            <dt class="col-6 text-muted">VLAN pool ID</dt>
            <dd class="col-6">{{ $order->vlan_pool_id ?: '—' }}</dd>

            <dt class="col-6 text-muted">Inviato da</dt>
            <dd class="col-6">{{ $order->sent_by_name ?: '—' }}</dd>

            <dt class="col-6 text-muted">Inviato il</dt>
            <dd class="col-6">
              {{ $order->sent_at ? \Carbon\Carbon::parse($order->sent_at)->format('d/m/Y H:i') : '—' }}
            </dd>

            <dt class="col-6 text-muted">Completato il</dt>
            <dd class="col-6">
              {{ $order->completed_at ? \Carbon\Carbon::parse($order->completed_at)->format('d/m/Y H:i') : '—' }}
            </dd>

            <dt class="col-6 text-muted">Retry</dt>
            <dd class="col-6">{{ $order->retry_count }}/3</dd>

            @if($order->last_error)
              <dt class="col-6 text-muted">Ultimo errore</dt>
              <dd class="col-6 text-danger small">{{ $order->last_error }}</dd>
            @endif

            @if($order->notes)
              <dt class="col-6 text-muted">Note</dt>
              <dd class="col-6">{{ $order->notes }}</dd>
            @endif

            <dt class="col-6 text-muted">Creato il</dt>
            <dd class="col-6">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</dd>
          </dl>
        </div>
      </div>

      {{-- Payloads (collapsible) --}}
      @if($order->payload_sent || $order->payload_received)
        <div class="card shadow-sm">
          <div class="card-header py-3">
            <h6 class="mb-0"><i class="ri-code-s-slash-line me-2"></i>Payload XML/JSON</h6>
          </div>
          <div class="card-body p-0">
            @if($order->payload_sent)
              <div class="p-3 border-bottom">
                <button class="btn btn-sm btn-outline-secondary w-100 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#payloadSent">
                  <i class="ri-arrow-up-s-line me-1"></i>Payload inviato (outbound)
                </button>
                <div class="collapse mt-2" id="payloadSent">
                  <pre class="bg-dark text-light p-3 rounded small" style="max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all">{{ $order->payload_sent }}</pre>
                </div>
              </div>
            @endif
            @if($order->payload_received)
              <div class="p-3">
                <button class="btn btn-sm btn-outline-secondary w-100 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#payloadReceived">
                  <i class="ri-arrow-down-s-line me-1"></i>Payload ricevuto (inbound)
                </button>
                <div class="collapse mt-2" id="payloadReceived">
                  <pre class="bg-dark text-light p-3 rounded small" style="max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all">{{ $order->payload_received }}</pre>
                </div>
              </div>
            @endif
          </div>
        </div>
      @endif

    </div>

    {{-- Right column: events log --}}
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="ri-history-line me-2"></i>Log eventi carrier</h6>
          <span class="badge bg-label-secondary">{{ $events->count() }}</span>
        </div>
        <div class="table-responsive" style="max-height:600px;overflow-y:auto">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th style="width:130px">Data/ora</th>
                <th>Metodo</th>
                <th style="width:80px">Dir.</th>
                <th style="width:70px">Esito</th>
                <th style="width:65px">ms</th>
                <th>Errore</th>
              </tr>
            </thead>
            <tbody>
              @forelse($events as $ev)
                <tr class="{{ $ev->ack_nack === 'nack' || $ev->ack_nack === 'error' ? 'table-danger' : '' }}">
                  <td class="small font-monospace">
                    {{ \Carbon\Carbon::parse($ev->logged_at)->format('d/m H:i:s') }}
                  </td>
                  <td>
                    <span class="small font-monospace">{{ $ev->method_name }}</span>
                    @if($ev->http_status)
                      <span class="badge bg-label-{{ $ev->http_status < 400 ? 'success' : 'danger' }} ms-1" style="font-size:0.65rem">
                        HTTP {{ $ev->http_status }}
                      </span>
                    @endif
                  </td>
                  <td>
                    @if($ev->direction === 'outbound')
                      <span class="badge bg-label-primary small"><i class="ri-arrow-right-line"></i> OUT</span>
                    @else
                      <span class="badge bg-label-info small"><i class="ri-arrow-left-line"></i> IN</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $ackColor = ['ack' => 'success', 'nack' => 'danger', 'timeout' => 'warning', 'error' => 'danger'][$ev->ack_nack] ?? 'secondary';
                    @endphp
                    @if($ev->ack_nack)
                      <span class="badge bg-{{ $ackColor }}">{{ strtoupper($ev->ack_nack) }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="small text-muted">{{ $ev->duration_ms ?? '—' }}</td>
                  <td class="small text-danger">
                    {{ $ev->error_message ? \Str::limit($ev->error_message, 60) : '' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Nessun evento registrato.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

</div>

{{-- Modal: Reschedule --}}
@if($canReschedule)
<div class="modal fade" id="modalReschedule" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('provisioning.reschedule', $order->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="ri-calendar-event-line me-2"></i>Rimodulazione appuntamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nuova data/ora</label>
            <input type="datetime-local" name="scheduled_date" class="form-control" required
                   min="{{ now()->addDay()->format('Y-m-d\TH:i') }}">
            <div class="form-text">Deve essere una data futura. La richiesta verrà inviata direttamente al carrier.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-info">
            <i class="ri-send-plane-line me-1"></i>Invia rimodulazione
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- Modal: Cancel --}}
@if($canCancel)
<div class="modal fade" id="modalCancel" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('provisioning.cancel', $order->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title text-danger"><i class="ri-close-circle-line me-2"></i>Annulla ordine</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning small">
            <i class="ri-alert-line me-1"></i>
            L'annullamento è irreversibile. Se l'ordine è già stato inviato al carrier, verrà inviata anche una notifica di cancellazione.
          </div>
          <div class="mb-3">
            <label class="form-label">Motivo annullamento <span class="text-muted">(opzionale)</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Es. Cliente in recesso, errore di configurazione…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
          <button type="submit" class="btn btn-danger">
            <i class="ri-close-circle-line me-1"></i>Conferma annullamento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

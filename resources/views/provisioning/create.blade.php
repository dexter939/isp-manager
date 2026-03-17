@extends('layouts.contentNavbarLayout')
@section('title', 'Nuovo ordine di provisioning')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('provisioning.index') }}">Provisioning</a></li>
  <li class="breadcrumb-item active">Nuovo ordine</li>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Nuovo ordine carrier</h4>
      <p class="text-muted mb-0">Crea un ordine in bozza da inviare a OpenFiber, FiberCop o Fastweb</p>
    </div>
    <a href="{{ route('provisioning.index') }}" class="btn btn-outline-secondary">
      <i class="ri-arrow-left-line me-1"></i>Indietro
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-line me-1"></i>
      <strong>Errori di validazione:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row">
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0"><i class="ri-signal-tower-line me-2"></i>Dettagli ordine</h6>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('provisioning.store') }}" id="orderForm">
            @csrf

            {{-- Contratto --}}
            <div class="mb-4">
              <label class="form-label fw-semibold" for="contract_id">
                Contratto <span class="text-danger">*</span>
              </label>
              <select name="contract_id" id="contract_id"
                      class="form-select @error('contract_id') is-invalid @enderror"
                      required onchange="onContractChange(this)">
                <option value="">— Seleziona contratto —</option>
                @foreach($contracts as $c)
                  <option value="{{ $c->id }}"
                          data-carrier="{{ $c->carrier }}"
                          @selected(old('contract_id', $selectedContract?->id) == $c->id)>
                    {{ $c->contract_number }} — {{ $c->customer_name }}
                    ({{ ucfirst($c->carrier) }})
                  </option>
                @endforeach
              </select>
              @error('contract_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">Solo contratti attivi. Il carrier viene rilevato automaticamente dal contratto.</div>
            </div>

            {{-- Carrier (read-only, auto-filled) --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">Carrier</label>
              <div id="carrierDisplay" class="d-flex align-items-center gap-2 py-2">
                <span class="text-muted small">
                  <i class="ri-information-line me-1"></i>Seleziona un contratto per vedere il carrier
                </span>
              </div>
            </div>

            {{-- Tipo ordine --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">
                Tipo ordine <span class="text-danger">*</span>
              </label>
              <div class="row g-2">
                @php
                  $typeConfig = [
                    'activation'   => ['icon' => 'ri-add-circle-line',        'color' => 'success', 'label' => 'Attivazione',  'desc' => 'Nuova attivazione linea'],
                    'change'       => ['icon' => 'ri-edit-line',               'color' => 'warning', 'label' => 'Modifica',     'desc' => 'Cambio profilo/piano'],
                    'deactivation' => ['icon' => 'ri-subtract-line',           'color' => 'danger',  'label' => 'Disattivazione','desc' => 'Cessazione linea'],
                    'migration'    => ['icon' => 'ri-arrow-right-circle-line', 'color' => 'info',    'label' => 'Migrazione',   'desc' => 'Migrazione da altro OLO'],
                  ];
                @endphp
                @foreach($orderTypes as $type)
                  @php $cfg = $typeConfig[$type->value] ?? ['icon' => 'ri-circle-line', 'color' => 'secondary', 'label' => ucfirst($type->value), 'desc' => '']; @endphp
                  <div class="col-6">
                    <input type="radio" class="btn-check" name="order_type" id="type_{{ $type->value }}"
                           value="{{ $type->value }}"
                           @checked(old('order_type', 'activation') === $type->value)>
                    <label class="btn btn-outline-{{ $cfg['color'] }} w-100 text-start h-100 p-3"
                           for="type_{{ $type->value }}">
                      <div class="d-flex align-items-center gap-2">
                        <i class="{{ $cfg['icon'] }} fs-5"></i>
                        <div>
                          <div class="fw-semibold">{{ $cfg['label'] }}</div>
                          <div class="text-muted" style="font-size:.75rem">{{ $cfg['desc'] }}</div>
                        </div>
                      </div>
                    </label>
                  </div>
                @endforeach
              </div>
              @error('order_type')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Note --}}
            <div class="mb-4">
              <label class="form-label fw-semibold" for="notes">Note interne</label>
              <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                        rows="3" placeholder="Informazioni aggiuntive per il tecnico, riferimenti interni…">{{ old('notes') }}</textarea>
              @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="alert alert-info small">
              <i class="ri-information-line me-1"></i>
              L'ordine viene creato in stato <strong>Bozza</strong>. Potrai verificarlo e inviarlo al carrier dalla pagina di dettaglio.
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i>Crea bozza ordine
              </button>
              <a href="{{ route('provisioning.index') }}" class="btn btn-outline-secondary">Annulla</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Sidebar: info carrier --}}
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm mb-4" id="carrierInfoCard" style="display:none!important">
        <div class="card-header">
          <h6 class="mb-0" id="carrierInfoTitle"><i class="ri-information-line me-2"></i>Info carrier</h6>
        </div>
        <div class="card-body" id="carrierInfoBody"></div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0"><i class="ri-question-line me-2"></i>Guida ai tipi ordine</h6>
        </div>
        <div class="list-group list-group-flush">
          <div class="list-group-item">
            <div class="d-flex gap-2">
              <i class="ri-add-circle-line text-success fs-5 flex-shrink-0 mt-1"></i>
              <div>
                <div class="fw-semibold small">Attivazione</div>
                <div class="text-muted" style="font-size:.78rem">Ordine di attivazione nuova linea FTTH/FTTC. Richiede coordinamento appuntamento tecnico.</div>
              </div>
            </div>
          </div>
          <div class="list-group-item">
            <div class="d-flex gap-2">
              <i class="ri-edit-line text-warning fs-5 flex-shrink-0 mt-1"></i>
              <div>
                <div class="fw-semibold small">Modifica</div>
                <div class="text-muted" style="font-size:.78rem">Cambio profilo velocità o configurazione su linea già attiva.</div>
              </div>
            </div>
          </div>
          <div class="list-group-item">
            <div class="d-flex gap-2">
              <i class="ri-subtract-line text-danger fs-5 flex-shrink-0 mt-1"></i>
              <div>
                <div class="fw-semibold small">Disattivazione</div>
                <div class="text-muted" style="font-size:.78rem">Cessazione linea. La VLAN viene rilasciata al pool al completamento.</div>
              </div>
            </div>
          </div>
          <div class="list-group-item">
            <div class="d-flex gap-2">
              <i class="ri-arrow-right-circle-line text-info fs-5 flex-shrink-0 mt-1"></i>
              <div>
                <div class="fw-semibold small">Migrazione</div>
                <div class="text-muted" style="font-size:.78rem">Subentro da altro OLO. Richiede dati portabilità del vecchio operatore.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
const carrierInfo = {
  openfiber: {
    color: 'primary',
    label: 'OpenFiber',
    details: 'Rete FTTH wholesale. Ordini via SOAP XML con firma PKI. SLA attivazione: 30 giorni lavorativi.'
  },
  fibercop: {
    color: 'warning',
    label: 'FiberCop',
    details: 'Rete TIM/FiberCop. Ordini via API REST. SLA attivazione: 20 giorni lavorativi.'
  },
  fastweb: {
    color: 'info',
    label: 'Fastweb',
    details: 'Rete Fastweb B2B wholesale. Contatto diretto via email + portale partner. SLA variabile.'
  }
};

function onContractChange(sel) {
  const opt    = sel.options[sel.selectedIndex];
  const carrier = opt.dataset.carrier;
  const display = document.getElementById('carrierDisplay');
  const infoCard = document.getElementById('carrierInfoCard');
  const infoTitle = document.getElementById('carrierInfoTitle');
  const infoBody  = document.getElementById('carrierInfoBody');

  if (!carrier) {
    display.innerHTML = '<span class="text-muted small"><i class="ri-information-line me-1"></i>Seleziona un contratto per vedere il carrier</span>';
    infoCard.style.display = 'none';
    return;
  }

  const info = carrierInfo[carrier] || { color: 'secondary', label: carrier, details: '' };
  display.innerHTML = `<span class="badge bg-label-${info.color} px-3 py-2 fs-6">${info.label}</span>`;
  infoTitle.innerHTML = `<i class="ri-information-line me-2"></i>Info ${info.label}`;
  infoBody.innerHTML  = `<p class="small text-muted mb-0">${info.details}</p>`;
  infoCard.style.removeProperty('display');
}

// Esegui al caricamento se c'è già un contratto selezionato
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('contract_id');
  if (sel.value) onContractChange(sel);
});
</script>
@endpush

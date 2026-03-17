{{-- Partial riusato da create e edit --}}
@php $p = $plan; @endphp

<div class="row g-3">

  {{-- ── Dati principali ── --}}
  <div class="col-12"><h6 class="text-muted small text-uppercase mb-0">Identificazione</h6></div>

  <div class="col-12 col-md-6">
    <label class="form-label" for="name">Nome piano <span class="text-danger">*</span></label>
    <input type="text" id="name" name="name"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $p->name ?? '') }}"
           placeholder="Es: Fibra 1Gbps Business">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label" for="carrier">Carrier <span class="text-danger">*</span></label>
    <select id="carrier" name="carrier" class="form-select @error('carrier') is-invalid @enderror">
      @foreach(['openfiber' => 'Open Fiber', 'fibercop' => 'FiberCop', 'fastweb' => 'Fastweb', 'tim' => 'TIM', 'fwa' => 'FWA proprio'] as $val => $label)
        <option value="{{ $val }}" @selected(old('carrier', $p->carrier ?? '') === $val)>{{ $label }}</option>
      @endforeach
    </select>
    @error('carrier')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label" for="technology">Tecnologia <span class="text-danger">*</span></label>
    <select id="technology" name="technology" class="form-select @error('technology') is-invalid @enderror">
      @foreach(['FTTH', 'FTTC', 'EVDSL', 'FWA', 'VDSL'] as $tech)
        <option value="{{ $tech }}" @selected(old('technology', $p->technology ?? '') === $tech)>{{ $tech }}</option>
      @endforeach
    </select>
    @error('technology')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label" for="carrier_product_code">Codice prodotto carrier</label>
    <input type="text" id="carrier_product_code" name="carrier_product_code"
           class="form-control font-monospace @error('carrier_product_code') is-invalid @enderror"
           value="{{ old('carrier_product_code', $p->carrier_product_code ?? '') }}"
           placeholder="Es: OF_FTTH_1G_B2B">
    <div class="form-text">Obbligatorio per inviare ordini al carrier.</div>
    @error('carrier_product_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- ── Banda ── --}}
  <div class="col-12 mt-2"><h6 class="text-muted small text-uppercase mb-0">Banda</h6></div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="bandwidth_dl">Download (Mbps) <span class="text-danger">*</span></label>
    <input type="number" id="bandwidth_dl" name="bandwidth_dl" min="1"
           class="form-control @error('bandwidth_dl') is-invalid @enderror"
           value="{{ old('bandwidth_dl', $p->bandwidth_dl ?? '') }}">
    @error('bandwidth_dl')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="bandwidth_ul">Upload (Mbps) <span class="text-danger">*</span></label>
    <input type="number" id="bandwidth_ul" name="bandwidth_ul" min="1"
           class="form-control @error('bandwidth_ul') is-invalid @enderror"
           value="{{ old('bandwidth_ul', $p->bandwidth_ul ?? '') }}">
    @error('bandwidth_ul')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- ── Prezzi ── --}}
  <div class="col-12 mt-2"><h6 class="text-muted small text-uppercase mb-0">Prezzi (IVA esclusa)</h6></div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="price_monthly">Canone mensile € <span class="text-danger">*</span></label>
    <div class="input-group">
      <span class="input-group-text">€</span>
      <input type="number" id="price_monthly" name="price_monthly" min="0" step="0.01"
             class="form-control @error('price_monthly') is-invalid @enderror"
             value="{{ old('price_monthly', $p->price_monthly ?? '') }}">
    </div>
    @error('price_monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="activation_fee">Costo attivazione €</label>
    <div class="input-group">
      <span class="input-group-text">€</span>
      <input type="number" id="activation_fee" name="activation_fee" min="0" step="0.01"
             class="form-control @error('activation_fee') is-invalid @enderror"
             value="{{ old('activation_fee', $p->activation_fee ?? '0') }}">
    </div>
    @error('activation_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="modem_fee">Costo modem/ONT €</label>
    <div class="input-group">
      <span class="input-group-text">€</span>
      <input type="number" id="modem_fee" name="modem_fee" min="0" step="0.01"
             class="form-control @error('modem_fee') is-invalid @enderror"
             value="{{ old('modem_fee', $p->modem_fee ?? '0') }}">
    </div>
    @error('modem_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="min_contract_months">Durata minima (mesi) <span class="text-danger">*</span></label>
    <input type="number" id="min_contract_months" name="min_contract_months" min="1"
           class="form-control @error('min_contract_months') is-invalid @enderror"
           value="{{ old('min_contract_months', $p->min_contract_months ?? '24') }}">
    @error('min_contract_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- ── SLA ── --}}
  <div class="col-12 mt-2"><h6 class="text-muted small text-uppercase mb-0">SLA</h6></div>

  <div class="col-6 col-md-4">
    <label class="form-label" for="sla_type">Tipo SLA</label>
    <select id="sla_type" name="sla_type" class="form-select">
      <option value="">Best effort (nessun SLA)</option>
      <option value="BEST_EFFORT" @selected(old('sla_type', $p->sla_type ?? '') === 'BEST_EFFORT')>Best effort (dichiarato)</option>
      <option value="PREMIUM"     @selected(old('sla_type', $p->sla_type ?? '') === 'PREMIUM')>Premium</option>
      <option value="GARANTITO"   @selected(old('sla_type', $p->sla_type ?? '') === 'GARANTITO')>Garantito</option>
    </select>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label" for="mtr_hours">MTR (ore)</label>
    <input type="number" id="mtr_hours" name="mtr_hours" min="1"
           class="form-control @error('mtr_hours') is-invalid @enderror"
           value="{{ old('mtr_hours', $p->mtr_hours ?? '') }}"
           placeholder="Es: 8">
    <div class="form-text">Mean Time to Restore</div>
    @error('mtr_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  {{-- ── Descrizione e visibilità ── --}}
  <div class="col-12 mt-2"><h6 class="text-muted small text-uppercase mb-0">Visibilità</h6></div>

  <div class="col-12 col-md-6">
    <label class="form-label" for="description">Descrizione commerciale</label>
    <textarea id="description" name="description" class="form-control" rows="3"
              placeholder="Descrizione visibile ai clienti...">{{ old('description', $p->description ?? '') }}</textarea>
  </div>

  <div class="col-12 col-md-6 d-flex flex-column gap-3 justify-content-center">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
             @checked(old('is_active', $p->is_active ?? true))>
      <label class="form-check-label" for="is_active">Piano attivo</label>
      <div class="form-text">I piani inattivi non possono essere usati per nuovi contratti.</div>
    </div>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
             @checked(old('is_public', $p->is_public ?? true))>
      <label class="form-check-label" for="is_public">Visibile in wizard contratto</label>
      <div class="form-text">Se disattivato, il piano è vendibile solo manualmente.</div>
    </div>
  </div>

</div>

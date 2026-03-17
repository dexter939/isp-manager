@extends('layouts.contentNavbarLayout')
@section('title', 'Impostazioni')

@section('breadcrumb')
  <li class="breadcrumb-item active">Impostazioni</li>
@endsection

@section('page-content')

  <x-page-header title="Impostazioni" subtitle="Configurazione tenant: dati aziendali, fatturazione, SLA, portali" />

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show small">
      <i class="ri-error-warning-line me-2"></i>
      <strong>Correggi i seguenti errori prima di salvare:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('settings.update') }}" id="settingsForm">
    @csrf @method('PUT')

    {{-- Tab nav --}}
    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-company" type="button"
                data-fields="company">
          <i class="ri-building-2-line me-1"></i>Azienda
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button"
                data-fields="billing">
          <i class="ri-bill-line me-1"></i>Fatturazione
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sdi" type="button"
                data-fields="sdi">
          <i class="ri-government-line me-1"></i>SDI / FatturaPA
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sla" type="button"
                data-fields="sla">
          <i class="ri-timer-flash-line me-1"></i>SLA &amp; Ticket
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notifications" type="button"
                data-fields="notifications,smtp">
          <i class="ri-mail-send-line me-1"></i>Notifiche
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-portal" type="button"
                data-fields="portal">
          <i class="ri-global-line me-1"></i>Portali
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button"
                data-fields="branding">
          <i class="ri-palette-line me-1"></i>Branding
        </button>
      </li>
    </ul>

    <div class="tab-content">

      {{-- ══ TAB AZIENDA ══ --}}
      <div class="tab-pane fade" id="tab-company">
        <div class="card">
          <div class="card-header fw-semibold small">Dati aziendali</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-8">
                <label class="form-label small" for="ragione_sociale">Ragione sociale</label>
                <input type="text" id="ragione_sociale" name="company[ragione_sociale]"
                       class="form-control @error('company.ragione_sociale') is-invalid @enderror"
                       value="{{ old('company.ragione_sociale', $settings['company']['ragione_sociale']) }}">
                @error('company.ragione_sociale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="piva">Partita IVA</label>
                <input type="text" id="piva" name="company[piva]"
                       class="form-control font-monospace @error('company.piva') is-invalid @enderror"
                       value="{{ old('company.piva', $settings['company']['piva']) }}"
                       maxlength="11" placeholder="12345678901">
                @error('company.piva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="cf">Codice fiscale</label>
                <input type="text" id="cf" name="company[cf]"
                       class="form-control font-monospace @error('company.cf') is-invalid @enderror"
                       value="{{ old('company.cf', $settings['company']['cf']) }}"
                       maxlength="16" style="text-transform:uppercase">
                @error('company.cf')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="rea">REA</label>
                <input type="text" id="rea" name="company[rea]"
                       class="form-control @error('company.rea') is-invalid @enderror"
                       value="{{ old('company.rea', $settings['company']['rea']) }}"
                       placeholder="MI-1234567">
                @error('company.rea')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="iban">IBAN</label>
                <input type="text" id="iban" name="company[iban]"
                       class="form-control font-monospace @error('company.iban') is-invalid @enderror"
                       value="{{ old('company.iban', $settings['company']['iban']) }}"
                       maxlength="34" placeholder="IT60 X054 2811 1010 0000 0123 456">
                @error('company.iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 mt-1"><hr class="my-1"><span class="text-muted small">Sede legale</span></div>

              <div class="col-12 col-md-6">
                <label class="form-label small" for="indirizzo">Indirizzo</label>
                <input type="text" id="indirizzo" name="company[indirizzo]"
                       class="form-control @error('company.indirizzo') is-invalid @enderror"
                       value="{{ old('company.indirizzo', $settings['company']['indirizzo']) }}"
                       placeholder="Via Roma, 1">
                @error('company.indirizzo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-4 col-md-2">
                <label class="form-label small" for="cap">CAP</label>
                <input type="text" id="cap" name="company[cap]"
                       class="form-control @error('company.cap') is-invalid @enderror"
                       value="{{ old('company.cap', $settings['company']['cap']) }}"
                       maxlength="10" placeholder="20100">
                @error('company.cap')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-8 col-md-2">
                <label class="form-label small" for="citta">Città</label>
                <input type="text" id="citta" name="company[citta]"
                       class="form-control @error('company.citta') is-invalid @enderror"
                       value="{{ old('company.citta', $settings['company']['citta']) }}">
                @error('company.citta')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-4 col-md-1">
                <label class="form-label small" for="provincia">Prov.</label>
                <input type="text" id="provincia" name="company[provincia]"
                       class="form-control text-uppercase @error('company.provincia') is-invalid @enderror"
                       value="{{ old('company.provincia', $settings['company']['provincia']) }}"
                       maxlength="2" placeholder="MI">
                @error('company.provincia')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-4 col-md-1">
                <label class="form-label small" for="paese">Paese</label>
                <input type="text" id="paese" name="company[paese]"
                       class="form-control text-uppercase @error('company.paese') is-invalid @enderror"
                       value="{{ old('company.paese', $settings['company']['paese']) }}"
                       maxlength="2">
                @error('company.paese')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 mt-1"><hr class="my-1"><span class="text-muted small">Contatti</span></div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="telefono">Telefono</label>
                <input type="tel" id="telefono" name="company[telefono]"
                       class="form-control @error('company.telefono') is-invalid @enderror"
                       value="{{ old('company.telefono', $settings['company']['telefono']) }}"
                       placeholder="+39 02 1234567">
                @error('company.telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="email_azienda">Email</label>
                <input type="email" id="email_azienda" name="company[email]"
                       class="form-control @error('company.email') is-invalid @enderror"
                       value="{{ old('company.email', $settings['company']['email']) }}">
                @error('company.email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="pec">PEC</label>
                <input type="email" id="pec" name="company[pec]"
                       class="form-control @error('company.pec') is-invalid @enderror"
                       value="{{ old('company.pec', $settings['company']['pec']) }}">
                @error('company.pec')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

            </div>
          </div>
        </div>
      </div>

      {{-- ══ TAB FATTURAZIONE ══ --}}
      <div class="tab-pane fade" id="tab-billing">
        <div class="card">
          <div class="card-header fw-semibold small">Impostazioni fatturazione</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-6 col-md-3">
                <label class="form-label small" for="billing_day">
                  Giorno fatturazione mensile
                  <i class="ri-question-line text-muted ms-1" data-bs-toggle="tooltip"
                     title="Giorno del mese in cui vengono emesse le fatture ricorrenti"></i>
                </label>
                <div class="input-group">
                  <input type="number" id="billing_day" name="billing[billing_day]" min="1" max="28"
                         class="form-control @error('billing.billing_day') is-invalid @enderror"
                         value="{{ old('billing.billing_day', $settings['billing']['billing_day']) }}">
                  <span class="input-group-text">del mese</span>
                </div>
                @error('billing.billing_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-3">
                <label class="form-label small" for="payment_days">Giorni scadenza fattura</label>
                <div class="input-group">
                  <input type="number" id="payment_days" name="billing[payment_days]" min="0" max="365"
                         class="form-control @error('billing.payment_days') is-invalid @enderror"
                         value="{{ old('billing.payment_days', $settings['billing']['payment_days']) }}">
                  <span class="input-group-text">gg</span>
                </div>
                @error('billing.payment_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small" for="iva_rate">Aliquota IVA</label>
                <div class="input-group">
                  <input type="number" id="iva_rate" name="billing[iva_rate]" min="0" max="100"
                         class="form-control @error('billing.iva_rate') is-invalid @enderror"
                         value="{{ old('billing.iva_rate', $settings['billing']['iva_rate']) }}">
                  <span class="input-group-text">%</span>
                </div>
                @error('billing.iva_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small" for="currency">Valuta</label>
                <select id="currency" name="billing[currency]" class="form-select">
                  <option value="EUR" @selected(old('billing.currency', $settings['billing']['currency']) === 'EUR')>EUR €</option>
                  <option value="USD" @selected(old('billing.currency', $settings['billing']['currency']) === 'USD')>USD $</option>
                  <option value="GBP" @selected(old('billing.currency', $settings['billing']['currency']) === 'GBP')>GBP £</option>
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="regime_iva">Regime IVA</label>
                <select id="regime_iva" name="billing[regime_iva]"
                        class="form-select @error('billing.regime_iva') is-invalid @enderror">
                  <option value="ordinario"   @selected(old('billing.regime_iva', $settings['billing']['regime_iva']) === 'ordinario')>Regime ordinario</option>
                  <option value="forfettario" @selected(old('billing.regime_iva', $settings['billing']['regime_iva']) === 'forfettario')>Forfettario (art. 1 L. 190/2014)</option>
                  <option value="minimi"      @selected(old('billing.regime_iva', $settings['billing']['regime_iva']) === 'minimi')>Regime dei minimi</option>
                </select>
                @error('billing.regime_iva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-3">
                <label class="form-label small" for="invoice_prefix">
                  Prefisso numerazione
                  <i class="ri-question-line text-muted ms-1" data-bs-toggle="tooltip"
                     title="Es: FT → FT-2025-0001"></i>
                </label>
                <input type="text" id="invoice_prefix" name="billing[invoice_prefix]" maxlength="10"
                       class="form-control font-monospace @error('billing.invoice_prefix') is-invalid @enderror"
                       value="{{ old('billing.invoice_prefix', $settings['billing']['invoice_prefix']) }}"
                       placeholder="FT">
                @error('billing.invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <div class="alert alert-info mb-0 small">
                  <i class="ri-information-line me-1"></i>
                  Formato: <code>{{ $settings['billing']['invoice_prefix'] }}-{{ now()->year }}-0001</code>
                  &nbsp;·&nbsp; Scadenza: <strong>{{ $settings['billing']['payment_days'] }} gg</strong>
                  &nbsp;·&nbsp; IVA: <strong>{{ $settings['billing']['iva_rate'] }}%</strong>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      {{-- ══ TAB SDI ══ --}}
      <div class="tab-pane fade" id="tab-sdi">
        <div class="card">
          <div class="card-header fw-semibold small">Fatturazione elettronica — SDI / FatturaPA</div>
          <div class="card-body">
            <div class="alert alert-warning small">
              <i class="ri-alert-line me-1"></i>
              Questi dati vengono usati per generare le fatture XML da trasmettere al Sistema di Interscambio (SDI).
              Il codice <strong>0000000</strong> indica consegna tramite PEC.
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label small" for="sdi_codice">Codice destinatario SDI</label>
                <input type="text" id="sdi_codice" name="sdi[codice_destinatario]" maxlength="7"
                       class="form-control font-monospace text-uppercase @error('sdi.codice_destinatario') is-invalid @enderror"
                       value="{{ old('sdi.codice_destinatario', $settings['sdi']['codice_destinatario']) }}"
                       placeholder="0000000">
                @error('sdi.codice_destinatario')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small" for="sdi_pec">PEC per ricezione SDI</label>
                <input type="email" id="sdi_pec" name="sdi[pec]"
                       class="form-control @error('sdi.pec') is-invalid @enderror"
                       value="{{ old('sdi.pec', $settings['sdi']['pec']) }}"
                       placeholder="fatture@pec.ispazienda.it">
                <div class="form-text">Obbligatoria se codice destinatario = 0000000</div>
                @error('sdi.pec')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ══ TAB SLA & TICKET ══ --}}
      <div class="tab-pane fade" id="tab-sla">
        <div class="card mb-4">
          <div class="card-header fw-semibold small">
            <i class="ri-timer-flash-line me-1 text-danger"></i>Soglie SLA per priorità
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">
              Definisci i tempi massimi di prima risposta e risoluzione per ciascuna priorità.
              Questi valori sovrascrivono i default di sistema per questo tenant.
            </p>

            @php
              $priorities = [
                'critical' => ['label' => 'Critica',  'color' => 'danger'],
                'high'     => ['label' => 'Alta',     'color' => 'warning'],
                'medium'   => ['label' => 'Media',    'color' => 'info'],
                'low'      => ['label' => 'Bassa',    'color' => 'secondary'],
              ];
            @endphp

            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="small fw-semibold">Priorità</th>
                    <th class="small fw-semibold text-center">Prima risposta (ore)</th>
                    <th class="small fw-semibold text-center">Risoluzione (ore)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($priorities as $key => $meta)
                    <tr>
                      <td>
                        <span class="badge bg-label-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                      </td>
                      <td class="text-center" style="width:200px">
                        <div class="input-group input-group-sm justify-content-center" style="max-width:120px;margin:auto">
                          <input type="number" name="sla[{{ $key }}_first_response_hours]"
                                 min="1" max="9999"
                                 class="form-control text-center @error('sla.'.$key.'_first_response_hours') is-invalid @enderror"
                                 value="{{ old('sla.'.$key.'_first_response_hours', $settings['sla'][$key.'_first_response_hours']) }}">
                          <span class="input-group-text">h</span>
                        </div>
                        @error('sla.'.$key.'_first_response_hours')
                          <div class="text-danger" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                      </td>
                      <td class="text-center" style="width:200px">
                        <div class="input-group input-group-sm justify-content-center" style="max-width:120px;margin:auto">
                          <input type="number" name="sla[{{ $key }}_resolution_hours]"
                                 min="1" max="9999"
                                 class="form-control text-center @error('sla.'.$key.'_resolution_hours') is-invalid @enderror"
                                 value="{{ old('sla.'.$key.'_resolution_hours', $settings['sla'][$key.'_resolution_hours']) }}">
                          <span class="input-group-text">h</span>
                        </div>
                        @error('sla.'.$key.'_resolution_hours')
                          <div class="text-danger" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold small">
            <i class="ri-time-line me-1 text-info"></i>Orario lavorativo
          </div>
          <div class="card-body">
            <div class="row g-3 align-items-center">
              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="business_hours_only"
                         name="sla[business_hours_only]" value="1"
                         @checked(old('sla.business_hours_only', $settings['sla']['business_hours_only']))>
                  <label class="form-check-label small fw-semibold" for="business_hours_only">
                    Calcola SLA solo nelle ore lavorative
                  </label>
                </div>
                <div class="form-text">Se attivo, il countdown SLA si sospende fuori dall'orario configurato.</div>
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small" for="business_hours_start">Inizio orario</label>
                <input type="time" id="business_hours_start" name="sla[business_hours_start]"
                       class="form-control"
                       value="{{ old('sla.business_hours_start', $settings['sla']['business_hours_start']) }}">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small" for="business_hours_end">Fine orario</label>
                <input type="time" id="business_hours_end" name="sla[business_hours_end]"
                       class="form-control"
                       value="{{ old('sla.business_hours_end', $settings['sla']['business_hours_end']) }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ══ TAB NOTIFICHE ══ --}}
      <div class="tab-pane fade" id="tab-notifications">

        {{-- Email mittente --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold small">
            <i class="ri-mail-send-line me-1 text-primary"></i>Email mittente
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small" for="email_from">Indirizzo From</label>
                <input type="email" id="email_from" name="notifications[email_from]"
                       class="form-control @error('notifications.email_from') is-invalid @enderror"
                       value="{{ old('notifications.email_from', $settings['notifications']['email_from']) }}"
                       placeholder="noreply@ispazienda.it">
                @error('notifications.email_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small" for="email_from_name">Nome mittente</label>
                <input type="text" id="email_from_name" name="notifications[email_from_name]"
                       class="form-control @error('notifications.email_from_name') is-invalid @enderror"
                       value="{{ old('notifications.email_from_name', $settings['notifications']['email_from_name']) }}"
                       placeholder="ISP Azienda">
                @error('notifications.email_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            {{-- Test email --}}
            <hr class="my-3">
            <form method="POST" action="{{ route('settings.test-email') }}" class="row g-2 align-items-end">
              @csrf
              <div class="col-12 col-md-5">
                <label class="form-label small">Invia email di test</label>
                <input type="email" name="test_email" class="form-control form-control-sm"
                       value="{{ auth()->user()->email }}" required>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                  <i class="ri-send-plane-line me-1"></i>Invia test
                </button>
              </div>
              <div class="col-12">
                <div class="form-text">Invia un'email di prova per verificare la configurazione SMTP.</div>
              </div>
            </form>
          </div>
        </div>

        {{-- SMTP override --}}
        <div class="card">
          <div class="card-header fw-semibold small d-flex align-items-center justify-content-between">
            <span><i class="ri-server-line me-1 text-warning"></i>SMTP personalizzato</span>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="smtp_enabled"
                     name="smtp[enabled]" value="1"
                     @checked(old('smtp.enabled', $settings['smtp']['enabled']))
                     onchange="document.getElementById('smtpFields').classList.toggle('d-none', !this.checked)">
              <label class="form-check-label small" for="smtp_enabled">Usa SMTP personalizzato</label>
            </div>
          </div>
          <div class="card-body" id="smtpFields"
               class="{{ $settings['smtp']['enabled'] ? '' : 'd-none' }}"
               @if(!old('smtp.enabled', $settings['smtp']['enabled'])) style="display:none!important" @endif>
            <div class="alert alert-info small mb-3">
              <i class="ri-information-line me-1"></i>
              Configurazione SMTP per-tenant. Se disabilitato, viene usato l'SMTP globale del file <code>.env</code>.
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-5">
                <label class="form-label small" for="smtp_host">Host SMTP</label>
                <input type="text" id="smtp_host" name="smtp[host]"
                       class="form-control font-monospace @error('smtp.host') is-invalid @enderror"
                       value="{{ old('smtp.host', $settings['smtp']['host']) }}"
                       placeholder="smtp.mailgun.org">
                @error('smtp.host')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small" for="smtp_port">Porta</label>
                <input type="number" id="smtp_port" name="smtp[port]"
                       class="form-control @error('smtp.port') is-invalid @enderror"
                       value="{{ old('smtp.port', $settings['smtp']['port']) }}"
                       min="1" max="65535">
                @error('smtp.port')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small" for="smtp_encryption">Cifratura</label>
                <select id="smtp_encryption" name="smtp[encryption]" class="form-select">
                  @foreach(['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Nessuna'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('smtp.encryption', $settings['smtp']['encryption']) === $val)>
                      {{ $lbl }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label small" for="smtp_username">Username</label>
                <input type="text" id="smtp_username" name="smtp[username]"
                       class="form-control @error('smtp.username') is-invalid @enderror"
                       value="{{ old('smtp.username', $settings['smtp']['username']) }}"
                       autocomplete="off">
                @error('smtp.username')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label small" for="smtp_password">Password</label>
                <div class="input-group">
                  <input type="password" id="smtp_password" name="smtp[password]"
                         class="form-control @error('smtp.password') is-invalid @enderror"
                         value="{{ old('smtp.password', $settings['smtp']['password']) }}"
                         autocomplete="new-password"
                         placeholder="{{ $settings['smtp']['password'] ? '••••••••' : '' }}">
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="const f=document.getElementById('smtp_password');f.type=f.type==='password'?'text':'password'">
                    <i class="ri-eye-line"></i>
                  </button>
                </div>
                @error('smtp.password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($settings['smtp']['password'])
                  <div class="form-text text-warning">
                    <i class="ri-lock-line me-1"></i>Password salvata. Lascia vuoto per non modificarla.
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>

      </div>

      {{-- ══ TAB PORTALI ══ --}}
      <div class="tab-pane fade" id="tab-portal">
        <div class="row g-4">

          {{-- Portale clienti --}}
          <div class="col-12 col-lg-6">
            <div class="card h-100">
              <div class="card-header fw-semibold small d-flex align-items-center justify-content-between">
                <span><i class="ri-user-line me-1 text-primary"></i>Portale Clienti</span>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="customer_portal_enabled"
                         name="portal[customer_portal_enabled]" value="1"
                         @checked(old('portal.customer_portal_enabled', $settings['portal']['customer_portal_enabled']))>
                  <label class="form-check-label small" for="customer_portal_enabled">Abilitato</label>
                </div>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <div class="text-muted small mb-2">URL accesso clienti:</div>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text font-monospace">
                      {{ url('/portal/login') }}
                    </span>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="navigator.clipboard.writeText('{{ url('/portal/login') }}')">
                      <i class="ri-file-copy-line"></i>
                    </button>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small" for="customer_portal_welcome">
                    Messaggio di benvenuto
                  </label>
                  <textarea id="customer_portal_welcome" name="portal[customer_portal_welcome]"
                            class="form-control @error('portal.customer_portal_welcome') is-invalid @enderror"
                            rows="3" maxlength="500"
                            placeholder="Benvenuto nel portale clienti di {{ $tenant->name }}...">{{ old('portal.customer_portal_welcome', $settings['portal']['customer_portal_welcome']) }}</textarea>
                  <div class="form-text">Mostrato nella pagina di login del portale clienti.</div>
                  @error('portal.customer_portal_welcome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          </div>

          {{-- Portale agenti --}}
          <div class="col-12 col-lg-6">
            <div class="card h-100">
              <div class="card-header fw-semibold small d-flex align-items-center justify-content-between">
                <span><i class="ri-shake-hands-line me-1 text-success"></i>Portale Agenti</span>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="agent_portal_enabled"
                         name="portal[agent_portal_enabled]" value="1"
                         @checked(old('portal.agent_portal_enabled', $settings['portal']['agent_portal_enabled']))>
                  <label class="form-check-label small" for="agent_portal_enabled">Abilitato</label>
                </div>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <div class="text-muted small mb-2">URL accesso agenti:</div>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text font-monospace">
                      {{ url('/agent-portal/login') }}
                    </span>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="navigator.clipboard.writeText('{{ url('/agent-portal/login') }}')">
                      <i class="ri-file-copy-line"></i>
                    </button>
                  </div>
                </div>
                <div class="alert alert-info small mb-0">
                  <i class="ri-information-line me-1"></i>
                  Per abilitare un agente al portale, imposta la sua email e password
                  dalla sezione <a href="{{ route('admin.agents.index') }}">Agenti</a>.
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- ══ TAB BRANDING ══ --}}
      <div class="tab-pane fade" id="tab-branding">
        <div class="card">
          <div class="card-header fw-semibold small">Branding e aspetto</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label small" for="display_name">Nome visualizzato nell'interfaccia</label>
                <input type="text" id="display_name" name="branding[display_name]"
                       class="form-control @error('branding.display_name') is-invalid @enderror"
                       value="{{ old('branding.display_name', $settings['branding']['display_name']) }}"
                       placeholder="{{ $tenant->name }}">
                @error('branding.display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small" for="logo_url">URL logo (PNG/SVG, 200×50px)</label>
                <input type="url" id="logo_url" name="branding[logo_url]"
                       class="form-control @error('branding.logo_url') is-invalid @enderror"
                       value="{{ old('branding.logo_url', $settings['branding']['logo_url']) }}"
                       placeholder="https://cdn.ispazienda.it/logo.svg">
                @error('branding.logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small" for="primary_color">Colore primario</label>
                <div class="input-group">
                  <input type="color" id="primary_color_picker"
                         value="{{ old('branding.primary_color', $settings['branding']['primary_color']) }}"
                         class="form-control form-control-color"
                         oninput="document.getElementById('primary_color').value = this.value">
                  <input type="text" id="primary_color" name="branding[primary_color]"
                         class="form-control font-monospace @error('branding.primary_color') is-invalid @enderror"
                         value="{{ old('branding.primary_color', $settings['branding']['primary_color']) }}"
                         maxlength="7" placeholder="#696cff"
                         oninput="document.getElementById('primary_color_picker').value = this.value">
                </div>
                @error('branding.primary_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              @if(!empty($settings['branding']['logo_url']))
                <div class="col-12">
                  <label class="form-label small">Anteprima logo</label>
                  <div class="border rounded p-3 bg-white d-inline-block">
                    <img src="{{ $settings['branding']['logo_url'] }}" alt="Logo"
                         style="max-height:50px;max-width:200px">
                  </div>
                </div>
              @endif

            </div>
          </div>
        </div>
      </div>

    </div>{{-- /tab-content --}}

    <div class="d-flex gap-2 mt-4">
      <button type="submit" class="btn btn-primary">
        <i class="ri-save-line me-1"></i>Salva impostazioni
      </button>
    </div>

  </form>

@endsection

@push('scripts')
<script>
// Auto-attiva il tab che contiene errori di validazione
(function () {
  const errorFields = @json(array_keys($errors->toArray()));
  if (!errorFields.length) {
    // Default: prima tab
    document.querySelector('#settingsTabs .nav-link')?.click();
    return;
  }
  // Trova quale tab contiene il primo errore
  const tabs = document.querySelectorAll('#settingsTabs .nav-link');
  let activated = false;
  tabs.forEach(tab => {
    const fields = (tab.dataset.fields || '').split(',');
    const hasError = errorFields.some(f => fields.some(g => f.startsWith(g)));
    if (hasError && !activated) {
      tab.click();
      activated = true;
    }
  });
  if (!activated) {
    document.querySelector('#settingsTabs .nav-link')?.click();
  }
})();

// Persist active tab in sessionStorage
document.querySelectorAll('#settingsTabs .nav-link').forEach(btn => {
  btn.addEventListener('shown.bs.tab', () => sessionStorage.setItem('settingsTab', btn.dataset.bsTarget));
});

// Ripristina tab precedente se nessun errore
@if(!$errors->any())
const saved = sessionStorage.getItem('settingsTab');
if (saved) {
  const btn = document.querySelector(`#settingsTabs [data-bs-target="${saved}"]`);
  if (btn) btn.click();
}
@endif

// Tooltip Bootstrap
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush

@extends('layouts.contentNavbarLayout')
@section('title', 'Elevazione WISP')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('coverage.index') }}">Copertura</a></li>
  <li class="breadcrumb-item active">Elevazione WISP</li>
@endsection

@section('page-content')

<x-page-header title="Elevazione WISP" subtitle="Calcola il profilo di elevazione e verifica la linea di vista tra sito e cliente" />

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

<div class="row g-4">

  {{-- Form calcolo --}}
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-radar-line me-2"></i>Nuovo calcolo profilo</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('coverage.elevation.calculate') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold small">Sito di rete (antenna) *</label>
            <select name="network_site_id" class="form-select form-select-sm @error('network_site_id') is-invalid @enderror" required>
              <option value="">— Seleziona sito —</option>
              @foreach($sites as $site)
                <option value="{{ $site->id }}" @selected(old('network_site_id') == $site->id)>
                  {{ $site->name }}
                  @if($site->altitude_meters) ({{ $site->altitude_meters }}m slm) @endif
                </option>
              @endforeach
            </select>
            @error('network_site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($sites->isEmpty())
              <div class="form-text text-warning">
                <i class="ri-alert-line me-1"></i>Nessun sito con coordinate GPS configurato.
              </div>
            @endif
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Coordinate cliente</label>
            <div class="row g-2">
              <div class="col-6">
                <input type="number" name="customer_lat" step="0.00001" class="form-control form-control-sm @error('customer_lat') is-invalid @enderror"
                       placeholder="Latitudine" value="{{ old('customer_lat') }}" required>
                @error('customer_lat')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-6">
                <input type="number" name="customer_lon" step="0.00001" class="form-control form-control-sm @error('customer_lon') is-invalid @enderror"
                       placeholder="Longitudine" value="{{ old('customer_lon') }}" required>
                @error('customer_lon')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Indirizzo cliente (opzionale)</label>
            <input type="text" name="customer_address" class="form-control form-control-sm"
                   placeholder="Via Roma 1, Milano" value="{{ old('customer_address') }}">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold small">Altezza antenna (m) *</label>
              <input type="number" name="antenna_height_m" class="form-control form-control-sm @error('antenna_height_m') is-invalid @enderror"
                     min="1" max="100" value="{{ old('antenna_height_m', 10) }}" required>
              @error('antenna_height_m')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold small">Altezza CPE (m) *</label>
              <input type="number" name="cpe_height_m" class="form-control form-control-sm @error('cpe_height_m') is-invalid @enderror"
                     min="1" max="30" value="{{ old('cpe_height_m', 3) }}" required>
              @error('cpe_height_m')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold small">Frequenza radio (GHz) — per zona Fresnel</label>
            <input type="number" name="frequency_ghz" step="0.1" class="form-control form-control-sm @error('frequency_ghz') is-invalid @enderror"
                   min="0.1" max="100" placeholder="es. 5.8" value="{{ old('frequency_ghz') }}">
            @error('frequency_ghz')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Lasciare vuoto per calcolo LOS senza Fresnel</div>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="ri-line-chart-line me-1"></i>Calcola profilo
          </button>
        </form>
      </div>
    </div>

    {{-- Info card --}}
    <div class="card shadow-sm mt-3">
      <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-2"></i>Come funziona</h6></div>
      <div class="list-group list-group-flush">
        <div class="list-group-item small">
          <i class="ri-number-1 text-primary me-2"></i>
          Seleziona il sito antenna e inserisci le coordinate GPS del cliente.
        </div>
        <div class="list-group-item small">
          <i class="ri-number-2 text-primary me-2"></i>
          Il sistema calcola il profilo di elevazione lungo il percorso (100 punti via Open-Elevation API).
        </div>
        <div class="list-group-item small">
          <i class="ri-number-3 text-primary me-2"></i>
          Verifica la linea di vista (LOS) e la zona Fresnel (se fornita la frequenza).
        </div>
        <div class="list-group-item small">
          <i class="ri-number-4 text-primary me-2"></i>
          Il risultato viene salvato in cache per 7 giorni per le stesse coordinate.
        </div>
      </div>
    </div>
  </div>

  {{-- Storico profili --}}
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header">
        <h6 class="mb-0"><i class="ri-history-line me-2"></i>Profili calcolati recenti</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Sito</th>
              <th>Indirizzo cliente</th>
              <th class="text-end">Distanza</th>
              <th class="text-center">LOS</th>
              <th class="text-center">Ostruzione</th>
              <th class="text-end">Fresnel %</th>
              <th>Calcolato il</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentProfiles as $profile)
              @php
                $hasObstruction = (bool)($profile->has_obstruction ?? false);
                $fresnelOk = !is_null($profile->fresnel_clearance_percent) && $profile->fresnel_clearance_percent >= 60;
              @endphp
              <tr>
                <td class="fw-semibold small">{{ $profile->site_name ?? '—' }}</td>
                <td class="small text-muted">
                  {{ $profile->customer_address ?? number_format($profile->customer_lat, 5) . ', ' . number_format($profile->customer_lon, 5) }}
                </td>
                <td class="text-end small">
                  {{ number_format($profile->distance_km ?? 0, 2) }} km
                </td>
                <td class="text-center">
                  @if($hasObstruction)
                    <span class="badge bg-danger"><i class="ri-close-line me-1"></i>Ostruita</span>
                  @else
                    <span class="badge bg-success"><i class="ri-check-line me-1"></i>Libera</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($hasObstruction)
                    <i class="ri-alert-fill text-danger" title="Ostruzione rilevata"></i>
                  @else
                    <i class="ri-checkbox-circle-fill text-success" title="Nessuna ostruzione"></i>
                  @endif
                </td>
                <td class="text-end small">
                  @if(!is_null($profile->fresnel_clearance_percent))
                    <span class="fw-semibold {{ $fresnelOk ? 'text-success' : 'text-warning' }}">
                      {{ $profile->fresnel_clearance_percent }}%
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="small text-muted">
                  {{ \Carbon\Carbon::parse($profile->calculated_at)->format('d/m/Y H:i') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ri-line-chart-line d-block fs-1 mb-2 opacity-25"></i>
                  Nessun profilo calcolato. Usa il form per calcolare il primo profilo.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Legenda --}}
    <div class="card shadow-sm mt-3">
      <div class="card-header"><h6 class="mb-0"><i class="ri-book-2-line me-2"></i>Legenda risultati</h6></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-start gap-2">
              <span class="badge bg-success mt-1">LOS Libera</span>
              <div class="small">Nessun ostacolo fisico tra antenna e CPE. Il collegamento dovrebbe funzionare.</div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-start gap-2">
              <span class="badge bg-danger mt-1">LOS Ostruita</span>
              <div class="small">Ostacolo rilevato nella linea di vista. Valutare antenna più alta o sito alternativo.</div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-start gap-2">
              <span class="fw-semibold text-success small mt-1">Fresnel ≥ 60%</span>
              <div class="small">Clearance Fresnel sufficiente. Buona qualità del segnale attesa.</div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-start gap-2">
              <span class="fw-semibold text-warning small mt-1">Fresnel &lt; 60%</span>
              <div class="small">Clearance Fresnel ridotta. Possibili interferenze o degradazione del segnale.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

@endsection

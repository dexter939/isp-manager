@extends('layouts.contentNavbarLayout')
@section('title', 'Nuovo agente')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.agents.index') }}">Agenti</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <x-page-header title="Nuovo agente" subtitle="Aggiungi un agente alla rete vendita" />

  <form method="POST" action="{{ route('admin.agents.store') }}">
    @csrf
    <div class="row g-4">

      {{-- Dati aziendali --}}
      <div class="col-12 col-lg-8">
        <div class="card mb-4">
          <div class="card-header fw-semibold small">Dati aziendali</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label small">Utente collegato *</label>
                <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                  <option value="">— Seleziona utente —</option>
                  @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>
                      {{ $u->name }} ({{ $u->email }})
                    </option>
                  @endforeach
                </select>
                @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Solo gli utenti senza agente associato sono mostrati.</div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">Ragione sociale *</label>
                <input type="text" name="business_name"
                       class="form-control @error('business_name') is-invalid @enderror"
                       value="{{ old('business_name') }}" required>
                @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Partita IVA</label>
                <input type="text" name="piva"
                       class="form-control @error('piva') is-invalid @enderror"
                       value="{{ old('piva') }}" maxlength="11">
                @error('piva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Codice Fiscale *</label>
                <input type="text" name="codice_fiscale"
                       class="form-control font-monospace @error('codice_fiscale') is-invalid @enderror"
                       value="{{ old('codice_fiscale') }}" maxlength="16" required>
                @error('codice_fiscale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Commissione base (%) *</label>
                <div class="input-group">
                  <input type="number" name="commission_rate" step="0.01" min="0" max="100"
                         class="form-control @error('commission_rate') is-invalid @enderror"
                         value="{{ old('commission_rate', '10.00') }}" required>
                  <span class="input-group-text">%</span>
                  @error('commission_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small">IBAN *</label>
                <input type="text" name="iban"
                       class="form-control font-monospace @error('iban') is-invalid @enderror"
                       value="{{ old('iban') }}" maxlength="34" placeholder="IT60X0542811101000000123456" required>
                @error('iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">Agente padre (sub-agente)</label>
                <select name="parent_agent_id" class="form-select">
                  <option value="">— Nessuno (agente principale) —</option>
                </select>
              </div>

            </div>
          </div>
        </div>

        {{-- Accesso portale --}}
        <div class="card">
          <div class="card-header fw-semibold small">
            <i class="ri-global-line me-1 text-success"></i>Accesso portale agenti
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small">Email portale</label>
                <input type="email" name="portal_email"
                       class="form-control @error('portal_email') is-invalid @enderror"
                       value="{{ old('portal_email') }}"
                       placeholder="lascia vuoto per non abilitare">
                @error('portal_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Se impostata, l'agente potrà accedere al portale. La password temporanea verrà generata automaticamente — impostala dalla scheda agente.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Sidebar --}}
      <div class="col-12 col-lg-4">
        <div class="card">
          <div class="card-header fw-semibold small">Riepilogo</div>
          <div class="card-body">
            <p class="small text-muted">
              Il codice agente verrà generato automaticamente al salvataggio (es. <code>AGT-0001</code>).
            </p>
            <p class="small text-muted">
              Dopo la creazione potrai impostare regole di commissione personalizzate dalla scheda dettaglio.
            </p>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary w-100">
              <i class="ri-save-line me-1"></i>Crea agente
            </button>
            <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-secondary w-100 mt-2">
              Annulla
            </a>
          </div>
        </div>
      </div>

    </div>
  </form>

@endsection

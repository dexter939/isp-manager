@extends('layouts.contentNavbarLayout')
@section('title', 'Modifica agente — ' . $agent->business_name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.agents.index') }}">Agenti</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.agents.show', $agent->id) }}">{{ $agent->code }}</a></li>
  <li class="breadcrumb-item active">Modifica</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica agente" subtitle="{{ $agent->business_name }}" />

  <div class="row g-4">

    {{-- Form modifica --}}
    <div class="col-12 col-lg-8">
      <form method="POST" action="{{ route('admin.agents.update', $agent->id) }}">
        @csrf
        @method('PUT')

        <div class="card mb-4">
          <div class="card-header fw-semibold small">Dati aziendali</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-8">
                <label class="form-label small">Ragione sociale *</label>
                <input type="text" name="business_name"
                       class="form-control @error('business_name') is-invalid @enderror"
                       value="{{ old('business_name', $agent->business_name) }}" required>
                @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Stato *</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                  @foreach(['active','inactive','suspended'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $agent->status) === $s)>{{ ucfirst($s) }}</option>
                  @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Partita IVA</label>
                <input type="text" name="piva"
                       class="form-control @error('piva') is-invalid @enderror"
                       value="{{ old('piva', $agent->piva) }}" maxlength="11">
                @error('piva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Codice Fiscale *</label>
                <input type="text" name="codice_fiscale"
                       class="form-control font-monospace @error('codice_fiscale') is-invalid @enderror"
                       value="{{ old('codice_fiscale', $agent->codice_fiscale) }}" maxlength="16" required>
                @error('codice_fiscale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label small">Commissione base (%) *</label>
                <div class="input-group">
                  <input type="number" name="commission_rate" step="0.01" min="0" max="100"
                         class="form-control @error('commission_rate') is-invalid @enderror"
                         value="{{ old('commission_rate', $agent->commission_rate) }}" required>
                  <span class="input-group-text">%</span>
                  @error('commission_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small">IBAN *</label>
                <input type="text" name="iban"
                       class="form-control font-monospace @error('iban') is-invalid @enderror"
                       value="{{ old('iban', $agent->iban) }}" maxlength="34" required>
                @error('iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">Agente padre</label>
                <select name="parent_agent_id" class="form-select">
                  <option value="">— Nessuno —</option>
                  @foreach($parentAgents as $pa)
                    <option value="{{ $pa->id }}"
                            @selected(old('parent_agent_id', $agent->parent_agent_id) == $pa->id)>
                      {{ $pa->business_name }} ({{ $pa->code }})
                    </option>
                  @endforeach
                </select>
              </div>

            </div>
          </div>
        </div>

        {{-- Portale --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold small">
            <i class="ri-global-line me-1 text-success"></i>Accesso portale agenti
          </div>
          <div class="card-body">
            <div class="col-12 col-md-6">
              <label class="form-label small">Email portale</label>
              <input type="email" name="portal_email"
                     class="form-control @error('portal_email') is-invalid @enderror"
                     value="{{ old('portal_email', $agent->portal_email) }}"
                     placeholder="lascia vuoto per disabilitare">
              @error('portal_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Salva modifiche
          </button>
          <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
      </form>
    </div>

    {{-- Sidebar: reset password portale --}}
    <div class="col-12 col-lg-4">
      @if($agent->portal_email)
      <div class="card">
        <div class="card-header fw-semibold small">
          <i class="ri-lock-password-line me-1 text-warning"></i>Password portale
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.agents.reset-password', $agent->id) }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
              <label class="form-label small">Nuova password</label>
              <input type="password" name="portal_password"
                     class="form-control @error('portal_password') is-invalid @enderror"
                     minlength="8" required>
              @error('portal_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
              <label class="form-label small">Conferma password</label>
              <input type="password" name="portal_password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-warning w-100 btn-sm">
              <i class="ri-lock-line me-1"></i>Imposta password
            </button>
          </form>
        </div>
      </div>
      @else
      <div class="card">
        <div class="card-body text-center text-muted small py-4">
          <i class="ri-global-line d-block fs-3 mb-2"></i>
          Il portale non è abilitato per questo agente.<br>
          Imposta un'email portale per abilitare l'accesso.
        </div>
      </div>
      @endif
    </div>

  </div>

@endsection

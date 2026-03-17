@extends('layouts.agent-portal')
@section('title', 'Profilo')
@section('nav_profile', 'active')

@section('content')

  <div class="mb-4">
    <h5 class="mb-0">Il mio profilo</h5>
    <p class="text-muted small">Informazioni account e sicurezza</p>
  </div>

  <div class="row g-4">

    {{-- Dati agente --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card">
        <div class="card-header bg-transparent fw-semibold small">
          <i class="ri-building-line me-1 text-primary"></i>Dati aziendali
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted small">Ragione sociale</dt>
            <dd class="col-7 small">{{ $agent->business_name }}</dd>

            <dt class="col-5 text-muted small">Codice agente</dt>
            <dd class="col-7 small"><code>{{ $agent->code }}</code></dd>

            <dt class="col-5 text-muted small">P. IVA</dt>
            <dd class="col-7 small">{{ $agent->piva ?: '—' }}</dd>

            <dt class="col-5 text-muted small">Codice Fiscale</dt>
            <dd class="col-7 small font-monospace">{{ $agent->codice_fiscale }}</dd>

            <dt class="col-5 text-muted small">IBAN</dt>
            <dd class="col-7 small font-monospace" style="font-size:.75rem">{{ $agent->iban }}</dd>

            <dt class="col-5 text-muted small">Commissione base</dt>
            <dd class="col-7 small fw-semibold text-success">{{ $agent->commission_rate }}%</dd>

            <dt class="col-5 text-muted small">Email portale</dt>
            <dd class="col-7 small">{{ $agent->portal_email }}</dd>

            <dt class="col-5 text-muted small">Ultimo accesso</dt>
            <dd class="col-7 small text-muted">
              {{ $agent->portal_last_login_at
                  ? \Carbon\Carbon::parse($agent->portal_last_login_at)->format('d/m/Y H:i')
                  : '—' }}
            </dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Cambio password --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card">
        <div class="card-header bg-transparent fw-semibold small">
          <i class="ri-lock-password-line me-1 text-warning"></i>Modifica password
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('agent-portal.password.update') }}">
            @csrf
            @method('PATCH')

            <div class="mb-3">
              <label class="form-label small">Password attuale</label>
              <input type="password" name="current_password"
                     class="form-control @error('current_password') is-invalid @enderror" required>
              @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label small">Nuova password</label>
              <input type="password" name="password"
                     class="form-control @error('password') is-invalid @enderror"
                     minlength="8" required>
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
              <label class="form-label small">Conferma nuova password</label>
              <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-warning w-100">
              <i class="ri-lock-line me-1"></i>Aggiorna password
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>

@endsection

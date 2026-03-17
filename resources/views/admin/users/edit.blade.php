@extends('layouts.contentNavbarLayout')
@section('title', 'Modifica utente')

@section('breadcrumb')
  <li class="breadcrumb-item">Amministrazione</li>
  <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utenti</a></li>
  <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica utente" subtitle="{{ $user->email }}" />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label" for="name">Nome completo *</label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="email">Email *</label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label small text-muted">Nuova password <span class="fw-normal">(lascia vuoto per non modificare)</span></label>
              </div>

              <div class="col-12 col-md-6">
                <input type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Nuova password" autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <input type="password" name="password_confirmation"
                       class="form-control" placeholder="Conferma nuova password"
                       autocomplete="new-password">
              </div>

              <div class="col-12">
                <label class="form-label">Ruoli</label>
                @php
                  $currentRoles = is_string($user->roles ?? null)
                      ? json_decode($user->roles, true)
                      : ($user->roles ?? []);
                  $currentRoles = old('roles', (array)$currentRoles);
                @endphp
                <div class="d-flex flex-wrap gap-3">
                  @foreach(['admin' => 'Admin', 'technician' => 'Tecnico', 'billing' => 'Billing', 'readonly' => 'Solo lettura'] as $value => $label)
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="roles[]"
                             id="role_{{ $value }}" value="{{ $value }}"
                             @checked(in_array($value, $currentRoles))>
                      <label class="form-check-label" for="role_{{ $value }}">{{ $label }}</label>
                    </div>
                  @endforeach
                </div>
                @error('roles')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label" for="daily_capacity_hours">Capacità giornaliera (ore)</label>
                <input type="number" id="daily_capacity_hours" name="daily_capacity_hours"
                       class="form-control @error('daily_capacity_hours') is-invalid @enderror"
                       value="{{ old('daily_capacity_hours', $user->daily_capacity_hours) }}"
                       min="1" max="24" step="0.5">
                @error('daily_capacity_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-1">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                         value="1" @checked(old('is_active', $user->is_active ?? true))>
                  <label class="form-check-label" for="is_active">Utente attivo</label>
                </div>
              </div>

              <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i>Salva modifiche
                  </button>
                  <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Annulla</a>
                </div>
                @if(auth()->id() !== $user->id)
                  <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                        onsubmit="return confirm('Eliminare l\'utente {{ addslashes($user->name) }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="ri-delete-bin-line me-1"></i>Elimina
                    </button>
                  </form>
                @endif
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

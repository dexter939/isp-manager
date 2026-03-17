@extends('layouts.contentNavbarLayout')

@section('title', 'Gestione Utenti')

@section('breadcrumb')
  <li class="breadcrumb-item">Amministrazione</li>
  <li class="breadcrumb-item active">Utenti</li>
@endsection

@section('page-content')

  <x-page-header title="Gestione Utenti" subtitle="Operatori e tecnici del tenant">
    <x-slot name="action">
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <i class="ri-user-add-line me-1"></i>Nuovo utente
      </a>
    </x-slot>
  </x-page-header>

  <x-filter-bar :resetRoute="route('admin.users.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Nome, email…" value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-3">
      <label class="form-label small">Ruolo</label>
      <select name="role" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="admin"       @selected(request('role') === 'admin')>Admin</option>
        <option value="technician"  @selected(request('role') === 'technician')>Tecnico</option>
        <option value="billing"     @selected(request('role') === 'billing')>Billing</option>
        <option value="readonly"    @selected(request('role') === 'readonly')>Solo lettura</option>
      </select>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nome</th>
              <th>Email</th>
              <th>Ruoli</th>
              <th>Capacità giorn.</th>
              <th>Ultimo accesso</th>
              <th>Stato</th>
              <th class="text-end">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users ?? [] as $user)
              <tr>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td class="small text-muted">{{ $user->email }}</td>
                <td>
                  @php
                    $roles = is_string($user->roles ?? null)
                        ? json_decode($user->roles, true)
                        : ($user->roles ?? []);
                  @endphp
                  @foreach((array)$roles as $role)
                    <span class="badge bg-label-primary me-1">{{ ucfirst($role) }}</span>
                  @endforeach
                </td>
                <td class="small text-muted text-center">
                  {{ $user->daily_capacity_hours ? $user->daily_capacity_hours . 'h' : '—' }}
                </td>
                <td class="small text-muted">
                  {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') : 'Mai' }}
                </td>
                <td>
                  @if($user->email_verified_at)
                    <span class="badge bg-success">Attivo</span>
                  @else
                    <span class="badge bg-warning text-dark">Non verificato</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('admin.users.edit', $user->id) }}"
                     class="btn btn-sm btn-outline-primary">
                    <i class="ri-pencil-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun utente trovato" icon="ri-team-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($users) && method_exists($users, 'hasPages') && $users->hasPages())
      <div class="card-footer">{{ $users->links() }}</div>
    @endif
  </div>

@endsection

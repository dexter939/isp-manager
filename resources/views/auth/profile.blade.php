@extends('layouts.contentNavbarLayout')
@section('title', 'Il mio profilo')

@section('breadcrumb')
  <li class="breadcrumb-item active">Profilo</li>
@endsection

@section('page-content')

  <x-page-header title="Il mio profilo" subtitle="Gestione account e sicurezza" />

  <div class="row g-4">

    {{-- Colonna sinistra: avatar + info + stats --}}
    <div class="col-12 col-lg-4">

      {{-- Card identità --}}
      <div class="card mb-4">
        <div class="card-body text-center py-4">
          <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3"
               style="width:72px;height:72px;font-size:2rem;line-height:72px">
            {{ strtoupper(substr($user->name, 0, 1)) }}
          </div>
          <h5 class="mb-1">{{ $user->name }}</h5>
          <p class="text-muted small mb-2">{{ $user->email }}</p>

          <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
            @foreach($roles as $role)
              <span class="badge bg-label-primary">{{ ucfirst($role) }}</span>
            @endforeach
            @if($user->is_super_admin ?? false)
              <span class="badge bg-label-danger">Super Admin</span>
            @endif
            @if(empty($roles) && !($user->is_super_admin ?? false))
              <span class="badge bg-label-secondary">Utente</span>
            @endif
          </div>

          <div class="text-muted small">
            <i class="ri-calendar-line me-1"></i>
            Membro dal {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
          </div>
        </div>
      </div>

      {{-- Stats ticket --}}
      <div class="card">
        <div class="card-header fw-semibold small">
          <i class="ri-bar-chart-line me-1 text-primary"></i>Statistiche ticket
        </div>
        <div class="card-body p-0">
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="small text-muted">Assegnati totali</span>
            <span class="fw-bold">{{ $stats['tickets_assigned'] }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="small text-muted">Aperti</span>
            <span class="fw-bold text-warning">{{ $stats['tickets_open'] }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between px-3 py-2">
            <span class="small text-muted">Risolti</span>
            <span class="fw-bold text-success">{{ $stats['tickets_resolved'] }}</span>
          </div>
        </div>
      </div>

    </div>

    {{-- Colonna destra: form + attività --}}
    <div class="col-12 col-lg-8">

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-4">
          <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      {{-- Dati personali --}}
      <div class="card mb-4" id="info">
        <div class="card-header fw-semibold small">
          <i class="ri-user-line me-1 text-primary"></i>Dati personali
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="name">Nome completo</label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="email">Email</label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              @if($user->daily_capacity_hours ?? null)
                <div class="col-12 col-md-4">
                  <label class="form-label small fw-semibold">Capacità giornaliera</label>
                  <div class="input-group">
                    <input type="text" class="form-control" value="{{ $user->daily_capacity_hours }}" disabled>
                    <span class="input-group-text">h/giorno</span>
                  </div>
                  <div class="form-text">Modificabile dall'amministratore.</div>
                </div>
              @endif
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="ri-save-line me-1"></i>Salva modifiche
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- Cambio password --}}
      <div class="card mb-4" id="password">
        <div class="card-header fw-semibold small">
          <i class="ri-lock-password-line me-1 text-warning"></i>Modifica password
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PATCH')
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold" for="current_password">Password attuale</label>
                <input type="password" id="current_password" name="current_password"
                       class="form-control @error('current_password') is-invalid @enderror" required>
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="new_password">Nuova password</label>
                <input type="password" id="new_password" name="password"
                       class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="password_confirmation">Conferma password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" required>
              </div>
            </div>
            <div class="form-text mt-2 mb-3">
              Minimo 8 caratteri con lettere maiuscole, minuscole e numeri.
            </div>
            <button type="submit" class="btn btn-warning btn-sm">
              <i class="ri-lock-line me-1"></i>Aggiorna password
            </button>
          </form>
        </div>
      </div>

      {{-- Attività recente --}}
      <div class="card">
        <div class="card-header fw-semibold small">
          <i class="ri-history-line me-1 text-info"></i>Ticket recenti assegnati
        </div>
        <div class="card-body p-0">
          @forelse($recentTickets as $t)
            @php
              $priorityColor = match($t->priority) {
                'critical' => 'danger',
                'high'     => 'warning',
                'medium'   => 'info',
                default    => 'secondary',
              };
              $statusColor = match($t->status) {
                'open'        => 'warning',
                'in_progress' => 'info',
                'resolved'    => 'success',
                'closed'      => 'secondary',
                default       => 'secondary',
              };
            @endphp
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
              <span class="bg-{{ $priorityColor }} rounded"
                    style="width:4px;height:36px;flex-shrink:0"></span>
              <div class="flex-grow-1">
                <a href="{{ route('tickets.show', $t->id) }}" class="text-decoration-none small fw-semibold">
                  {{ $t->ticket_number }} — {{ Str::limit($t->title, 55) }}
                </a>
                <div class="text-muted" style="font-size:.72rem">
                  {{ \Carbon\Carbon::parse($t->opened_at)->format('d/m/Y H:i') }}
                </div>
              </div>
              <span class="badge bg-label-{{ $statusColor }} small">
                {{ ucfirst(str_replace('_', ' ', $t->status)) }}
              </span>
            </div>
          @empty
            <p class="text-center text-muted small py-4">Nessun ticket assegnato.</p>
          @endforelse
        </div>
        @if($stats['tickets_assigned'] > 5)
          <div class="card-footer bg-transparent text-center">
            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">
              Vedi tutti i ticket
            </a>
          </div>
        @endif
      </div>

    </div>
  </div>

@endsection

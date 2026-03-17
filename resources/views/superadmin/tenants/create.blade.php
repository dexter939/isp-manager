@extends('layouts.contentNavbarLayout')
@section('title', 'Nuovo tenant')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.index') }}">Tenant</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <x-page-header title="Crea nuovo tenant" subtitle="Registra un nuovo ISP sulla piattaforma" />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <form method="POST" action="{{ route('superadmin.tenants.store') }}">
        @csrf

        {{-- Dati tenant --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">
            <i class="ri-building-4-line me-1"></i>Informazioni tenant
          </div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label" for="name">Nome ISP *</label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="Acme Broadband s.r.l." required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="slug">Slug *</label>
                <div class="input-group">
                  <span class="input-group-text text-muted small">ispmanager.local/</span>
                  <input type="text" id="slug" name="slug"
                         class="form-control font-monospace @error('slug') is-invalid @enderror"
                         value="{{ old('slug') }}" placeholder="acme-broadband">
                </div>
                <div class="form-text">Identificativo URL-safe, solo lettere, numeri e trattini.</div>
                @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="domain">Dominio personalizzato</label>
                <input type="text" id="domain" name="domain"
                       class="form-control @error('domain') is-invalid @enderror"
                       value="{{ old('domain') }}" placeholder="portale.acme.it">
                <div class="form-text">Opzionale — per white-label.</div>
                @error('domain')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

            </div>
          </div>
        </div>

        {{-- Primo admin --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">
            <i class="ri-user-star-line me-1"></i>Primo amministratore
          </div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label" for="admin_name">Nome *</label>
                <input type="text" id="admin_name" name="admin_name"
                       class="form-control @error('admin_name') is-invalid @enderror"
                       value="{{ old('admin_name') }}" required>
                @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="admin_email">Email *</label>
                <input type="email" id="admin_email" name="admin_email"
                       class="form-control @error('admin_email') is-invalid @enderror"
                       value="{{ old('admin_email') }}" required>
                @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="admin_password">Password *</label>
                <input type="password" id="admin_password" name="admin_password"
                       class="form-control @error('admin_password') is-invalid @enderror"
                       autocomplete="new-password" required>
                @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="admin_password_confirmation">Conferma password *</label>
                <input type="password" id="admin_password_confirmation" name="admin_password_confirmation"
                       class="form-control" autocomplete="new-password" required>
              </div>

            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="ri-building-4-line me-1"></i>Crea tenant
          </button>
          <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>

      </form>
    </div>
  </div>

@endsection

@push('scripts')
<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function () {
  const slugField = document.getElementById('slug');
  if (slugField.dataset.touched) return;
  slugField.value = this.value
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
});
document.getElementById('slug').addEventListener('input', function () {
  this.dataset.touched = '1';
});
</script>
@endpush

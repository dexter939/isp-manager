@extends('layouts.contentNavbarLayout')
@section('title', 'Modifica tenant')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.index') }}">Tenant</a></li>
  <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.show', $tenant->id) }}">{{ $tenant->name }}</a></li>
  <li class="breadcrumb-item active">Modifica</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica tenant" subtitle="{{ $tenant->slug }}" />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('superadmin.tenants.update', $tenant->id) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label" for="name">Nome ISP *</label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $tenant->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="slug">Slug *</label>
                <input type="text" id="slug" name="slug"
                       class="form-control font-monospace @error('slug') is-invalid @enderror"
                       value="{{ old('slug', $tenant->slug) }}" required>
                <div class="form-text">Solo lettere, numeri e trattini.</div>
                @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="domain">Dominio personalizzato</label>
                <input type="text" id="domain" name="domain"
                       class="form-control @error('domain') is-invalid @enderror"
                       value="{{ old('domain', $tenant->domain) }}" placeholder="portale.isp.it">
                @error('domain')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i>Salva modifiche
                  </button>
                  <a href="{{ route('superadmin.tenants.show', $tenant->id) }}" class="btn btn-outline-secondary">
                    Annulla
                  </a>
                </div>
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

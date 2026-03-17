@extends('layouts.contentNavbarLayout')

@section('title', 'Modifica contratto')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">Contratti</a></li>
  <li class="breadcrumb-item"><a href="{{ route('contracts.show', $contract->id) }}">#{{ $contract->id }}</a></li>
  <li class="breadcrumb-item active">Modifica</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Modifica contratto #{{ $contract->id }}</h4>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('contracts.update', $contract->id) }}">
        @csrf @method('PUT')

        <div class="row g-3">
          <div class="col-12 col-md-8">
            <label class="form-label">Indirizzo di installazione</label>
            <input type="text" name="installation_address"
                   class="form-control @error('installation_address') is-invalid @enderror"
                   value="{{ old('installation_address', $contract->installation_address) }}" required>
            @error('installation_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary">
              <i class="ri-save-line me-1"></i>Salva modifiche
            </button>
            <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-outline-secondary ms-2">Annulla</a>
          </div>
        </div>
      </form>
    </div>
  </div>

@endsection

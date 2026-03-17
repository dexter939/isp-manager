@extends('layouts.contentNavbarLayout')

@section('title', 'Nuovo cliente')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Clienti</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Nuovo cliente</h4>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('customers.store') }}">
        @csrf

        <div class="row g-3">

          <div class="col-12">
            <label class="form-label">Tipo cliente</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" value="privato" id="typePrivato"
                       @checked(old('type', 'privato') === 'privato')>
                <label class="form-check-label" for="typePrivato">Privato</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" value="azienda" id="typeAzienda"
                       @checked(old('type') === 'azienda')>
                <label class="form-check-label" for="typeAzienda">Azienda</label>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="first_name">Nome</label>
            <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                   value="{{ old('first_name') }}">
            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="last_name">Cognome</label>
            <input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                   value="{{ old('last_name') }}">
            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="codice_fiscale">Codice fiscale</label>
            <input type="text" id="codice_fiscale" name="codice_fiscale"
                   class="form-control font-monospace @error('codice_fiscale') is-invalid @enderror"
                   value="{{ old('codice_fiscale') }}" maxlength="16" style="text-transform:uppercase">
            @error('codice_fiscale')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="phone">Telefono</label>
            <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone') }}" placeholder="+39 333 1234567">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary">
              <i class="ri-save-line me-1"></i>Salva cliente
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary ms-2">Annulla</a>
          </div>

        </div>
      </form>
    </div>
  </div>

@endsection

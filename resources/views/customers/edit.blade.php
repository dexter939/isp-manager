@extends('layouts.contentNavbarLayout')

@section('title', 'Modifica cliente')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Clienti</a></li>
  <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer->id) }}">{{ $customer->ragione_sociale ?? ($customer->nome . ' ' . $customer->cognome) }}</a></li>
  <li class="breadcrumb-item active">Modifica</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica cliente" />

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('customers.update', $customer->id) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">

          <div class="col-12">
            <label class="form-label">Tipo cliente</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" value="privato" id="typePrivato"
                       @checked(old('type', $customer->type) === 'privato')>
                <label class="form-check-label" for="typePrivato">Privato</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" value="azienda" id="typeAzienda"
                       @checked(old('type', $customer->type) === 'azienda')>
                <label class="form-check-label" for="typeAzienda">Azienda</label>
              </div>
            </div>
          </div>

          {{-- Azienda --}}
          <div class="col-12 d-none" id="fields-azienda">
            <div class="row g-3">
              <div class="col-12 col-md-8">
                <label class="form-label" for="ragione_sociale">Ragione sociale</label>
                <input type="text" id="ragione_sociale" name="ragione_sociale"
                       class="form-control @error('ragione_sociale') is-invalid @enderror"
                       value="{{ old('ragione_sociale', $customer->ragione_sociale) }}">
                @error('ragione_sociale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label" for="piva">Partita IVA</label>
                <input type="text" id="piva" name="piva"
                       class="form-control font-monospace @error('piva') is-invalid @enderror"
                       value="{{ old('piva', $customer->piva) }}" maxlength="11">
                @error('piva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          {{-- Privato --}}
          <div class="col-12" id="fields-privato">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" id="nome" name="nome"
                       class="form-control @error('nome') is-invalid @enderror"
                       value="{{ old('nome', $customer->nome) }}">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label" for="cognome">Cognome</label>
                <input type="text" id="cognome" name="cognome"
                       class="form-control @error('cognome') is-invalid @enderror"
                       value="{{ old('cognome', $customer->cognome) }}">
                @error('cognome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="codice_fiscale">Codice fiscale</label>
            <input type="text" id="codice_fiscale" name="codice_fiscale"
                   class="form-control font-monospace @error('codice_fiscale') is-invalid @enderror"
                   value="{{ old('codice_fiscale', $customer->codice_fiscale) }}"
                   maxlength="16" style="text-transform:uppercase">
            @error('codice_fiscale')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $customer->email) }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="telefono">Telefono</label>
            <input type="tel" id="telefono" name="telefono"
                   class="form-control @error('telefono') is-invalid @enderror"
                   value="{{ old('telefono', $customer->telefono) }}" placeholder="+39 02 1234567">
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="cellulare">Cellulare</label>
            <input type="tel" id="cellulare" name="cellulare"
                   class="form-control @error('cellulare') is-invalid @enderror"
                   value="{{ old('cellulare', $customer->cellulare) }}" placeholder="+39 333 1234567">
            @error('cellulare')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="pec">PEC</label>
            <input type="email" id="pec" name="pec"
                   class="form-control @error('pec') is-invalid @enderror"
                   value="{{ old('pec', $customer->pec) }}">
            @error('pec')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="payment_method">Metodo di pagamento</label>
            <select id="payment_method" name="payment_method" class="form-select">
              @foreach(['bonifico' => 'Bonifico bancario', 'sdd' => 'Addebito SEPA (SDD)', 'carta' => 'Carta di credito', 'contanti' => 'Contanti'] as $val => $label)
                <option value="{{ $val }}" @selected(old('payment_method', $customer->payment_method) === $val)>{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label" for="notes">Note interne</label>
            <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
          </div>

          <div class="col-12 mt-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ri-save-line me-1"></i>Salva modifiche
            </button>
            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-secondary">Annulla</a>
            <form method="POST" action="{{ route('customers.destroy', $customer->id) }}" class="ms-auto">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-outline-danger"
                      data-confirm="Eliminare definitivamente il cliente {{ $customer->ragione_sociale ?? ($customer->nome . ' ' . $customer->cognome) }}?">
                <i class="ri-delete-bin-line me-1"></i>Elimina cliente
              </button>
            </form>
          </div>

        </div>
      </form>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  const typeRadios = document.querySelectorAll('input[name="type"]');
  const fieldsAzienda = document.getElementById('fields-azienda');
  const fieldsPrivato = document.getElementById('fields-privato');

  function toggleFields() {
    const isAzienda = document.querySelector('input[name="type"]:checked')?.value === 'azienda';
    fieldsAzienda.classList.toggle('d-none', !isAzienda);
    fieldsPrivato.classList.toggle('d-none', isAzienda);
  }

  typeRadios.forEach(r => r.addEventListener('change', toggleFields));
  toggleFields();
</script>
@endpush

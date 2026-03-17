@extends('layouts.contentNavbarLayout')
@section('title', 'Nuovo ticket')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Ticket</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <x-page-header title="Apri nuovo ticket" />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('tickets.store') }}">
            @csrf

            @if($contractId)
              <input type="hidden" name="contract_id" value="{{ $contractId }}">
            @endif

            <div class="row g-3">

              <div class="col-12">
                <label class="form-label" for="title">Oggetto *</label>
                <input type="text" id="title" name="title" maxlength="255"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required
                       placeholder="Descrivi brevemente il problema">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label" for="type">Tipo</label>
                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                  <option value="">Seleziona...</option>
                  <option value="assurance"    @selected(old('type') === 'assurance')>Problema connessione</option>
                  <option value="billing"      @selected(old('type') === 'billing')>Fatturazione</option>
                  <option value="provisioning" @selected(old('type') === 'provisioning')>Attivazione/Modifica</option>
                  <option value="other"        @selected(old('type') === 'other')>Altro</option>
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label" for="priority">Priorità *</label>
                <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                  <option value="low"      @selected(old('priority','medium') === 'low')>Bassa (SLA 120h)</option>
                  <option value="medium"   @selected(old('priority','medium') === 'medium')>Media (SLA 48h)</option>
                  <option value="high"     @selected(old('priority') === 'high')>Alta (SLA 24h)</option>
                  <option value="critical" @selected(old('priority') === 'critical')>Critica (SLA 8h)</option>
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              @if(!$contractId)
              <div class="col-12 col-md-4">
                <label class="form-label" for="customer_id">Cliente</label>
                <select id="customer_id" name="customer_id" class="form-select">
                  <option value="">— Nessuno specifico —</option>
                  @foreach($customers as $cust)
                    <option value="{{ $cust->id }}" @selected(old('customer_id') == $cust->id)>
                      {{ $cust->ragione_sociale ?: trim($cust->nome . ' ' . $cust->cognome) }}
                    </option>
                  @endforeach
                </select>
              </div>
              @endif

              <div class="col-12">
                <label class="form-label" for="description">Descrizione *</label>
                <textarea id="description" name="description" rows="6"
                          class="form-control @error('description') is-invalid @enderror"
                          required placeholder="Descrivi il problema in dettaglio: quando si è verificato, sintomi, errori...">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="ri-send-plane-line me-1"></i>Apri ticket
                </button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Annulla</a>
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

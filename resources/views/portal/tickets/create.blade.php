@extends('layouts.portal')
@section('title', 'Nuova richiesta')

@section('content')
  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.tickets') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line"></i></a>
    <h5 class="mb-0">Nuova richiesta di assistenza</h5>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card portal-card">
        <div class="card-body">
          <form method="POST" action="{{ route('portal.tickets.store') }}">
            @csrf
            <div class="row g-3">

              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold small" for="title">Oggetto della richiesta *</label>
                <input type="text" id="title" name="title" maxlength="200"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}"
                       placeholder="Descrivi brevemente il problema...">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small" for="type">Categoria *</label>
                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                  <option value="">Seleziona...</option>
                  <option value="assurance"    @selected(old('type') === 'assurance')>Problema connessione</option>
                  <option value="billing"      @selected(old('type') === 'billing')>Fatturazione/Pagamenti</option>
                  <option value="provisioning" @selected(old('type') === 'provisioning')>Attivazione/Modifica</option>
                  <option value="other"        @selected(old('type') === 'other')>Altro</option>
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              @if($contracts->count() > 0)
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold small" for="contract_id">Contratto (opzionale)</label>
                <select id="contract_id" name="contract_id" class="form-select">
                  <option value="">— Nessuno specifico —</option>
                  @foreach($contracts as $c)
                    <option value="{{ $c->id }}" @selected(old('contract_id') == $c->id)>
                      {{ $c->plan_name }} ({{ strtoupper($c->carrier) }})
                    </option>
                  @endforeach
                </select>
              </div>
              @endif

              <div class="col-12 col-md-{{ $contracts->count() > 0 ? '6' : '12' }}">
                <label class="form-label fw-semibold small" for="priority">Urgenza *</label>
                <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                  <option value="low"    @selected(old('priority', 'medium') === 'low')>Bassa — non urgente</option>
                  <option value="medium" @selected(old('priority', 'medium') === 'medium')>Media — entro qualche giorno</option>
                  <option value="high"   @selected(old('priority') === 'high')>Alta — urgente</option>
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold small" for="description">Descrizione dettagliata *</label>
                <textarea id="description" name="description" rows="6"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Descrivi il problema con più dettagli possibili: quando è iniziato, cosa hai già provato, eventuali messaggi di errore...">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="ri-send-plane-line me-1"></i>Invia richiesta
                </button>
                <a href="{{ route('portal.tickets') }}" class="btn btn-outline-secondary">Annulla</a>
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

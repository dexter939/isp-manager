@extends('layouts.contentNavbarLayout')
@section('title', 'Modifica articolo')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventario</a></li>
  <li class="breadcrumb-item"><a href="{{ route('inventory.show', $item->id) }}">{{ $item->sku }}</a></li>
  <li class="breadcrumb-item active">Modifica</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica articolo" subtitle="{{ $item->name }}" />

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('inventory.update', $item->id) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label class="form-label" for="sku">SKU</label>
            <input type="text" id="sku" name="sku"
                   class="form-control font-monospace @error('sku') is-invalid @enderror"
                   value="{{ old('sku', $item->sku) }}" required>
            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-5">
            <label class="form-label" for="name">Nome</label>
            <input type="text" id="name" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $item->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="category">Categoria</label>
            <input type="text" id="category" name="category"
                   class="form-control @error('category') is-invalid @enderror"
                   value="{{ old('category', $item->category) }}" placeholder="Router, ONT, SFP...">
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label" for="quantity">Giacenza attuale</label>
            <input type="number" id="quantity" name="quantity"
                   class="form-control @error('quantity') is-invalid @enderror"
                   value="{{ old('quantity', $item->quantity) }}" min="0">
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label" for="min_quantity">Scorta minima</label>
            <input type="number" id="min_quantity" name="min_quantity"
                   class="form-control @error('min_quantity') is-invalid @enderror"
                   value="{{ old('min_quantity', $item->min_quantity) }}" min="0">
            @error('min_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i>Salva modifiche
              </button>
              <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-outline-secondary">Annulla</a>
            </div>
            <form method="POST" action="{{ route('inventory.destroy', $item->id) }}"
                  onsubmit="return confirm('Eliminare l\'articolo {{ addslashes($item->name) }}?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="ri-delete-bin-line me-1"></i>Elimina articolo
              </button>
            </form>
          </div>
        </div>
      </form>
    </div>
  </div>

@endsection

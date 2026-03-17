@extends('layouts.contentNavbarLayout')

@section('title', 'Nuovo articolo')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventario</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Nuovo articolo inventario</h4>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('inventory.store') }}">
        @csrf

        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label class="form-label" for="sku">SKU</label>
            <input type="text" id="sku" name="sku"
                   class="form-control font-monospace @error('sku') is-invalid @enderror"
                   value="{{ old('sku') }}" required>
            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-5">
            <label class="form-label" for="name">Nome</label>
            <input type="text" id="name" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="category">Categoria</label>
            <input type="text" id="category" name="category"
                   class="form-control @error('category') is-invalid @enderror"
                   value="{{ old('category') }}" placeholder="Router, ONT, SFP...">
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label" for="quantity">Giacenza iniziale</label>
            <input type="number" id="quantity" name="quantity"
                   class="form-control @error('quantity') is-invalid @enderror"
                   value="{{ old('quantity', 0) }}" min="0">
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label" for="min_quantity">Scorta minima</label>
            <input type="number" id="min_quantity" name="min_quantity"
                   class="form-control @error('min_quantity') is-invalid @enderror"
                   value="{{ old('min_quantity', 5) }}" min="0">
            @error('min_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary">
              <i class="ri-save-line me-1"></i>Salva
            </button>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary ms-2">Annulla</a>
          </div>
        </div>
      </form>
    </div>
  </div>

@endsection

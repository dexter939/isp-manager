@extends('layouts.contentNavbarLayout')

@section('title', $item->name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventario</a></li>
  <li class="breadcrumb-item active">{{ $item->sku }}</li>
@endsection

@section('page-content')

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-1">{{ $item->name }}</h4>
      <p class="text-muted mb-0"><code>{{ $item->sku }}</code> — {{ $item->category }}</p>
    </div>
    <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-outline-primary btn-sm">
      <i class="ri-pencil-line me-1"></i>Modifica
    </a>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-5">
      <div class="card">
        <div class="card-header">Dettagli articolo</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">SKU</dt>
            <dd class="col-7 font-monospace">{{ $item->sku }}</dd>
            <dt class="col-5 text-muted">Categoria</dt>
            <dd class="col-7">{{ $item->category }}</dd>
            <dt class="col-5 text-muted">Descrizione</dt>
            <dd class="col-7">{{ $item->description ?? '—' }}</dd>
            <dt class="col-5 text-muted">Giacenza</dt>
            <dd class="col-7 fw-semibold fs-5">{{ $item->quantity }}</dd>
            <dt class="col-5 text-muted">Scorta minima</dt>
            <dd class="col-7">{{ $item->min_quantity }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

@endsection

@extends('layouts.contentNavbarLayout')

@section('title', 'Inventario')

@section('breadcrumb')
  <li class="breadcrumb-item active">Inventario</li>
@endsection

@section('page-content')

  <div class="page-header d-flex justify-content-between align-items-start">
    <div>
      <h4>Inventario hardware</h4>
      <p class="text-muted mb-0">Gestione asset e magazzino</p>
    </div>
    <a href="{{ route('inventory.create') }}" class="btn btn-primary">
      <i class="ri-add-line me-1"></i>Nuovo articolo
    </a>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-primary">{{ $stats['total_items'] ?? 0 }}</div>
          <div class="text-muted small">Articoli totali</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center border-warning">
        <div class="card-body">
          <div class="fs-2 fw-bold text-warning">{{ $stats['low_stock'] ?? 0 }}</div>
          <div class="text-muted small">Scorte basse</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center border-danger">
        <div class="card-body">
          <div class="fs-2 fw-bold text-danger">{{ $stats['out_of_stock'] ?? 0 }}</div>
          <div class="text-muted small">Esauriti</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-success">{{ $stats['assigned_assets'] ?? 0 }}</div>
          <div class="text-muted small">Asset assegnati</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>SKU</th>
              <th>Nome</th>
              <th>Categoria</th>
              <th class="text-end">Giacenza</th>
              <th class="text-end">Min.</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              @php
                $stockStatus = $item->quantity <= 0
                  ? ['label' => 'Esaurito', 'badge' => 'danger']
                  : ($item->quantity <= $item->min_quantity
                    ? ['label' => 'Scorta bassa', 'badge' => 'warning']
                    : ['label' => 'Disponibile', 'badge' => 'success']);
              @endphp
              <tr>
                <td class="font-monospace small">{{ $item->sku }}</td>
                <td class="fw-medium">{{ $item->name }}</td>
                <td><span class="badge bg-light text-dark border">{{ $item->category }}</span></td>
                <td class="text-end fw-semibold">{{ $item->quantity }}</td>
                <td class="text-end text-muted">{{ $item->min_quantity }}</td>
                <td>
                  <span class="badge bg-{{ $stockStatus['badge'] }}">{{ $stockStatus['label'] }}</span>
                </td>
                <td class="text-end">
                  <a href="{{ route('inventory.show', $item) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Nessun articolo in inventario</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($items->hasPages())
      <div class="card-footer">{{ $items->links() }}</div>
    @endif
  </div>

@endsection

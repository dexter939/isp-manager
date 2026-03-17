@extends('layouts.contentNavbarLayout')
@section('title', 'Prodotti Ricarica Prepaid')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="#">Fatturazione</a></li>
  <li class="breadcrumb-item active">Prodotti Ricarica</li>
@endsection

@section('page-content')

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Prodotti Ricarica Prepaid</h4>
    <p class="text-muted mb-0">Catalogo dei tagli di ricarica disponibili ai clienti</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('billing.prepaid.wallets.index') }}" class="btn btn-outline-secondary">
      <i class="ri-wallet-3-line me-1"></i>Portafogli
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
      <i class="ri-add-line me-1"></i>Nuovo prodotto
    </button>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:40px">Ord.</th>
          <th>Nome prodotto</th>
          <th>Importo</th>
          <th>Bonus</th>
          <th>Validità</th>
          <th>Stato</th>
          <th class="text-end">Azioni</th>
        </tr>
      </thead>
      <tbody>
        @forelse($products as $prod)
          <tr class="{{ $prod->is_active ? '' : 'opacity-50' }}">
            <td class="text-muted small">{{ $prod->sort_order }}</td>
            <td class="fw-semibold">{{ $prod->name }}</td>
            <td class="fw-bold text-success">€ {{ number_format($prod->amount_amount / 100, 2, ',', '.') }}</td>
            <td>
              @if($prod->bonus_amount > 0)
                <span class="badge bg-label-info">+€ {{ number_format($prod->bonus_amount / 100, 2, ',', '.') }}</span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              @if($prod->validity_days)
                <span class="small">{{ $prod->validity_days }} giorni</span>
              @else
                <span class="text-muted small">Illimitata</span>
              @endif
            </td>
            <td>
              <form method="POST" action="{{ route('billing.prepaid.products.toggle', $prod->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm {{ $prod->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                        title="{{ $prod->is_active ? 'Disattiva' : 'Attiva' }}">
                  <i class="{{ $prod->is_active ? 'ri-checkbox-circle-line' : 'ri-forbid-line' }}"></i>
                  {{ $prod->is_active ? 'Attivo' : 'Inattivo' }}
                </button>
              </form>
            </td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <button class="btn btn-sm btn-icon btn-outline-primary" title="Modifica"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal"
                        data-id="{{ $prod->id }}"
                        data-name="{{ $prod->name }}"
                        data-amount="{{ $prod->amount_amount }}"
                        data-bonus="{{ $prod->bonus_amount }}"
                        data-validity="{{ $prod->validity_days }}"
                        data-sort="{{ $prod->sort_order }}">
                  <i class="ri-edit-line"></i>
                </button>
                <form method="POST" action="{{ route('billing.prepaid.products.destroy', $prod->id) }}"
                      onsubmit="return confirm('Eliminare il prodotto {{ addslashes($prod->name) }}?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Elimina">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="ri-price-tag-3-line fs-1 d-block mb-2 opacity-25"></i>
              Nessun prodotto configurato.
              <br><button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="ri-add-line me-1"></i>Crea il primo prodotto
              </button>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Modal crea prodotto --}}
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('billing.prepaid.products.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nuovo prodotto ricarica</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('billing.prepaid.products._form')
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Crea prodotto</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal modifica prodotto --}}
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editForm" action="">
        @csrf @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Modifica prodotto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @include('billing.prepaid.products._form')
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Salva modifiche</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', (e) => {
  const btn  = e.relatedTarget;
  const form = document.getElementById('editForm');
  form.action = '{{ url("billing/prepaid/products") }}/' + btn.dataset.id;
  form.querySelector('[name=name]').value       = btn.dataset.name;
  form.querySelector('[name=amount_amount]').value = btn.dataset.amount;
  form.querySelector('[name=bonus_amount]').value  = btn.dataset.bonus;
  form.querySelector('[name=validity_days]').value = btn.dataset.validity || '';
  form.querySelector('[name=sort_order]').value    = btn.dataset.sort;
});
</script>
@endpush

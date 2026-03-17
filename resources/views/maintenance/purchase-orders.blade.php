@extends('layouts.contentNavbarLayout')

@section('title', 'Ordini di acquisto')

@section('breadcrumb')
  <li class="breadcrumb-item">Magazzino</li>
  <li class="breadcrumb-item active">Ordini di acquisto</li>
@endsection

@section('page-content')

  <x-page-header title="Ordini di acquisto" subtitle="Gestione fornitori e riordino automatico">
    <x-slot:action>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovoOA">
        <i class="ri-add-line me-1"></i>Nuovo OA
      </button>
    </x-slot:action>
  </x-page-header>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-time-line" color="warning" label="OA in attesa" :value="$stats['pending'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-shopping-cart-line" color="primary" label="OA questo mese" :value="$stats['this_month'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-money-euro-circle-line" color="success" label="Valore totale mese"
        :value="'€ ' . number_format(($stats['total_value_cents'] ?? 0) / 100, 2, ',', '.')" />
    </div>
  </div>

  {{-- Regole riordino automatico --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="ri-repeat-line me-2"></i>Regole di riordino automatico</span>
      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalReorderRule">
        <i class="ri-add-line me-1"></i>Nuova regola
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Modello</th>
              <th>Soglia min.</th>
              <th>Qtà riordino</th>
              <th>Fornitore</th>
              <th>Auto-order</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($reorderRules ?? [] as $rule)
              <tr>
                <td class="fw-medium small">{{ $rule->inventoryModel->name ?? '—' }}</td>
                <td class="small">{{ $rule->min_stock_quantity }}</td>
                <td class="small">{{ $rule->reorder_quantity }}</td>
                <td class="small">{{ $rule->supplier->name ?? '—' }}</td>
                <td>
                  @if($rule->auto_order)
                    <span class="badge bg-success">Attivo</span>
                  @else
                    <span class="badge bg-secondary">Manuale</span>
                  @endif
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-secondary"><i class="ri-edit-line"></i></button>
                  <form method="POST" action="#" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"
                            data-confirm="Eliminare questa regola di riordino?">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessuna regola configurata" icon="ri-repeat-line" colspan="6" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Ordini di acquisto --}}
  <div class="card">
    <div class="card-header">
      <i class="ri-shopping-cart-line me-2"></i>Ordini di acquisto
    </div>
    <div class="card-body border-bottom pb-3">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-sm-3">
          <label class="form-label small">Stato</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="draft"     @selected(request('status') === 'draft')>Bozza</option>
            <option value="sent"      @selected(request('status') === 'sent')>Inviato</option>
            <option value="partial"   @selected(request('status') === 'partial')>Parziale</option>
            <option value="received"  @selected(request('status') === 'received')>Ricevuto</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Annullato</option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="ri-search-line me-1"></i>Filtra
          </button>
          <a href="{{ route('maintenance.purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
      </form>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>OA #</th>
              <th>Fornitore</th>
              <th>Articoli</th>
              <th class="text-end">Totale</th>
              <th>Stato</th>
              <th>Data</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($purchaseOrders ?? [] as $po)
              <tr>
                <td class="font-monospace small">{{ $po->po_number ?? $po->id }}</td>
                <td class="fw-medium small">{{ $po->supplier->name ?? '—' }}</td>
                <td class="small text-muted">{{ $po->items?->count() ?? 0 }} articoli</td>
                <td class="text-end fw-semibold small">
                  € {{ number_format(($po->total_amount_cents ?? 0) / 100, 2, ',', '.') }}
                </td>
                <td><x-status-badge :status="$po->status" /></td>
                <td class="small text-muted">{{ $po->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#modalOaDetail"
                          data-po-id="{{ $po->id }}"
                          title="Dettaglio">
                    <i class="ri-eye-line"></i>
                  </button>
                  @php $poStatus = $po->status->value ?? $po->status; @endphp
                  @if($poStatus === 'draft')
                    <form method="POST" action="{{ route('maintenance.purchase-orders.approve', $po->id) }}"
                          class="d-inline"
                          onsubmit="return confirm('Approvare questo ordine di acquisto?')">
                      @csrf
                      <button class="btn btn-sm btn-outline-success" title="Approva">
                        <i class="ri-check-line"></i>
                      </button>
                    </form>
                  @elseif(in_array($poStatus, ['sent', 'partial']))
                    <button class="btn btn-sm btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#modalReceive"
                            data-po-id="{{ $po->id }}"
                            title="Registra ricezione">
                      <i class="ri-truck-line"></i>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun ordine trovato" icon="ri-shopping-cart-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($purchaseOrders) && $purchaseOrders->hasPages())
      <div class="card-footer">{{ $purchaseOrders->links() }}</div>
    @endif
  </div>

@endsection

{{-- Modale dettaglio OA --}}
<div class="modal fade" id="modalOaDetail" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Dettaglio ordine di acquisto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalOaBody">
        <div class="text-center py-4 text-muted">
          <i class="ri-loader-4-line fs-2 d-block mb-2"></i>
          Caricamento...
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal: Nuovo OA --}}
<div class="modal fade" id="modalNuovoOA" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-shopping-cart-line me-2"></i>Nuovo ordine di acquisto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('maintenance.purchase-orders.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold small">Fornitore *</label>
              <select name="supplier_id" class="form-select form-select-sm" required>
                <option value="">— Seleziona fornitore —</option>
                @foreach(DB::table('suppliers')->where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get() as $sup)
                  <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold small">Consegna prevista</label>
              <input type="date" name="expected_delivery" class="form-control form-control-sm"
                     min="{{ today()->addDay()->toDateString() }}">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Note</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="small fw-semibold mb-0">Articoli *</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem">
              <i class="ri-add-line me-1"></i>Aggiungi articolo
            </button>
          </div>
          <div id="oaItems">
            <div class="row g-2 align-items-end oa-item mb-2">
              <div class="col-5">
                <label class="form-label small">Modello *</label>
                <select name="items[0][inventory_model_id]" class="form-select form-select-sm" required>
                  <option value="">— Seleziona modello —</option>
                  @foreach(DB::table('inventory_models')->where('tenant_id', auth()->user()->tenant_id)->orderBy('brand')->get() as $m)
                    <option value="{{ $m->id }}">{{ $m->brand }} {{ $m->model }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-3">
                <label class="form-label small">Quantità *</label>
                <input type="number" name="items[0][quantity_ordered]" class="form-control form-control-sm"
                       min="1" value="1" required>
              </div>
              <div class="col-3">
                <label class="form-label small">Prezzo unit. (¢)</label>
                <input type="number" name="items[0][unit_price_amount]" class="form-control form-control-sm"
                       min="0" value="0" required>
              </div>
              <div class="col-1">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" disabled>
                  <i class="ri-delete-bin-line"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Crea ordine (Bozza)
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal: registra ricezione --}}
<div class="modal fade" id="modalReceive" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-truck-line me-2"></i>Registra ricezione merce</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="receiveForm" method="POST" action="#">
        @csrf
        <div class="modal-body" id="receiveBody">
          <div class="text-center py-4 text-muted">
            <i class="ri-loader-4-line d-block fs-2 mb-2"></i>Caricamento articoli...
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-success">
            <i class="ri-truck-line me-1"></i>Conferma ricezione
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // Items dinamici per il nuovo OA
  let itemIdx = 1;
  const modelsHtml = document.querySelector('#oaItems .oa-item select[name="items[0][inventory_model_id]"]')?.innerHTML ?? '';

  document.getElementById('btnAddItem')?.addEventListener('click', () => {
    const container = document.getElementById('oaItems');
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-end oa-item mb-2';
    div.innerHTML = `
      <div class="col-5">
        <select name="items[${itemIdx}][inventory_model_id]" class="form-select form-select-sm" required>
          ${modelsHtml}
        </select>
      </div>
      <div class="col-3">
        <input type="number" name="items[${itemIdx}][quantity_ordered]" class="form-control form-control-sm" min="1" value="1" required>
      </div>
      <div class="col-3">
        <input type="number" name="items[${itemIdx}][unit_price_amount]" class="form-control form-control-sm" min="0" value="0" required>
      </div>
      <div class="col-1">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>`;
    container.appendChild(div);
    itemIdx++;
    updateRemoveButtons();
  });

  document.getElementById('oaItems')?.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-item')) {
      e.target.closest('.oa-item').remove();
      updateRemoveButtons();
    }
  });

  function updateRemoveButtons() {
    const items = document.querySelectorAll('#oaItems .oa-item');
    items.forEach(item => {
      const btn = item.querySelector('.btn-remove-item');
      if (btn) btn.disabled = items.length <= 1;
    });
  }

  // Receive modal: load items via API
  document.getElementById('modalReceive')?.addEventListener('show.bs.modal', async (e) => {
    const poId = e.relatedTarget?.dataset?.poId;
    if (!poId) return;
    document.getElementById('receiveForm').action = `{{ url('maintenance/purchase-orders') }}/${poId}/receive`;
    const body = document.getElementById('receiveBody');
    try {
      const res = await fetch(`/api/maintenance/purchase-orders/${poId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      const items = data.items ?? [];
      if (!items.length) {
        body.innerHTML = '<p class="text-muted text-center py-3">Nessun articolo in questo ordine.</p>';
        return;
      }
      body.innerHTML = `<table class="table table-sm mb-0">
        <thead class="table-light">
          <tr><th>Modello</th><th class="text-center">Ordinato</th><th class="text-center">Da ricevere</th></tr>
        </thead>
        <tbody>
          ${items.map(i => `
          <tr>
            <td class="small">${i.model_name ?? '—'}
              <input type="hidden" name="items[][item_id]" value="${i.id}">
            </td>
            <td class="text-center small">${i.quantity_ordered}</td>
            <td class="text-center">
              <input type="number" name="items[][quantity_received]" class="form-control form-control-sm"
                     style="width:80px;margin:auto" min="0" max="${i.quantity_ordered}"
                     value="${i.quantity_received ?? 0}">
            </td>
          </tr>`).join('')}
        </tbody>
      </table>`;
    } catch {
      body.innerHTML = '<div class="alert alert-danger small">Errore nel caricamento degli articoli.</div>';
    }
  });

  document.getElementById('modalOaDetail')?.addEventListener('show.bs.modal', async (e) => {
    const poId = e.relatedTarget?.dataset?.poId;
    if (!poId) return;
    const body = document.getElementById('modalOaBody');
    try {
      const res = await apiFetch(`/api/maintenance/purchase-orders/${poId}`);
      const data = await res.json();
      const items = data.items ?? [];
      body.innerHTML = `
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Modello</th><th>Qtà ordinata</th><th>Qtà ricevuta</th><th class="text-end">Prezzo unitario</th></tr>
          </thead>
          <tbody>
            ${items.map(i => `
              <tr>
                <td>${i.model_name ?? '—'}</td>
                <td>${i.quantity_ordered}</td>
                <td>${i.quantity_received ?? 0}</td>
                <td class="text-end">€ ${((i.unit_price_cents ?? 0) / 100).toFixed(2).replace('.', ',')}</td>
              </tr>
            `).join('') || '<tr><td colspan="4" class="text-center text-muted py-3">Nessun articolo</td></tr>'}
          </tbody>
        </table>`;
    } catch {
      body.innerHTML = '<div class="alert alert-danger">Errore nel caricamento dei dati.</div>';
    }
  });
</script>
@endpush

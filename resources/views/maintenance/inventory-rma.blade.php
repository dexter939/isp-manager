@extends('layouts.contentNavbarLayout')

@section('title', 'Inventario & RMA')

@section('breadcrumb')
  <li class="breadcrumb-item">Magazzino</li>
  <li class="breadcrumb-item active">Inventario & RMA</li>
@endsection

@section('page-content')

  <x-page-header title="Inventario & RMA" subtitle="Ciclo di vita dispositivi e gestione resi" />

  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-archive-line" color="primary" label="Dispositivi a magazzino" :value="$stats['total_items'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-arrow-go-back-line" color="warning" label="RMA aperti" :value="$stats['open_rma'] ?? 0" />
    </div>
    <div class="col-12 col-sm-4">
      <x-kpi-card icon="ri-percent-line" color="danger" label="Tasso difetti (%)" :value="number_format($stats['defect_rate'] ?? 0, 1) . '%'" />
    </div>
  </div>

  {{-- Tabs --}}
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabInventory" type="button">
        <i class="ri-archive-line me-1"></i>Inventario
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRmaActive" type="button">
        <i class="ri-arrow-go-back-line me-1"></i>RMA attivi
        @if(($stats['open_rma'] ?? 0) > 0)
          <span class="badge bg-warning text-dark ms-1">{{ $stats['open_rma'] }}</span>
        @endif
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRmaHistory" type="button">
        <i class="ri-history-line me-1"></i>Storico RMA
      </button>
    </li>
  </ul>

  <div class="tab-content">

    {{-- Tab Inventario --}}
    <div class="tab-pane fade show active" id="tabInventory">
      <x-filter-bar :resetRoute="route('maintenance.inventory-rma.index')">
        <div class="col-12 col-sm-4">
          <label class="form-label small">Modello</label>
          <input type="text" name="model" class="form-control form-control-sm"
                 placeholder="Cerca modello..." value="{{ request('model') }}">
        </div>
        <div class="col-6 col-sm-2">
          <label class="form-label small">Stato</label>
          <select name="item_status" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <option value="in_stock"  @selected(request('item_status') === 'in_stock')>In magazzino</option>
            <option value="deployed"  @selected(request('item_status') === 'deployed')>Dispiegato</option>
            <option value="in_rma"    @selected(request('item_status') === 'in_rma')>In RMA</option>
            <option value="dismissed" @selected(request('item_status') === 'dismissed')>Dismesso</option>
          </select>
        </div>
      </x-filter-bar>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Seriale</th>
                  <th>Modello</th>
                  <th>Stato</th>
                  <th>Cliente / Contratto</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($inventoryItems ?? [] as $item)
                  <tr>
                    <td class="font-monospace small">{{ $item->serial_number }}</td>
                    <td>{{ $item->inventoryModel->name ?? $item->model_id }}</td>
                    <td><x-status-badge :status="$item->status" /></td>
                    <td class="small text-muted">
                      {{ $item->contract->customer->full_name ?? '—' }}
                    </td>
                    <td class="text-end">
                      @if($item->status->value === 'in_stock')
                        <button class="btn btn-sm btn-outline-success" title="Deploy">
                          <i class="ri-send-plane-line"></i>
                        </button>
                      @endif
                      @if(in_array($item->status->value ?? $item->status, ['deployed']))
                        <button class="btn btn-sm btn-outline-warning" title="Apri RMA">
                          <i class="ri-arrow-go-back-line"></i>
                        </button>
                      @endif
                    </td>
                  </tr>
                @empty
                  <x-empty-state message="Nessun dispositivo trovato" icon="ri-archive-line" colspan="5" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if(isset($inventoryItems) && $inventoryItems->hasPages())
          <div class="card-footer">{{ $inventoryItems->links() }}</div>
        @endif
      </div>
    </div>

    {{-- Tab RMA attivi --}}
    <div class="tab-pane fade" id="tabRmaActive">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>RMA #</th>
                  <th>Seriale</th>
                  <th>Modello</th>
                  <th>Motivo</th>
                  <th>Stato</th>
                  <th>Aperto il</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($activeRma ?? [] as $rma)
                  <tr>
                    <td class="font-monospace small">{{ $rma->rma_number ?? $rma->id }}</td>
                    <td class="font-monospace small">{{ $rma->inventoryItem->serial_number ?? '—' }}</td>
                    <td class="small">{{ $rma->inventoryItem->inventoryModel->name ?? '—' }}</td>
                    <td class="small">{{ Str::limit($rma->reason ?? '', 40) }}</td>
                    <td><x-status-badge :status="$rma->status" /></td>
                    <td class="small text-muted">{{ $rma->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-success" title="Risolvi">
                        <i class="ri-check-double-line"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <x-empty-state message="Nessun RMA attivo" icon="ri-checkbox-circle-line" colspan="7" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Tab Storico RMA --}}
    <div class="tab-pane fade" id="tabRmaHistory">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>RMA #</th>
                  <th>Seriale</th>
                  <th>Modello</th>
                  <th>Motivo</th>
                  <th>Stato finale</th>
                  <th>Chiuso il</th>
                </tr>
              </thead>
              <tbody>
                @forelse($historyRma ?? [] as $rma)
                  <tr>
                    <td class="font-monospace small">{{ $rma->rma_number ?? $rma->id }}</td>
                    <td class="font-monospace small">{{ $rma->inventoryItem->serial_number ?? '—' }}</td>
                    <td class="small">{{ $rma->inventoryItem->inventoryModel->name ?? '—' }}</td>
                    <td class="small">{{ Str::limit($rma->reason ?? '', 40) }}</td>
                    <td><x-status-badge :status="$rma->status" /></td>
                    <td class="small text-muted">{{ $rma->resolved_at?->format('d/m/Y') ?? '—' }}</td>
                  </tr>
                @empty
                  <x-empty-state message="Nessuno storico RMA" icon="ri-history-line" colspan="6" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

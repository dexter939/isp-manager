@extends('layouts.contentNavbarLayout')

@section('title', 'Topologia di rete')

@section('breadcrumb')
  <li class="breadcrumb-item">Infrastruttura</li>
  <li class="breadcrumb-item active">Topologia</li>
@endsection

@section('page-content')

  <x-page-header title="Topologia di rete" subtitle="Grafo dispositivi e link" />

  {{-- Toolbar --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <select id="siteFilter" class="form-select form-select-sm" style="max-width:220px">
          <option value="">Tutti i siti</option>
          @foreach($sites ?? [] as $site)
            <option value="{{ $site->id }}">{{ $site->name }}</option>
          @endforeach
        </select>

        <form method="POST" action="{{ route('network.topology.discovery.run') }}" class="d-inline"
              onsubmit="return confirm('Avviare la scoperta automatica della topologia tramite LLDP/SNMP? L\'operazione potrebbe richiedere alcuni minuti.')">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="ri-radar-line me-1"></i>Scoperta automatica
          </button>
        </form>

        <button id="btnRefreshGraph" class="btn btn-sm btn-outline-secondary">
          <i class="ri-refresh-line me-1"></i>Aggiorna
        </button>

        <div class="ms-auto d-flex gap-2 small text-muted">
          <span><span class="badge bg-success me-1">&nbsp;</span>Online</span>
          <span><span class="badge bg-danger me-1">&nbsp;</span>Offline</span>
          <span><span class="badge bg-warning me-1">&nbsp;</span>Warning</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Grafo --}}
  <div class="card mb-3">
    <div class="card-body p-2">
      <div id="topology-graph" style="height:560px;border:1px solid #dee2e6;border-radius:.375rem;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">
        <div class="text-center text-muted">
          <i class="ri-git-branch-line fs-1 d-block mb-2"></i>
          <p class="mb-1">Grafo topologia</p>
          <small>Integra vis-network o D3.js per il rendering interattivo</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabella link --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Link di rete</span>
      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddLink"
              data-devices="{{ json_encode($devices->map(fn($d) => ['id' => $d->id, 'label' => $d->hostname])->values()) }}">
        <i class="ri-add-line me-1"></i>Aggiungi link
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Device A</th>
              <th>Device B</th>
              <th>Tipo</th>
              <th>Stato</th>
              <th>Latenza (ms)</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($links ?? [] as $link)
              <tr>
                <td class="small fw-medium">{{ $link->deviceA->hostname ?? $link->device_a_id }}</td>
                <td class="small fw-medium">{{ $link->deviceB->hostname ?? $link->device_b_id }}</td>
                <td>
                  <span class="badge bg-light text-dark border">{{ strtoupper($link->link_type->value ?? $link->link_type) }}</span>
                </td>
                <td><x-status-badge :status="$link->status" /></td>
                <td class="small text-muted">{{ $link->latency_ms ?? '—' }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-secondary"><i class="ri-edit-line"></i></button>
                  <form method="POST" action="{{ route('network.topology.links.destroy', $link->id) }}" class="d-inline"
                        onsubmit="return confirm('Eliminare questo link topologico?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun link configurato" icon="ri-git-branch-line" colspan="6" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($links) && $links->hasPages())
      <div class="card-footer">{{ $links->links() }}</div>
    @endif
  </div>

@endsection

{{-- Modal: Aggiungi link --}}
<div class="modal fade" id="modalAddLink" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-git-branch-line me-2"></i>Aggiungi link topologico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('network.topology.links.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Device A *</label>
            <select name="device_a_id" class="form-select form-select-sm" required>
              <option value="">— Seleziona device —</option>
              @foreach($devices as $d)
                <option value="{{ $d->id }}">{{ $d->hostname }} ({{ $d->ip_address ?? '—' }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Device B *</label>
            <select name="device_b_id" class="form-select form-select-sm" required>
              <option value="">— Seleziona device —</option>
              @foreach($devices as $d)
                <option value="{{ $d->id }}">{{ $d->hostname }} ({{ $d->ip_address ?? '—' }})</option>
              @endforeach
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold small">Interface A</label>
              <input type="text" name="source_interface" class="form-control form-control-sm" placeholder="es. ether1">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold small">Interface B</label>
              <input type="text" name="target_interface" class="form-control form-control-sm" placeholder="es. sfp1">
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold small">Tipo link *</label>
              <select name="link_type" class="form-select form-select-sm" required>
                <option value="fiber">Fibra</option>
                <option value="radio">Radio</option>
                <option value="copper">Rame</option>
                <option value="uplink">Uplink</option>
                <option value="aggregate">Aggregato</option>
                <option value="other">Altro</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold small">Banda (Mbps)</label>
              <input type="number" name="bandwidth_mbps" class="form-control form-control-sm"
                     min="1" placeholder="es. 1000">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Descrizione</label>
            <input type="text" name="description" class="form-control form-control-sm" maxlength="255">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary"><i class="ri-add-line me-1"></i>Aggiungi link</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const topologyData = @json($graphData ?? ['nodes' => [], 'edges' => []]);

  // Render graph with vis-network if available, otherwise show placeholder
  (function initTopologyGraph() {
    const container = document.getElementById('topology-graph');
    if (!container) return;

    const nodes = topologyData.nodes ?? [];
    const edges = topologyData.edges ?? [];

    if (nodes.length === 0) {
      container.innerHTML = `
        <div class="text-center text-muted p-4">
          <i class="ri-git-branch-line d-block fs-1 mb-2 opacity-25"></i>
          <p class="mb-0">Nessun dispositivo configurato.</p>
          <small>Aggiungi dispositivi di rete per visualizzare il grafo.</small>
        </div>`;
      return;
    }

    // Render a simple SVG force-graph (no external dependency required)
    let html = '<div class="p-3 overflow-auto" style="height:100%"><div class="d-flex flex-wrap gap-3 align-items-start">';
    nodes.forEach(node => {
      html += `<div class="text-center" style="width:90px">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center
             bg-label-primary border" style="width:48px;height:48px;font-size:.8rem">
          <i class="ri-router-line"></i>
        </div>
        <div class="small mt-1 fw-medium text-truncate">${node.label}</div>
        <div class="text-muted" style="font-size:.7rem">${node.title ?? ''}</div>
      </div>`;
    });
    html += `</div>
      <p class="text-muted small mt-3 mb-0">
        <i class="ri-information-line me-1"></i>
        ${edges.length} link configurati. Per il grafo interattivo integrare vis-network.
      </p>
    </div>`;
    container.innerHTML = html;
  })();

  document.getElementById('siteFilter')?.addEventListener('change', function () {
    const url = new URL(window.location);
    if (this.value) url.searchParams.set('site_id', this.value);
    else url.searchParams.delete('site_id');
    window.location = url;
  });

  document.getElementById('btnRefreshGraph')?.addEventListener('click', () => {
    window.location.reload();
  });
</script>
@endpush

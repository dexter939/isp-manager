@extends('layouts.contentNavbarLayout')

@section('title', 'Bundle')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('billing.invoices.index') }}">Fatturazione</a></li>
  <li class="breadcrumb-item active">Bundle</li>
@endsection

@section('page-content')

  <x-page-header title="Piani Bundle" subtitle="Gestione pacchetti multi-servizio">
    <x-slot:action>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bundleModal">
        <i class="ri-add-line me-1"></i>Nuovo bundle
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

  {{-- Piani bundle --}}
  <div class="card mb-4">
    <div class="card-header">Piani disponibili</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nome</th>
              <th>Servizi inclusi</th>
              <th class="text-end">Prezzo lista €</th>
              <th class="text-end">Prezzo bundle €</th>
              <th class="text-end">Sconto €</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($bundlePlans ?? [] as $plan)
              @php
                $listCents   = $plan->list_price_cents ?? 0;
                $bundleCents = $plan->bundle_price_cents ?? 0;
                $discountCents = $listCents - $bundleCents;
              @endphp
              <tr>
                <td class="fw-semibold">{{ $plan->name }}</td>
                <td>
                  @foreach($plan->items ?? [] as $item)
                    <span class="badge bg-primary rounded-pill me-1">{{ $item->service_name ?? $item->service_id }}</span>
                  @endforeach
                </td>
                <td class="text-end text-muted">€ {{ number_format($listCents / 100, 2, ',', '.') }}</td>
                <td class="text-end fw-semibold text-success">€ {{ number_format($bundleCents / 100, 2, ',', '.') }}</td>
                <td class="text-end text-danger">- € {{ number_format($discountCents / 100, 2, ',', '.') }}</td>
                <td><x-status-badge :status="$plan->is_active ? 'attivo' : 'terminato'" /></td>
                <td class="text-end">
                  <form method="POST" action="{{ route('billing.bundles.toggle', $plan->id) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-{{ $plan->is_active ? 'warning' : 'success' }}"
                            title="{{ $plan->is_active ? 'Disattiva' : 'Attiva' }}">
                      <i class="ri-{{ $plan->is_active ? 'pause' : 'play' }}-line"></i>
                    </button>
                  </form>
                  <button class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal" data-bs-target="#bundleModal"
                          data-id="{{ $plan->id }}"
                          data-name="{{ $plan->name }}"
                          data-description="{{ $plan->description }}"
                          data-price="{{ $plan->price_amount ?? $plan->bundle_price_cents ?? 0 }}"
                          data-period="{{ $plan->billing_period }}"
                          title="Modifica">
                    <i class="ri-pencil-line"></i>
                  </button>
                  <form method="POST" action="{{ route('billing.bundles.destroy', $plan->id) }}" class="d-inline"
                        onsubmit="return confirm('Eliminare il piano bundle {{ addslashes($plan->name) }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                  </form>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun piano bundle configurato" icon="ri-stack-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Abbonamenti attivi --}}
  <div class="card">
    <div class="card-header">Abbonamenti attivi</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Cliente</th>
              <th>Bundle</th>
              <th>Data attivazione</th>
              <th>Prossima fattura</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subscriptions ?? [] as $sub)
              <tr>
                <td>
                  <a href="{{ route('customers.show', $sub->customer_id ?? '#') }}" class="fw-medium text-body">
                    {{ $sub->customer->full_name ?? '—' }}
                  </a>
                </td>
                <td>{{ $sub->bundlePlan->name ?? '—' }}</td>
                <td class="small text-muted">{{ $sub->activated_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="small text-muted">{{ $sub->next_billing_date?->format('d/m/Y') ?? '—' }}</td>
                <td><x-status-badge :status="$sub->status" /></td>
              </tr>
            @empty
              <x-empty-state message="Nessun abbonamento attivo" icon="ri-user-heart-line" colspan="5" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($subscriptions) && $subscriptions->hasPages())
      <div class="card-footer">{{ $subscriptions->links() }}</div>
    @endif
  </div>

@endsection

{{-- Modal: crea / modifica bundle --}}
<div class="modal fade" id="bundleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bundleModalTitle"><i class="ri-stack-line me-2"></i>Nuovo piano bundle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="bundleForm" method="POST" action="{{ route('billing.bundles.store') }}">
        @csrf
        <span id="bundleMethodField"></span>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Nome bundle *</label>
            <input type="text" name="name" id="bundleName" class="form-control" required maxlength="150"
                   placeholder="es. Fibra All-In">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Descrizione</label>
            <textarea name="description" id="bundleDesc" class="form-control" rows="2"></textarea>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold small">Prezzo bundle (centesimi) *</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">¢</span>
                <input type="number" name="price_amount" id="bundlePrice" class="form-control"
                       min="0" required placeholder="es. 3990">
              </div>
              <div class="form-text">es. 3990 = €39,90</div>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold small">Periodo fatturazione *</label>
              <select name="billing_period" id="bundlePeriod" class="form-select form-select-sm" required>
                <option value="monthly">Mensile</option>
                <option value="bimonthly">Bimestrale</option>
                <option value="quarterly">Trimestrale</option>
                <option value="semiannual">Semestrale</option>
                <option value="annual">Annuale</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Salva
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('bundleModal')?.addEventListener('show.bs.modal', function (e) {
  const btn  = e.relatedTarget;
  const form = document.getElementById('bundleForm');
  const id   = btn?.dataset?.id;

  document.getElementById('bundleModalTitle').innerHTML =
    (id ? '<i class="ri-pencil-line me-2"></i>Modifica piano bundle' : '<i class="ri-stack-line me-2"></i>Nuovo piano bundle');
  document.getElementById('bundleName').value   = btn?.dataset?.name        ?? '';
  document.getElementById('bundleDesc').value   = btn?.dataset?.description ?? '';
  document.getElementById('bundlePrice').value  = btn?.dataset?.price       ?? '';
  document.getElementById('bundlePeriod').value = btn?.dataset?.period      ?? 'monthly';

  const methodField = document.getElementById('bundleMethodField');
  if (id) {
    form.action = `{{ url('billing/bundles') }}/${id}`;
    methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
  } else {
    form.action = '{{ route("billing.bundles.store") }}';
    methodField.innerHTML = '';
  }
});
</script>
@endpush

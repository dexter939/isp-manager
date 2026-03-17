@extends('layouts.contentNavbarLayout')

@section('title', 'Payment Matching')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('billing.invoices.index') }}">Fatturazione</a></li>
  <li class="breadcrumb-item active">Payment Matching</li>
@endsection

@section('page-content')

  <x-page-header title="Regole di Matching Pagamenti" subtitle="Abbinamento automatico transazioni bancarie">
    <x-slot:action>
      <a href="#" class="btn btn-primary"><i class="ri-add-line me-1"></i>Nuova regola</a>
    </x-slot:action>
  </x-page-header>

  <div class="row g-3">

    {{-- Simulatore --}}
    <div class="col-12 col-xl-4">
      <div class="card">
        <div class="card-header"><i class="ri-test-tube-line me-1"></i>Simulatore</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small">Importo (€)</label>
            <input type="number" id="simAmount" class="form-control" step="0.01" placeholder="Es. 29.90">
          </div>
          <div class="mb-3">
            <label class="form-label small">Descrizione causale</label>
            <input type="text" id="simDescription" class="form-control"
                   placeholder="Es. PAGAMENTO FATTURA 2024/001">
          </div>
          <button id="btnSimulate" class="btn btn-primary w-100">
            <i class="ri-play-line me-1"></i>Simula
          </button>
          <div id="simResult" class="mt-3"></div>
        </div>
      </div>
    </div>

    {{-- Regole --}}
    <div class="col-12 col-xl-8">
      <div class="card">
        <div class="card-header">Regole attive (ordinate per priorità)</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:60px">Priorità</th>
                  <th>Nome</th>
                  <th>Criteri</th>
                  <th>Azione</th>
                  <th>Attiva</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($rules ?? [] as $rule)
                  <tr>
                    <td class="text-center fw-bold">{{ $rule->priority }}</td>
                    <td class="fw-semibold">{{ $rule->name }}</td>
                    <td>
                      <ul class="mb-0 ps-3 small text-muted">
                        @foreach($rule->criteria ?? [] as $criterion)
                          <li>
                            {{ $criterion['field'] ?? '' }}
                            {{ $criterion['operator'] ?? '' }}
                            <code>{{ $criterion['value'] ?? '' }}</code>
                          </li>
                        @endforeach
                      </ul>
                    </td>
                    <td>
                      <span class="badge bg-info">{{ $rule->action->value ?? $rule->action }}</span>
                    </td>
                    <td>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               {{ $rule->is_active ? 'checked' : '' }}
                               onchange="toggleRule('{{ $rule->id }}', this.checked)">
                      </div>
                    </td>
                    <td class="text-end">
                      <a href="#" class="btn btn-sm btn-outline-primary"><i class="ri-pencil-line"></i></a>
                      <form method="POST" action="#" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"
                                data-confirm="Eliminare questa regola?">
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <x-empty-state message="Nessuna regola configurata" icon="ri-git-branch-line" colspan="6" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

@push('scripts')
<script>
  document.getElementById('btnSimulate')?.addEventListener('click', async () => {
    const amount      = document.getElementById('simAmount').value;
    const description = document.getElementById('simDescription').value;
    const btn         = document.getElementById('btnSimulate');
    btn.disabled = true;
    try {
      const res  = await apiFetch('/api/billing/payment-matching/simulate', {
        method: 'POST',
        body: JSON.stringify({
          amount_cents:  Math.round(parseFloat(amount) * 100),
          description,
          payment_date:  new Date().toISOString().slice(0, 10),
        }),
      });
      const data = await res.json();
      document.getElementById('simResult').innerHTML = data.matched
        ? `<div class="alert alert-success mb-0">Abbinata a: <strong>${data.rule_name}</strong> — Azione: <code>${data.action}</code></div>`
        : `<div class="alert alert-warning mb-0">Nessuna regola corrispondente trovata.</div>`;
    } catch {
      document.getElementById('simResult').innerHTML =
        '<div class="alert alert-danger mb-0">Errore durante la simulazione.</div>';
    } finally {
      btn.disabled = false;
    }
  });

  function toggleRule(id, active) {
    apiFetch(`/api/billing/payment-matching/rules/${id}/toggle`, {
      method: 'PATCH',
      body: JSON.stringify({ is_active: active }),
    });
  }
</script>
@endpush

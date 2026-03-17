@extends('layouts.contentNavbarLayout')
@section('title', 'Agente — ' . $agent->business_name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('admin.agents.index') }}">Agenti</a></li>
  <li class="breadcrumb-item active">{{ $agent->code }}</li>
@endsection

@section('page-content')

  <div class="d-flex align-items-start justify-content-between mb-4">
    <div>
      <h4 class="mb-1">{{ $agent->business_name }}</h4>
      <code class="text-muted">{{ $agent->code }}</code>
      @php $statusColors = ['active'=>'success','inactive'=>'secondary','suspended'=>'warning']; @endphp
      <span class="badge bg-label-{{ $statusColors[$agent->status] ?? 'secondary' }} ms-2">
        {{ ucfirst($agent->status) }}
      </span>
    </div>
    <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-outline-primary">
      <i class="ri-pencil-line me-1"></i>Modifica
    </a>
  </div>

  {{-- KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-primary">{{ $contracts->count() }}</div>
          <div class="small text-muted">Contratti assegnati</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-warning">€ {{ number_format(($kpis->pending_cents ?? 0) / 100, 2, ',', '.') }}</div>
          <div class="small text-muted">In attesa</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-info">€ {{ number_format(($kpis->accrued_cents ?? 0) / 100, 2, ',', '.') }}</div>
          <div class="small text-muted">Da liquidare</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <div class="card-body text-center py-3">
          <div class="fs-4 fw-bold text-success">€ {{ number_format(($kpis->paid_cents ?? 0) / 100, 2, ',', '.') }}</div>
          <div class="small text-muted">Pagato totale</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- Dati anagrafici --}}
    <div class="col-12 col-lg-4">
      <div class="card mb-4">
        <div class="card-header fw-semibold small">Dati anagrafici</div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Utente</dt>
            <dd class="col-7">{{ $agent->user_name }}<br><span class="text-muted" style="font-size:.75rem">{{ $agent->user_email }}</span></dd>
            <dt class="col-5 text-muted">P. IVA</dt>
            <dd class="col-7">{{ $agent->piva ?: '—' }}</dd>
            <dt class="col-5 text-muted">Cod. Fiscale</dt>
            <dd class="col-7 font-monospace">{{ $agent->codice_fiscale }}</dd>
            <dt class="col-5 text-muted">IBAN</dt>
            <dd class="col-7 font-monospace" style="font-size:.7rem">{{ $agent->iban }}</dd>
            <dt class="col-5 text-muted">Commissione</dt>
            <dd class="col-7 fw-semibold text-success">{{ $agent->commission_rate }}%</dd>
          </dl>
        </div>
      </div>

      {{-- Portale --}}
      <div class="card">
        <div class="card-header fw-semibold small">Portale agenti</div>
        <div class="card-body small">
          @if($agent->portal_email)
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-label-success">Attivo</span>
              <span>{{ $agent->portal_email }}</span>
            </div>
            @if($agent->portal_last_login_at)
              <div class="text-muted">
                Ultimo accesso: {{ \Carbon\Carbon::parse($agent->portal_last_login_at)->format('d/m/Y H:i') }}
              </div>
            @else
              <div class="text-muted">Mai effettuato il login</div>
            @endif
          @else
            <span class="badge bg-label-secondary">Non abilitato</span>
            <p class="text-muted mt-2 mb-0">
              Imposta un'email portale per abilitare l'accesso dell'agente al portale self-service.
            </p>
          @endif
        </div>
      </div>
    </div>

    {{-- Contratti assegnati --}}
    <div class="col-12 col-lg-8">
      <div class="card mb-4">
        <div class="card-header fw-semibold small">
          <i class="ri-file-text-line me-1 text-primary"></i>Contratti assegnati (ultimi 20)
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="small">Cliente</th>
                  <th class="small">Contratto</th>
                  <th class="small">Piano</th>
                  <th class="small">Stato</th>
                  <th class="small">Assegnato</th>
                </tr>
              </thead>
              <tbody>
                @forelse($contracts as $c)
                  <tr>
                    <td class="small">{{ $c->ragione_sociale ?? trim($c->nome . ' ' . $c->cognome) }}</td>
                    <td class="small"><code>{{ $c->contract_number }}</code></td>
                    <td class="small text-muted">{{ $c->plan_name ?? '—' }}</td>
                    <td><span class="badge bg-label-{{ $c->status === 'active' ? 'success' : 'secondary' }} small">{{ ucfirst($c->status) }}</span></td>
                    <td class="small text-muted">{{ \Carbon\Carbon::parse($c->assigned_at)->format('d/m/Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3 small">Nessun contratto assegnato.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Provvigioni recenti --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">
          <i class="ri-money-euro-circle-line me-1 text-success"></i>Ultime provvigioni
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="small">Cliente</th>
                  <th class="small">Contratto</th>
                  <th class="small">Periodo</th>
                  <th class="small text-end">Importo</th>
                  <th class="small">Stato</th>
                </tr>
              </thead>
              <tbody>
                @forelse($commissions as $ce)
                  <tr>
                    <td class="small">{{ $ce->ragione_sociale ?? trim($ce->nome . ' ' . $ce->cognome) }}</td>
                    <td class="small"><code>{{ $ce->contract_number }}</code></td>
                    <td class="small">{{ \Carbon\Carbon::parse($ce->period_month)->format('M Y') }}</td>
                    <td class="small text-end fw-semibold">€ {{ number_format($ce->amount_cents / 100, 2, ',', '.') }}</td>
                    <td>
                      @php $commColors = ['pending'=>'warning','accrued'=>'info','paid'=>'success']; @endphp
                      <span class="badge bg-label-{{ $commColors[$ce->status] ?? 'secondary' }} small">{{ ucfirst($ce->status) }}</span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3 small">Nessuna provvigione.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Liquidazioni --}}
      <div class="card">
        <div class="card-header fw-semibold small d-flex justify-content-between align-items-center">
          <span><i class="ri-bank-card-line me-1 text-info"></i>Liquidazioni</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="small">Periodo</th>
                  <th class="small text-end">Importo</th>
                  <th class="small">Stato</th>
                  <th class="small">Approvata</th>
                  <th class="small">Pagata</th>
                  <th class="small">Azioni</th>
                </tr>
              </thead>
              <tbody>
                @forelse($liquidations as $liq)
                  <tr>
                    <td class="small fw-semibold">{{ \Carbon\Carbon::parse($liq->period_month)->format('M Y') }}</td>
                    <td class="small text-end">€ {{ number_format($liq->total_amount_cents / 100, 2, ',', '.') }}</td>
                    <td>
                      @php $liqColors = ['draft'=>'secondary','approved'=>'primary','paid'=>'success']; @endphp
                      <span class="badge bg-label-{{ $liqColors[$liq->status] ?? 'secondary' }} small">{{ ucfirst($liq->status) }}</span>
                    </td>
                    <td class="small text-muted">{{ $liq->approved_at ? \Carbon\Carbon::parse($liq->approved_at)->format('d/m/Y') : '—' }}</td>
                    <td class="small">{{ $liq->paid_at ? \Carbon\Carbon::parse($liq->paid_at)->format('d/m/Y') : '—' }}</td>
                    <td>
                      <div class="d-flex gap-1">
                        @if($liq->status === 'draft')
                          <form method="POST" action="{{ route('admin.agents.liquidations.approve', [$agent->id, $liq->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-outline-primary" title="Approva">
                              <i class="ri-check-line"></i> Approva
                            </button>
                          </form>
                        @elseif($liq->status === 'approved')
                          <form method="POST" action="{{ route('admin.agents.liquidations.pay', [$agent->id, $liq->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-outline-success" title="Segna pagata">
                              <i class="ri-bank-card-line"></i> Pagata
                            </button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted py-3 small">Nessuna liquidazione.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

@endsection

@push('styles')
<style>
.btn-xs { padding: .1rem .4rem; font-size: .7rem; }
</style>
@endpush

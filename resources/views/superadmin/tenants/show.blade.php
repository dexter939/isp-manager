@extends('layouts.contentNavbarLayout')
@section('title', $tenant->name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('superadmin.tenants.index') }}">Tenant</a></li>
  <li class="breadcrumb-item active">{{ $tenant->name }}</li>
@endsection

@section('page-content')

  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h4 class="mb-1">
        {{ $tenant->name }}
        @if(!$tenant->is_active)
          <span class="badge bg-secondary ms-2">Sospeso</span>
        @else
          <span class="badge bg-success ms-2">Attivo</span>
        @endif
      </h4>
      <p class="text-muted mb-0">
        <code>{{ $tenant->slug }}</code>
        @if($tenant->domain)
          &nbsp;·&nbsp; <a href="https://{{ $tenant->domain }}" target="_blank" rel="noopener">{{ $tenant->domain }}</a>
        @endif
      </p>
    </div>
    <div class="d-flex gap-2">
      @if($tenant->is_active)
        <form method="POST" action="{{ route('superadmin.tenants.impersonate', $tenant->id) }}">
          @csrf
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="ri-user-shared-line me-1"></i>Impersona
          </button>
        </form>
      @endif
      <a href="{{ route('superadmin.tenants.edit', $tenant->id) }}" class="btn btn-outline-primary btn-sm">
        <i class="ri-pencil-line me-1"></i>Modifica
      </a>
      <form method="POST" action="{{ route('superadmin.tenants.toggle', $tenant->id) }}"
            onsubmit="return confirm('{{ $tenant->is_active ? 'Sospendere' : 'Riattivare' }} questo tenant?')">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-{{ $tenant->is_active ? 'danger' : 'success' }}">
          <i class="ri-{{ $tenant->is_active ? 'pause' : 'play' }}-circle-line me-1"></i>
          {{ $tenant->is_active ? 'Sospendi' : 'Riattiva' }}
        </button>
      </form>
    </div>
  </div>

  {{-- KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-team-line" color="primary" label="Utenti" :value="$stats['users']" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-file-text-line" color="info" label="Contratti attivi"
        :value="$stats['contracts_active'] . ' / ' . $stats['contracts_total']" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-funds-line" color="success" label="MRR"
        :value="'€ ' . number_format($stats['mrr'] / 100, 2, ',', '.')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-error-warning-line" color="danger" label="Arretrato"
        :value="'€ ' . number_format($stats['overdue_amount'] / 100, 2, ',', '.')" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-bill-line" color="info" label="Fatture totali" :value="$stats['invoices_total']" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-customer-service-2-line" color="warning" label="Ticket aperti" :value="$stats['tickets_open']" />
    </div>
    <div class="col-6 col-sm-3">
      <x-kpi-card icon="ri-router-line" color="secondary" label="Apparati CPE" :value="$stats['cpe_total']" />
    </div>
  </div>

  <div class="row g-4">

    {{-- Revenue chart --}}
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-header"><h6 class="mb-0">Revenue ultimi 6 mesi</h6></div>
        <div class="card-body"><div id="chartRevenue"></div></div>
      </div>
    </div>

    {{-- Tenant info --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header"><h6 class="mb-0">Dettagli tenant</h6></div>
        <div class="card-body">
          <dl class="row small mb-0">
            <dt class="col-5 text-muted">ID</dt>
            <dd class="col-7 font-monospace">#{{ $tenant->id }}</dd>
            <dt class="col-5 text-muted">Slug</dt>
            <dd class="col-7 font-monospace">{{ $tenant->slug }}</dd>
            <dt class="col-5 text-muted">Dominio</dt>
            <dd class="col-7">{{ $tenant->domain ?? '—' }}</dd>
            <dt class="col-5 text-muted">Creato il</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($tenant->created_at)->format('d/m/Y') }}</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Users --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header"><h6 class="mb-0">Utenti del tenant</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th>Email</th>
                  <th>Ruoli</th>
                  <th>Ultimo accesso</th>
                  <th class="text-center">Stato</th>
                </tr>
              </thead>
              <tbody>
                @forelse($users as $u)
                  @php
                    $roles = is_string($u->roles ?? null) ? json_decode($u->roles, true) : ($u->roles ?? []);
                  @endphp
                  <tr>
                    <td class="small fw-semibold">{{ $u->name }}</td>
                    <td class="small text-muted">{{ $u->email }}</td>
                    <td>
                      @foreach((array)$roles as $role)
                        <span class="badge bg-label-primary me-1">{{ ucfirst($role) }}</span>
                      @endforeach
                    </td>
                    <td class="small text-muted">
                      {{ $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->format('d/m/Y H:i') : 'Mai' }}
                    </td>
                    <td class="text-center">
                      <span class="badge bg-{{ $u->is_active ? 'success' : 'secondary' }}">
                        {{ $u->is_active ? 'Attivo' : 'Inattivo' }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3 small">Nessun utente.</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
<script>
new ApexCharts(document.getElementById('chartRevenue'), {
  chart: { type: 'bar', height: 240, toolbar: { show: false } },
  series: [{ name: 'Fatturato', data: @json($revenueChart->pluck('revenue')->map(fn($v) => round($v/100,2))) }],
  xaxis: { categories: @json($revenueChart->pluck('month')) },
  colors: ['#696cff'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  yaxis: { labels: { formatter: v => '€' + v.toLocaleString('it') } },
}).render();
</script>
@endpush

@extends('layouts.contentNavbarLayout')

@section('title', 'Cliente')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Clienti</a></li>
  <li class="breadcrumb-item active">{{ $customer->full_name ?? ($customer->company_name ?? ($customer->first_name . ' ' . $customer->last_name)) }}</li>
@endsection

@section('page-content')

  <div class="page-header d-flex justify-content-between align-items-start">
    <div>
      <h4>{{ $customer->company_name ?? ($customer->first_name . ' ' . $customer->last_name) }}</h4>
      <p class="text-muted mb-0">
        <span class="badge bg-light text-dark border me-1">{{ $customer->type === 'azienda' ? 'Azienda' : 'Privato' }}</span>
        <code>{{ $customer->codice_fiscale }}</code>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-outline-secondary">
        <i class="ri-pencil-line me-1"></i>Modifica
      </a>
      <a href="{{ route('contracts.create') }}?customer_id={{ $customer->id }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nuovo contratto
      </a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-5">
      <div class="card">
        <div class="card-header">Anagrafica</div>
        <div class="card-body">
          <dl class="row mb-0">
            @if($customer->company_name)
              <dt class="col-5 text-muted">Ragione sociale</dt>
              <dd class="col-7">{{ $customer->company_name }}</dd>
              <dt class="col-5 text-muted">P. IVA</dt>
              <dd class="col-7"><code>{{ $customer->partita_iva ?? '—' }}</code></dd>
            @else
              <dt class="col-5 text-muted">Nome</dt>
              <dd class="col-7">{{ $customer->first_name }} {{ $customer->last_name }}</dd>
            @endif
            <dt class="col-5 text-muted">Codice fiscale</dt>
            <dd class="col-7"><code>{{ $customer->codice_fiscale }}</code></dd>
            <dt class="col-5 text-muted">Email</dt>
            <dd class="col-7">{{ $customer->email }}</dd>
            <dt class="col-5 text-muted">Telefono</dt>
            <dd class="col-7">{{ $customer->phone ?? '—' }}</dd>
            <dt class="col-5 text-muted">Cliente dal</dt>
            <dd class="col-7">{{ \Carbon\Carbon::parse($customer->created_at)->format('d/m/Y') }}</dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Contratti</span>
          <a href="{{ route('contracts.index') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-outline-primary">Tutti</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr><th>ID</th><th>Piano</th><th>Carrier</th><th>Stato</th><th>Attivazione</th></tr>
              </thead>
              <tbody>
                @php
                  $contracts = \Illuminate\Support\Facades\DB::table('contracts')
                    ->join('service_plans','contracts.service_plan_id','=','service_plans.id')
                    ->where('contracts.customer_id', $customer->id)
                    ->select('contracts.*','service_plans.name as plan_name')
                    ->orderByDesc('contracts.created_at')->limit(10)->get();
                @endphp
                @forelse($contracts as $c)
                  <tr>
                    <td><a href="{{ route('contracts.show', $c->id) }}">{{ $c->id }}</a></td>
                    <td>{{ $c->plan_name }}</td>
                    <td>{{ strtoupper($c->carrier ?? '—') }}</td>
                    <td><span class="badge badge-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
                    <td class="small text-muted">{{ $c->activated_at ? \Carbon\Carbon::parse($c->activated_at)->format('d/m/Y') : '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3">Nessun contratto</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

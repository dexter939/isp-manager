@extends('layouts.contentNavbarLayout')

@section('title', 'Contratto #' . $contract->id)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">Contratti</a></li>
  <li class="breadcrumb-item active">{{ $contract->id }}</li>
@endsection

@section('page-content')

  <div class="page-header d-flex justify-content-between align-items-start">
    <div>
      <h4>Contratto #{{ $contract->id }}</h4>
      <p class="text-muted mb-0">{{ $contract->customer->full_name }}</p>
    </div>
    <div class="d-flex gap-2">
      @if($contract->status->value === 'active')
        <form method="POST" action="{{ route('contracts.suspend', $contract) }}">
          @csrf @method('PATCH')
          <button type="submit" class="btn btn-warning btn-sm"
                  onclick="return confirm('Sospendere il contratto?')">
            <i class="ri-pause-line me-1"></i>Sospendi
          </button>
        </form>
      @elseif($contract->status->value === 'suspended')
        <form method="POST" action="{{ route('contracts.reactivate', $contract) }}">
          @csrf @method('PATCH')
          <button type="submit" class="btn btn-success btn-sm">
            <i class="ri-play-line me-1"></i>Riattiva
          </button>
        </form>
      @endif
      <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-primary btn-sm">
        <i class="ri-edit-line me-1"></i>Modifica
      </a>
    </div>
  </div>

  <div class="row g-3">

    {{-- Contract info --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header">Dati contratto</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">Stato</dt>
            <dd class="col-7">
              <span class="badge badge-{{ $contract->status->value }}">{{ $contract->status->label() }}</span>
            </dd>
            <dt class="col-5 text-muted">Piano</dt>
            <dd class="col-7">{{ $contract->servicePlan->name }}</dd>
            <dt class="col-5 text-muted">Carrier</dt>
            <dd class="col-7">{{ strtoupper($contract->carrier ?? '—') }}</dd>
            <dt class="col-5 text-muted">VLAN</dt>
            <dd class="col-7">{{ $contract->vlan_id ?? '—' }}</dd>
            <dt class="col-5 text-muted">IP PPPoE</dt>
            <dd class="col-7"><code>{{ $contract->pppoe_ip ?? '—' }}</code></dd>
            <dt class="col-5 text-muted">Attivazione</dt>
            <dd class="col-7">{{ $contract->activated_at?->format('d/m/Y') ?? '—' }}</dd>
            <dt class="col-5 text-muted">Canone mensile</dt>
            <dd class="col-7 fw-semibold">€ {{ number_format($contract->servicePlan->monthly_fee / 100, 2, ',', '.') }}</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Customer info --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between">
          <span>Cliente</span>
          <a href="{{ route('customers.show', $contract->customer) }}" class="btn btn-sm btn-outline-secondary">Apri</a>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5 text-muted">Nome</dt>
            <dd class="col-7">{{ $contract->customer->full_name }}</dd>
            <dt class="col-5 text-muted">Cod. Fiscale</dt>
            <dd class="col-7"><code>{{ $contract->customer->codice_fiscale }}</code></dd>
            <dt class="col-5 text-muted">Email</dt>
            <dd class="col-7">{{ $contract->customer->email }}</dd>
            <dt class="col-5 text-muted">Telefono</dt>
            <dd class="col-7">{{ $contract->customer->phone ?? '—' }}</dd>
            <dt class="col-5 text-muted">Indirizzo</dt>
            <dd class="col-7">{{ $contract->installation_address }}</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Invoices --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Fatture</span>
          <a href="{{ route('billing.invoices.index', ['contract_id' => $contract->id]) }}" class="btn btn-sm btn-outline-primary">Tutte</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Numero</th>
                  <th>Emissione</th>
                  <th>Scadenza</th>
                  <th class="text-end">Importo</th>
                  <th>Stato</th>
                </tr>
              </thead>
              <tbody>
                @forelse($contract->invoices->take(5) as $inv)
                  <tr>
                    <td><a href="{{ route('billing.invoices.show', $inv) }}">{{ $inv->number }}</a></td>
                    <td>{{ $inv->issue_date->format('d/m/Y') }}</td>
                    <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                    <td class="text-end">€ {{ number_format($inv->total_amount / 100, 2, ',', '.') }}</td>
                    <td><span class="badge bg-secondary">{{ $inv->status->label() }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3">Nessuna fattura</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Tickets --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Ticket assistenza</span>
          <a href="{{ route('tickets.create', ['contract_id' => $contract->id]) }}" class="btn btn-sm btn-primary">
            <i class="ri-add-line me-1"></i>Nuovo ticket
          </a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Numero</th>
                  <th>Oggetto</th>
                  <th>Priorità</th>
                  <th>Stato</th>
                  <th>Aperto</th>
                </tr>
              </thead>
              <tbody>
                @forelse($contract->tickets->take(5) as $ticket)
                  <tr>
                    <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td>
                    <td>{{ Str::limit($ticket->subject, 60) }}</td>
                    <td><span class="badge bg-{{ $ticket->priority->badgeColor() }}">{{ $ticket->priority->label() }}</span></td>
                    <td><span class="badge bg-secondary">{{ $ticket->status->label() }}</span></td>
                    <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3">Nessun ticket</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection

@extends('layouts.contentNavbarLayout')
@section('title', 'Ticket')

@section('breadcrumb')
  <li class="breadcrumb-item active">Ticket</li>
@endsection

@section('page-content')

  <x-page-header title="Ticket assistenza" subtitle="Gestione segnalazioni e richieste">
    <x-slot name="action">
      <a href="{{ route('tickets.sla') }}" class="btn btn-outline-warning btn-sm me-2">
        <i class="ri-alarm-warning-line me-1"></i>SLA
      </a>
      <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
        <i class="ri-add-line me-1"></i>Nuovo ticket
      </a>
    </x-slot>
  </x-page-header>

  <x-filter-bar :resetRoute="route('tickets.index')">
    <div class="col-12 col-sm-4">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Numero, titolo, cliente..." value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Stato</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="open"        @selected(request('status') === 'open')>Aperto</option>
        <option value="in_progress" @selected(request('status') === 'in_progress')>In lavorazione</option>
        <option value="pending"     @selected(request('status') === 'pending')>In attesa</option>
        <option value="resolved"    @selected(request('status') === 'resolved')>Risolto</option>
        <option value="closed"      @selected(request('status') === 'closed')>Chiuso</option>
      </select>
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Priorità</label>
      <select name="priority" class="form-select form-select-sm">
        <option value="">Tutte</option>
        <option value="critical" @selected(request('priority') === 'critical')>Critica</option>
        <option value="high"     @selected(request('priority') === 'high')>Alta</option>
        <option value="medium"   @selected(request('priority') === 'medium')>Media</option>
        <option value="low"      @selected(request('priority') === 'low')>Bassa</option>
      </select>
    </div>
    <div class="col-auto d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="overdue" name="overdue" value="1"
               @checked(request('overdue'))>
        <label class="form-check-label small" for="overdue">Solo scaduti</label>
      </div>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Numero</th>
              <th>Titolo</th>
              <th>Cliente</th>
              <th>Priorità</th>
              <th>Stato</th>
              <th>SLA</th>
              <th>Assegnato a</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              @php
                $isOverdue = $ticket->due_at
                    && \Carbon\Carbon::parse($ticket->due_at)->isPast()
                    && !in_array($ticket->status, ['resolved','closed','cancelled']);
                $priorityColor = match($ticket->priority) {
                    'critical' => 'danger',
                    'high'     => 'danger',
                    'medium'   => 'warning',
                    default    => 'secondary',
                };
                $statusColor = match($ticket->status) {
                    'open'        => 'primary',
                    'in_progress' => 'info',
                    'pending'     => 'warning',
                    'resolved'    => 'success',
                    'closed'      => 'secondary',
                    default       => 'secondary',
                };
                $statusLabel = match($ticket->status) {
                    'open'        => 'Aperto',
                    'in_progress' => 'In lavorazione',
                    'pending'     => 'In attesa',
                    'resolved'    => 'Risolto',
                    'closed'      => 'Chiuso',
                    'cancelled'   => 'Annullato',
                    default       => ucfirst($ticket->status),
                };
              @endphp
              <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                <td class="font-monospace small fw-semibold">
                  <a href="{{ route('tickets.show', $ticket->id) }}" class="text-decoration-none">
                    {{ $ticket->ticket_number }}
                  </a>
                </td>
                <td class="small">{{ \Illuminate\Support\Str::limit($ticket->title, 55) }}</td>
                <td class="small text-muted">{{ $ticket->customer_full_name ?? '—' }}</td>
                <td>
                  <span class="badge bg-{{ $priorityColor }}">
                    {{ ucfirst($ticket->priority) }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $statusColor }}">{{ $statusLabel }}</span>
                </td>
                <td class="small">
                  @if($ticket->due_at)
                    @php $due = \Carbon\Carbon::parse($ticket->due_at); @endphp
                    @if($isOverdue)
                      <span class="text-danger fw-semibold">
                        <i class="ri-alarm-warning-line me-1"></i>
                        +{{ round(now()->diffInHours($due)) }}h
                      </span>
                    @else
                      <span class="text-muted">{{ $due->format('d/m H:i') }}</span>
                    @endif
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="small text-muted">{{ $ticket->assigned_name ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun ticket trovato" icon="ri-customer-service-2-line" colspan="8" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($tickets->hasPages())
      <div class="card-footer">{{ $tickets->links() }}</div>
    @endif
  </div>

@endsection

@extends('layouts.portal')
@section('title', 'La mia area')

@section('content')

  <div class="row g-3 mb-4">
    <div class="col-12">
      <h5 class="mb-0">Benvenuto, <strong>{{ auth('portal')->user()->display_name }}</strong></h5>
      <p class="text-muted small">Riepilogo del tuo account</p>
    </div>

    <div class="col-6 col-md-3">
      <div class="card portal-card h-100 text-center p-3">
        <div class="display-6 text-primary"><i class="ri-file-text-line"></i></div>
        <div class="fw-bold fs-4">{{ $contracts->count() }}</div>
        <div class="text-muted small">Contratti attivi</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card portal-card h-100 text-center p-3">
        <div class="display-6 text-{{ $totalOverdue > 0 ? 'danger' : 'success' }}"><i class="ri-bill-line"></i></div>
        <div class="fw-bold fs-4">€ {{ number_format($totalOverdue / 100, 2, ',', '.') }}</div>
        <div class="text-muted small">Da pagare</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card portal-card h-100 text-center p-3">
        <div class="display-6 text-success"><i class="ri-checkbox-circle-line"></i></div>
        <div class="fw-bold fs-4">€ {{ number_format($totalPaid / 100, 2, ',', '.') }}</div>
        <div class="text-muted small">Pagato totale</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card portal-card h-100 text-center p-3">
        <div class="display-6 text-warning"><i class="ri-customer-service-2-line"></i></div>
        <div class="fw-bold fs-4">{{ $openTickets->count() }}</div>
        <div class="text-muted small">Ticket aperti</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- Contratti --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card h-100">
        <div class="card-header bg-transparent fw-semibold">
          <i class="ri-file-text-line me-2 text-primary"></i>I tuoi contratti
        </div>
        <div class="card-body p-0">
          @forelse($contracts as $c)
            <div class="d-flex align-items-start gap-3 p-3 border-bottom">
              <div class="flex-grow-1">
                <div class="fw-semibold small">{{ $c->plan_name }}</div>
                <div class="text-muted" style="font-size:.75rem">
                  {{ strtoupper($c->carrier ?? '—') }} · {{ $c->technology }}
                  · {{ $c->bandwidth_dl }}/{{ $c->bandwidth_ul }} Mbps
                </div>
                @if($c->activation_date)
                  <div class="text-muted" style="font-size:.75rem">
                    Attivo dal {{ \Carbon\Carbon::parse($c->activation_date)->format('d/m/Y') }}
                  </div>
                @endif
              </div>
              <span class="badge badge-status-{{ $c->status }}">{{ ucfirst($c->status) }}</span>
            </div>
          @empty
            <p class="text-center text-muted py-4 small">Nessun contratto attivo.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Fatture in sospeso --}}
    <div class="col-12 col-lg-6">
      <div class="card portal-card h-100">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="ri-bill-line me-2 text-warning"></i>Fatture da pagare</span>
          <a href="{{ route('portal.invoices') }}" class="btn btn-sm btn-outline-primary">Tutte</a>
        </div>
        <div class="card-body p-0">
          @forelse($pendingInvoices as $inv)
            <div class="d-flex align-items-center gap-3 p-3 border-bottom">
              <div class="flex-grow-1">
                <a href="{{ route('portal.invoices.show', $inv->id) }}" class="fw-semibold small text-decoration-none">
                  {{ $inv->number }}
                </a>
                <div class="text-muted" style="font-size:.75rem">
                  Scadenza: {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold small">€ {{ number_format($inv->total / 100, 2, ',', '.') }}</div>
                <span class="badge badge-status-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span>
              </div>
            </div>
          @empty
            <p class="text-center text-muted py-4 small">
              <i class="ri-checkbox-circle-line text-success d-block fs-3 mb-1"></i>
              Nessuna fattura in sospeso.
            </p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Ticket aperti --}}
    <div class="col-12">
      <div class="card portal-card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="ri-customer-service-2-line me-2 text-info"></i>Ultime richieste assistenza</span>
          <a href="{{ route('portal.tickets.create') }}" class="btn btn-sm btn-primary">
            <i class="ri-add-line me-1"></i>Nuova richiesta
          </a>
        </div>
        <div class="card-body p-0">
          @forelse($openTickets as $t)
            <div class="d-flex align-items-center gap-3 p-3 border-bottom">
              <div class="flex-grow-1">
                <a href="{{ route('portal.tickets.show', $t->ticket_number) }}" class="fw-semibold small text-decoration-none">
                  {{ $t->ticket_number }} — {{ $t->title }}
                </a>
                <div class="text-muted" style="font-size:.75rem">
                  Aperto: {{ \Carbon\Carbon::parse($t->opened_at)->format('d/m/Y H:i') }}
                </div>
              </div>
              <div class="d-flex gap-1 flex-column align-items-end">
                <span class="badge badge-status-{{ $t->status }}">{{ ucfirst($t->status) }}</span>
                @if($t->priority === 'critical' || $t->priority === 'high')
                  <span class="badge bg-danger">{{ ucfirst($t->priority) }}</span>
                @endif
              </div>
            </div>
          @empty
            <p class="text-center text-muted py-4 small">Nessun ticket aperto.</p>
          @endforelse
        </div>
      </div>
    </div>

  </div>

@endsection

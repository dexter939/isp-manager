@extends('layouts.contentNavbarLayout')
@section('title', 'SLA Dashboard')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Ticket</a></li>
  <li class="breadcrumb-item active">SLA Dashboard</li>
@endsection

@section('page-content')

  <x-page-header title="SLA Dashboard" subtitle="Monitoraggio in tempo reale degli accordi di servizio" />

  {{-- KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
      <div class="card text-center">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-primary">{{ $stats['open'] }}</div>
          <div class="small text-muted">Ticket aperti</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center border-{{ $stats['breached'] > 0 ? 'danger' : 'success' }}">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-{{ $stats['breached'] > 0 ? 'danger' : 'success' }}">
            {{ $stats['breached'] }}
          </div>
          <div class="small text-muted">SLA scaduti</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center border-{{ $stats['at_risk'] > 0 ? 'warning' : 'success' }}">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-{{ $stats['at_risk'] > 0 ? 'warning' : 'success' }}">
            {{ $stats['at_risk'] }}
          </div>
          <div class="small text-muted">A rischio (4h)</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-sm-3">
      <div class="card text-center border-{{ $stats['no_first_response'] > 0 ? 'warning' : 'success' }}">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-{{ $stats['no_first_response'] > 0 ? 'warning' : 'success' }}">
            {{ $stats['no_first_response'] }}
          </div>
          <div class="small text-muted">Prima risposta scaduta</div>
        </div>
      </div>
    </div>
  </div>

  {{-- SLA compliance ultima settimana --}}
  @if($compliance->count())
  <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Compliance SLA ultimi 30 giorni</h6></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Priorità</th>
              <th class="text-center">Totali risolti</th>
              <th class="text-center">Entro SLA</th>
              <th class="text-center">% Compliance</th>
              <th class="text-end">Media risoluzione</th>
            </tr>
          </thead>
          <tbody>
            @foreach($compliance as $row)
              @php
                $rate = $row->total > 0 ? round($row->within_sla / $row->total * 100, 1) : 0;
                $rateColor = $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
              @endphp
              <tr>
                <td>
                  <span class="badge bg-{{ match($row->priority) { 'critical','high' => 'danger', 'medium' => 'warning', default => 'secondary' } }}">
                    {{ ucfirst($row->priority) }}
                  </span>
                </td>
                <td class="text-center">{{ $row->total }}</td>
                <td class="text-center text-success">{{ $row->within_sla }}</td>
                <td class="text-center">
                  <div class="d-flex align-items-center gap-2 justify-content-center">
                    <div class="progress flex-grow-1" style="height:6px; max-width:80px">
                      <div class="progress-bar bg-{{ $rateColor }}" style="width:{{ $rate }}%"></div>
                    </div>
                    <span class="fw-semibold text-{{ $rateColor }}">{{ $rate }}%</span>
                  </div>
                </td>
                <td class="text-end small text-muted">
                  {{ $row->avg_hours ? round($row->avg_hours, 1) . 'h' : '—' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  {{-- Ticket scaduti --}}
  @if($breached->count())
  <div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between">
      <h6 class="mb-0"><i class="ri-alarm-warning-line me-1"></i>SLA Scaduti — intervento immediato</h6>
      <span class="badge bg-white text-danger">{{ $breached->count() }}</span>
    </div>
    <div class="card-body p-0">
      @include('tickets._sla_table', ['rows' => $breached, 'mode' => 'overdue'])
    </div>
  </div>
  @endif

  {{-- Prima risposta scaduta --}}
  @if($firstResponseBreached->count())
  <div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark d-flex justify-content-between">
      <h6 class="mb-0"><i class="ri-chat-check-line me-1"></i>Prima risposta scaduta</h6>
      <span class="badge bg-dark text-white">{{ $firstResponseBreached->count() }}</span>
    </div>
    <div class="card-body p-0">
      @include('tickets._sla_table', ['rows' => $firstResponseBreached, 'mode' => 'no_response'])
    </div>
  </div>
  @endif

  {{-- A rischio --}}
  @if($atRisk->count())
  <div class="card border-warning">
    <div class="card-header bg-label-warning d-flex justify-content-between">
      <h6 class="mb-0"><i class="ri-time-line me-1"></i>A rischio nelle prossime 4 ore</h6>
      <span class="badge bg-warning text-dark">{{ $atRisk->count() }}</span>
    </div>
    <div class="card-body p-0">
      @include('tickets._sla_table', ['rows' => $atRisk, 'mode' => 'at_risk'])
    </div>
  </div>
  @endif

  @if(!$stats['breached'] && !$stats['at_risk'] && !$stats['no_first_response'])
    <div class="text-center py-5">
      <i class="ri-shield-check-line text-success" style="font-size:3rem"></i>
      <h5 class="mt-3 text-success">Tutti gli SLA sono rispettati</h5>
      <p class="text-muted">Nessun ticket a rischio o scaduto al momento.</p>
    </div>
  @endif

@endsection

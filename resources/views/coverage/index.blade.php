@extends('layouts.contentNavbarLayout')

@section('title', 'Copertura')

@section('breadcrumb')
  <li class="breadcrumb-item active">Copertura</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Mappa di copertura</h4>
    <p class="text-muted mb-0">Verifica fattibilità e copertura carrier</p>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-primary">{{ number_format($stats['openfiber'] ?? 0) }}</div>
          <div class="text-muted small">UI Open Fiber</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-success">{{ number_format($stats['fibercop'] ?? 0) }}</div>
          <div class="text-muted small">Z-Point FiberCop</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card text-center">
        <div class="card-body">
          <div class="fs-2 fw-bold text-warning">{{ number_format($stats['addresses'] ?? 0) }}</div>
          <div class="text-muted small">Indirizzi censiti</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Feasibility check --}}
  <div class="card mb-3">
    <div class="card-header">Verifica fattibilità</div>
    <div class="card-body">
      <form method="GET" action="{{ route('coverage.feasibility') }}" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
          <label class="form-label small">Indirizzo</label>
          <input type="text" name="address" class="form-control"
                 placeholder="Via Roma 1, Milano" value="{{ request('address') }}" required>
        </div>
        <div class="col-6 col-sm-2">
          <label class="form-label small">CAP</label>
          <input type="text" name="cap" class="form-control" placeholder="20121" value="{{ request('cap') }}">
        </div>
        <div class="col-6 col-sm-2">
          <label class="form-label small">Carrier</label>
          <select name="carrier" class="form-select">
            <option value="">Tutti</option>
            <option value="openfiber" @selected(request('carrier') === 'openfiber')>Open Fiber</option>
            <option value="fibercop"  @selected(request('carrier') === 'fibercop')>FiberCop</option>
          </select>
        </div>
        <div class="col-12 col-sm-auto">
          <button type="submit" class="btn btn-primary">
            <i class="ri-search-eye-line me-1"></i>Verifica
          </button>
        </div>
      </form>

      @if(isset($feasibilityResult))
        <hr>
        <div class="mt-2">
          @if($feasibilityResult['feasible'])
            <div class="alert alert-success mb-0">
              <i class="ri-checkbox-circle-line me-2"></i>
              <strong>Copertura disponibile</strong> — {{ $feasibilityResult['carrier'] }}
              ({{ $feasibilityResult['technology'] }})
            </div>
          @else
            <div class="alert alert-warning mb-0">
              <i class="ri-information-line me-2"></i>
              <strong>Nessuna copertura</strong> trovata per l'indirizzo inserito.
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>

  {{-- Recent imports --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <span>Import recenti</span>
      <form method="POST" action="{{ route('coverage.import') }}" enctype="multipart/form-data" class="d-flex gap-2">
        @csrf
        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.xlsx" style="max-width:200px">
        <select name="carrier" class="form-select form-select-sm" style="max-width:140px">
          <option value="openfiber">Open Fiber</option>
          <option value="fibercop">FiberCop</option>
        </select>
        <button type="submit" class="btn btn-sm btn-success">
          <i class="ri-upload-line me-1"></i>Importa
        </button>
      </form>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Carrier</th>
              <th>File</th>
              <th class="text-end">Righe</th>
              <th>Stato</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            @forelse($imports ?? [] as $import)
              <tr>
                <td><span class="badge bg-light text-dark border">{{ strtoupper($import->carrier) }}</span></td>
                <td class="small">{{ $import->filename }}</td>
                <td class="text-end">{{ number_format($import->total_rows) }}</td>
                <td><span class="badge bg-{{ $import->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($import->status) }}</span></td>
                <td class="text-muted small">{{ $import->created_at->format('d/m/Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-3">Nessun import</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection

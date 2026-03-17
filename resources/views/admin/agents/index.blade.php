@extends('layouts.contentNavbarLayout')
@section('title', 'Agenti')

@section('breadcrumb')
  <li class="breadcrumb-item">Amministrazione</li>
  <li class="breadcrumb-item active">Agenti</li>
@endsection

@section('page-content')

  <x-page-header title="Rete Agenti" subtitle="Gestione agenti e provvigioni" />

  <div class="d-flex gap-2 mb-4">
    <a href="{{ route('admin.agents.create') }}" class="btn btn-primary">
      <i class="ri-add-line me-1"></i>Nuovo agente
    </a>
  </div>

  {{-- Filtri --}}
  <div class="card mb-4">
    <div class="card-body py-2">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Cerca per ragione sociale, codice, email…"
                 value="{{ request('search') }}">
        </div>
        <div class="col-12 col-md-3">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            @foreach(['active','inactive','suspended'] as $s)
              <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="ri-search-line"></i>
          </button>
          @if(request('search') || request('status'))
            <a href="{{ route('admin.agents.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
              <i class="ri-close-line"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="small fw-semibold">Agente</th>
              <th class="small fw-semibold">Codice</th>
              <th class="small fw-semibold">Utente collegato</th>
              <th class="small fw-semibold text-center">Contratti</th>
              <th class="small fw-semibold text-end">Provvigioni</th>
              <th class="small fw-semibold">Portale</th>
              <th class="small fw-semibold">Stato</th>
              <th class="small fw-semibold">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($agents as $a)
              <tr>
                <td>
                  <a href="{{ route('admin.agents.show', $a->id) }}" class="fw-semibold text-decoration-none small">
                    {{ $a->business_name }}
                  </a>
                </td>
                <td><code class="small">{{ $a->code }}</code></td>
                <td class="small text-muted">
                  <div>{{ $a->user_name }}</div>
                  <div style="font-size:.7rem">{{ $a->user_email }}</div>
                </td>
                <td class="text-center">
                  <span class="badge bg-label-primary">{{ $a->contracts_count }}</span>
                </td>
                <td class="text-end small fw-semibold">
                  € {{ number_format($a->total_commissions_cents / 100, 2, ',', '.') }}
                </td>
                <td>
                  @if($a->portal_email)
                    <span class="badge bg-label-success" title="{{ $a->portal_email }}">
                      <i class="ri-check-line me-1"></i>Attivo
                    </span>
                    @if($a->portal_last_login_at)
                      <div class="text-muted" style="font-size:.7rem">
                        {{ \Carbon\Carbon::parse($a->portal_last_login_at)->format('d/m/Y') }}
                      </div>
                    @endif
                  @else
                    <span class="badge bg-label-secondary">Non attivo</span>
                  @endif
                </td>
                <td>
                  @php
                    $statusColors = ['active'=>'success','inactive'=>'secondary','suspended'=>'warning'];
                  @endphp
                  <span class="badge bg-label-{{ $statusColors[$a->status] ?? 'secondary' }}">
                    {{ ucfirst($a->status) }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('admin.agents.show', $a->id) }}"
                       class="btn btn-sm btn-outline-info" title="Dettaglio">
                      <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('admin.agents.edit', $a->id) }}"
                       class="btn btn-sm btn-outline-primary" title="Modifica">
                      <i class="ri-pencil-line"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="ri-shake-hands-line d-block fs-3 mb-1"></i>
                  Nessun agente trovato.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($agents->hasPages())
      <div class="card-footer bg-transparent">
        {{ $agents->links() }}
      </div>
    @endif
  </div>

@endsection

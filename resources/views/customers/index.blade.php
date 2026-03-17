@extends('layouts.contentNavbarLayout')

@section('title', 'Clienti')

@section('breadcrumb')
  <li class="breadcrumb-item active">Clienti</li>
@endsection

@section('page-content')

  <x-page-header title="Clienti" subtitle="Anagrafica clienti">
    <x-slot:action>
      <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="ri-user-add-line me-1"></i>Nuovo cliente
      </a>
    </x-slot:action>
  </x-page-header>

  <x-filter-bar :resetRoute="route('customers.index')">
    <div class="col-12 col-sm-5">
      <label class="form-label small">Cerca</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Nome, email, codice fiscale..." value="{{ request('search') }}">
    </div>
    <div class="col-6 col-sm-2">
      <label class="form-label small">Tipo</label>
      <select name="type" class="form-select form-select-sm">
        <option value="">Tutti</option>
        <option value="privato" @selected(request('type') === 'privato')>Privato</option>
        <option value="azienda" @selected(request('type') === 'azienda')>Azienda</option>
      </select>
    </div>
  </x-filter-bar>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Cliente</th>
              <th>Tipo</th>
              <th>Email</th>
              <th>Telefono</th>
              <th>Contratti</th>
              <th>Registrato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($customers as $customer)
              <tr>
                <td>
                  <span class="fw-medium">{{ $customer->full_name }}</span>
                  <br><small class="text-muted font-monospace">{{ $customer->codice_fiscale }}</small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ $customer->type === 'azienda' ? 'Azienda' : 'Privato' }}
                  </span>
                </td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td>
                  <span class="badge bg-primary rounded-pill">{{ $customer->contracts_count ?? 0 }}</span>
                </td>
                <td class="text-muted small">{{ $customer->created_at->format('d/m/Y') }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <x-empty-state message="Nessun cliente trovato" icon="ri-user-line" colspan="7" />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($customers->hasPages())
      <div class="card-footer">{{ $customers->links() }}</div>
    @endif
  </div>

@endsection

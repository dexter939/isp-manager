@extends('layouts.contentNavbarLayout')

@section('title', 'Nuovo contratto')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">Contratti</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <div class="page-header">
    <h4>Nuovo contratto</h4>
    <p class="text-muted mb-0">Inserisci i dati per attivare un nuovo contratto di fornitura</p>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('contracts.store') }}">
        @csrf

        @if(request('customer_id'))
          <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
        @endif

        <div class="row g-3">

          @if(!request('customer_id'))
          <div class="col-12 col-md-6">
            <label class="form-label">Cliente (ID)</label>
            <input type="number" name="customer_id" class="form-control @error('customer_id') is-invalid @enderror"
                   value="{{ old('customer_id') }}" required>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          @endif

          <div class="col-12 col-md-6">
            <label class="form-label">Piano di servizio</label>
            <select name="service_plan_id" class="form-select @error('service_plan_id') is-invalid @enderror" required>
              <option value="">Seleziona piano...</option>
              @foreach(\Illuminate\Support\Facades\DB::table('service_plans')->where('is_active', true)->orderBy('name')->get() as $plan)
                <option value="{{ $plan->id }}" @selected(old('service_plan_id') == $plan->id)>
                  {{ $plan->name }} — € {{ number_format($plan->monthly_fee / 100, 2, ',', '.') }}/mese
                </option>
              @endforeach
            </select>
            @error('service_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Carrier</label>
            <select name="carrier" class="form-select @error('carrier') is-invalid @enderror">
              <option value="">Nessuno</option>
              <option value="openfiber" @selected(old('carrier') === 'openfiber')>Open Fiber</option>
              <option value="fibercop"  @selected(old('carrier') === 'fibercop')>FiberCop</option>
              <option value="fwa"       @selected(old('carrier') === 'fwa')>FWA</option>
            </select>
            @error('carrier')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Indirizzo di installazione</label>
            <input type="text" name="installation_address"
                   class="form-control @error('installation_address') is-invalid @enderror"
                   value="{{ old('installation_address') }}" required>
            @error('installation_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary">
              <i class="ri-save-line me-1"></i>Crea contratto
            </button>
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary ms-2">Annulla</a>
          </div>

        </div>
      </form>
    </div>
  </div>

@endsection

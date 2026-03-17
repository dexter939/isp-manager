@extends('layouts.contentNavbarLayout')

@section('title', 'Modifica piano — ' . $plan->name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('service-plans.index') }}">Piani di servizio</a></li>
  <li class="breadcrumb-item active">{{ $plan->name }}</li>
@endsection

@section('page-content')

  <x-page-header :title="'Modifica: ' . $plan->name" />

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('service-plans.update', $plan->id) }}">
        @csrf @method('PUT')

        @include('service-plans._form', ['plan' => $plan])

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Salva modifiche
          </button>
          <a href="{{ route('service-plans.index') }}" class="btn btn-outline-secondary">Annulla</a>
          <form method="POST" action="{{ route('service-plans.destroy', $plan->id) }}" class="ms-auto">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger"
                    data-confirm="Eliminare il piano {{ $plan->name }}?">
              <i class="ri-delete-bin-line me-1"></i>Elimina
            </button>
          </form>
        </div>
      </form>
    </div>
  </div>

@endsection

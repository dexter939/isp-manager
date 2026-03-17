@extends('layouts.contentNavbarLayout')

@section('title', 'Nuovo piano di servizio')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('service-plans.index') }}">Piani di servizio</a></li>
  <li class="breadcrumb-item active">Nuovo</li>
@endsection

@section('page-content')

  <x-page-header title="Nuovo piano di servizio" />

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('service-plans.store') }}">
        @csrf

        @include('service-plans._form', ['plan' => null])

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Crea piano
          </button>
          <a href="{{ route('service-plans.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
      </form>
    </div>
  </div>

@endsection

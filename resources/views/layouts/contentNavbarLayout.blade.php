@extends('layouts.commonMaster')

@section('content')
  @include('layouts.sections.menu.verticalMenu')

  <div class="layout-page">
    @include('layouts.sections.navbar.navbar')

    <div class="content-wrapper">

      {{-- Impersonation banner --}}
      @if(session('superadmin_original_id'))
        <div class="alert alert-warning alert-dismissible mb-0 rounded-0 border-0 border-bottom d-flex justify-content-between align-items-center py-2 px-4" role="alert">
          <div>
            <i class="ri-user-shared-line me-2"></i>
            <strong>Modalità impersonazione</strong> — stai navigando come
            <strong>{{ auth()->user()->name }}</strong>
            ({{ auth()->user()->email }})
          </div>
          <form method="POST" action="{{ route('superadmin.stop-impersonating') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning">
              <i class="ri-logout-box-line me-1"></i>Termina impersonazione
            </button>
          </form>
        </div>
      @endif

      {{-- Flash messages --}}
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" data-auto-dismiss="4000" role="alert">
          <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" data-auto-dismiss="6000" role="alert">
          <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      @yield('page-content')

    </div>

    @include('layouts.sections.footer.footer')
  </div>
@endsection

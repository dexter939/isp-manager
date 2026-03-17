<nav class="layout-navbar">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-link p-0 text-secondary d-lg-none" data-sidebar-toggle aria-label="Toggle menu">
      <i class="ri-menu-line fs-5"></i>
    </button>

    @hasSection('breadcrumb')
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          @yield('breadcrumb')
        </ol>
      </nav>
    @endif
  </div>

  <div class="d-flex align-items-center gap-3">
    {{-- Notifiche --}}
    <div class="dropdown">
      <a href="#" class="text-secondary position-relative" data-bs-toggle="dropdown">
        <i class="ri-notification-3-line fs-5"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:300px">
        <h6 class="mb-2">Notifiche</h6>
        <p class="text-muted small mb-0">Nessuna notifica recente.</p>
      </div>
    </div>

    {{-- Utente --}}
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
        <div class="avatar avatar-sm">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
               style="width:36px;height:36px;font-size:0.875rem;font-weight:600">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
          </div>
        </div>
        <span class="d-none d-md-inline text-body small fw-medium">
          {{ auth()->user()->name ?? 'Utente' }}
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li class="px-3 py-2 border-bottom">
          <p class="mb-0 fw-semibold text-body">{{ auth()->user()->name ?? '' }}</p>
          <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('profile') }}">
            <i class="ri-user-line me-2"></i>Profilo
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item text-danger">
              <i class="ri-logout-box-line me-2"></i>Esci
            </button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</nav>

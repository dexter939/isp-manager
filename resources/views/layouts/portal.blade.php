<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Portale Clienti') — {{ config('variables.templateName', 'ISP Manager') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <style>
    body { background: #f4f5fb; font-family: 'Public Sans', system-ui, sans-serif; }
    .portal-navbar { background: #696cff; }
    .portal-navbar .navbar-brand { color: #fff; font-weight: 700; font-size: 1.1rem; }
    .portal-navbar .nav-link { color: rgba(255,255,255,.85); }
    .portal-navbar .nav-link:hover, .portal-navbar .nav-link.active { color: #fff; }
    .portal-card { border: none; border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .badge-status-open      { background: #ffc107; color: #000; }
    .badge-status-paid      { background: #28a745; color: #fff; }
    .badge-status-overdue   { background: #dc3545; color: #fff; }
    .badge-status-issued    { background: #0dcaf0; color: #000; }
    .badge-status-resolved  { background: #6c757d; color: #fff; }
    .badge-status-active    { background: #28a745; color: #fff; }
    .badge-status-suspended { background: #ffc107; color: #000; }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg portal-navbar mb-4">
    <div class="container">
      <a class="navbar-brand" href="{{ route('portal.dashboard') }}">
        <i class="ri-wifi-line me-2"></i>{{ config('variables.templateName', 'ISP Manager') }}
        <small class="opacity-75 ms-1" style="font-weight:400;font-size:.75rem">Portale Clienti</small>
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav">
        <i class="ri-menu-line text-white"></i>
      </button>

      <div class="collapse navbar-collapse" id="portalNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('portal.dashboard') }}">
              <i class="ri-home-line me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('portal.invoices') }}">
              <i class="ri-bill-line me-1"></i>Fatture
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('portal.tickets') }}">
              <i class="ri-customer-service-2-line me-1"></i>Assistenza
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('portal.payment-methods') }}">
              <i class="ri-bank-card-line me-1"></i>Pagamenti
            </a>
          </li>
          <li class="nav-item ms-lg-3">
            <span class="nav-link text-white opacity-75 small">
              <i class="ri-user-line me-1"></i>{{ auth('portal')->user()?->display_name }}
            </span>
          </li>
          <li class="nav-item">
            <form method="POST" action="{{ route('portal.logout') }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-light">
                <i class="ri-logout-box-line me-1"></i>Esci
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container pb-5">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @yield('content')
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>

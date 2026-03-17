<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Portale Agenti') — {{ config('variables.templateName', 'ISP Manager') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <style>
    body { background: #f4f5fb; font-family: 'Public Sans', system-ui, sans-serif; }
    .agent-navbar { background: #28a745; }
    .agent-navbar .navbar-brand { color: #fff; font-weight: 700; font-size: 1.1rem; }
    .agent-navbar .nav-link { color: rgba(255,255,255,.85); }
    .agent-navbar .nav-link:hover,
    .agent-navbar .nav-link.active { color: #fff; font-weight: 600; }
    .portal-card { border: none; border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .stat-card { border-left: 4px solid; }
    .badge-pending  { background: #ffc107; color: #000; }
    .badge-accrued  { background: #0dcaf0; color: #000; }
    .badge-paid     { background: #28a745; color: #fff; }
    .badge-draft    { background: #6c757d; color: #fff; }
    .badge-approved { background: #0d6efd; color: #fff; }
    .badge-active   { background: #28a745; color: #fff; }
    .badge-inactive { background: #6c757d; color: #fff; }
    .badge-suspended{ background: #dc3545; color: #fff; }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg agent-navbar mb-4">
    <div class="container">
      <a class="navbar-brand" href="{{ route('agent-portal.dashboard') }}">
        <i class="ri-shake-hands-line me-2"></i>{{ config('variables.templateName', 'ISP Manager') }}
        <small class="opacity-75 ms-1" style="font-weight:400;font-size:.75rem">Portale Agenti</small>
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#agentNav">
        <i class="ri-menu-line text-white"></i>
      </button>

      <div class="collapse navbar-collapse" id="agentNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
          <li class="nav-item">
            <a class="nav-link @yield('nav_dashboard')" href="{{ route('agent-portal.dashboard') }}">
              <i class="ri-home-line me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link @yield('nav_contracts')" href="{{ route('agent-portal.contracts') }}">
              <i class="ri-file-text-line me-1"></i>Contratti
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link @yield('nav_commissions')" href="{{ route('agent-portal.commissions') }}">
              <i class="ri-money-euro-circle-line me-1"></i>Provvigioni
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link @yield('nav_liquidations')" href="{{ route('agent-portal.liquidations') }}">
              <i class="ri-bank-card-line me-1"></i>Liquidazioni
            </a>
          </li>
          <li class="nav-item ms-lg-3">
            <a class="nav-link @yield('nav_profile')" href="{{ route('agent-portal.profile') }}">
              <i class="ri-user-line me-1"></i>{{ auth('agent')->user()?->display_name }}
            </a>
          </li>
          <li class="nav-item">
            <form method="POST" action="{{ route('agent-portal.logout') }}" class="d-inline">
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
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @yield('content')
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>

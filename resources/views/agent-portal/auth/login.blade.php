<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accesso Portale Agenti</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <style>
    body { background: #f4f5fb; display: flex; align-items: center; min-height: 100vh; }
    .login-card { border: none; border-radius: 1rem; box-shadow: 0 8px 32px rgba(40,167,69,.12); max-width: 420px; width: 100%; }
    .login-brand { color: #28a745; font-size: 1.6rem; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-6 col-lg-5">
        <div class="card login-card p-4 p-md-5">

          <div class="text-center mb-4">
            <div class="login-brand"><i class="ri-shake-hands-line"></i></div>
            <h5 class="mb-1">Portale Agenti</h5>
            <p class="text-muted small">Consulta contratti, provvigioni e liquidazioni</p>
          </div>

          @if($errors->any())
            <div class="alert alert-danger small">
              <i class="ri-error-warning-line me-1"></i>{{ $errors->first() }}
            </div>
          @endif

          <form method="POST" action="{{ route('agent-portal.login.post') }}">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold small" for="email">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-mail-line"></i></span>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" placeholder="nome@email.it" autofocus required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold small" for="password">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-lock-line"></i></span>
                <input type="password" id="password" name="password" class="form-control" required>
              </div>
            </div>
            <div class="mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Ricordami</label>
              </div>
            </div>
            <button type="submit" class="btn btn-success w-100">
              <i class="ri-login-box-line me-1"></i>Accedi
            </button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            Problemi di accesso? Contatta l'amministratore.
          </p>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

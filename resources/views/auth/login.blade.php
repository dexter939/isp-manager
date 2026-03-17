@extends('layouts.blankLayout')

@section('title', 'Accesso')

@section('page-content')
<div class="auth-page">
  <div class="card auth-card shadow-sm">
    <div class="card-body p-4 p-md-5">

      <div class="text-center mb-4">
        <span class="auth-logo d-block mb-1">
          <i class="ri-wifi-line me-1"></i>ISPManager
        </span>
        <p class="text-muted mb-0">Accedi al tuo account</p>
      </div>

      @if(session('status'))
        <div class="alert alert-success small">
          <i class="ri-checkbox-circle-line me-1"></i>{{ session('status') }}
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email') }}" placeholder="admin@esempio.it" required autofocus>
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <label class="form-label" for="password">Password</label>
            @if(Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="small">Password dimenticata?</a>
            @endif
          </div>
          <div class="input-group">
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
              <i class="ri-eye-line" id="eyeIcon"></i>
            </button>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="remember" name="remember">
          <label class="form-check-label" for="remember">Rimani connesso</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
          <i class="ri-login-box-line me-2"></i>Accedi
        </button>
      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.className = 'ri-eye-off-line';
    } else {
      pwd.type = 'password';
      icon.className = 'ri-eye-line';
    }
  });
</script>
@endpush

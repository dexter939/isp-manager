@extends('layouts.blankLayout')
@section('title', 'Reimposta password')

@section('page-content')
<div class="auth-page">
  <div class="card auth-card shadow-sm">
    <div class="card-body p-4 p-md-5">

      <div class="text-center mb-4">
        <span class="auth-logo d-block mb-1">
          <i class="ri-lock-unlock-line me-1"></i>ISPManager
        </span>
        <p class="text-muted mb-0">Scegli una nuova password</p>
      </div>

      @if($errors->any())
        <div class="alert alert-danger small">
          @foreach($errors->all() as $error)
            <div><i class="ri-error-warning-line me-1"></i>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email"
                 class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email', $email) }}" required autofocus>
          @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Nuova password</label>
          <div class="input-group">
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePwd">
              <i class="ri-eye-line" id="eyeIcon"></i>
            </button>
          </div>
          @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          <div class="form-text">Minimo 8 caratteri, con maiuscole, minuscole e numeri.</div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="password_confirmation">Conferma password</label>
          <input type="password" id="password_confirmation" name="password_confirmation"
                 class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
          <i class="ri-lock-line me-2"></i>Reimposta password
        </button>

        <div class="text-center mt-3">
          <a href="{{ route('login') }}" class="small text-muted">
            <i class="ri-arrow-left-line me-1"></i>Torna al login
          </a>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
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

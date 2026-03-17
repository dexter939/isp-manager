@extends('layouts.blankLayout')

@section('title', 'Password Dimenticata')

@section('page-content')
<div class="auth-page">
  <div class="card auth-card shadow-sm">
    <div class="card-body p-4 p-md-5">

      <div class="text-center mb-4">
        <span class="auth-logo d-block mb-1">
          <i class="ri-lock-password-line me-1"></i>Reset Password
        </span>
        <p class="text-muted mb-0">Inserisci la tua email per il reset</p>
      </div>

      @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">
          @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email"
                 class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email') }}" required autofocus>
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
          <i class="ri-send-plane-line me-2"></i>Invia link di reset
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

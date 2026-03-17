@extends('layouts.contentNavbarLayout')
@section('title', 'Template Email')

@section('breadcrumb')
  <li class="breadcrumb-item">Impostazioni</li>
  <li class="breadcrumb-item active">Template Email</li>
@endsection

@section('page-content')

  <x-page-header title="Template Email" subtitle="Personalizza le email automatiche inviate ai tuoi clienti" />

  <div class="row g-4">
    @foreach($templates as $tpl)
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 {{ !$tpl->is_active ? 'opacity-75' : '' }}">
          <div class="card-body d-flex flex-column">

            <div class="d-flex align-items-start justify-content-between mb-3">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm bg-label-{{ $tpl->color }} rounded-circle">
                  <i class="{{ $tpl->icon }}"></i>
                </span>
                <div>
                  <div class="fw-semibold small">{{ $tpl->name }}</div>
                  <code class="small text-muted">{{ $tpl->slug }}</code>
                </div>
              </div>

              <form method="POST" action="{{ route('email-templates.toggle', $tpl->slug) }}">
                @csrf
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" role="switch"
                         onchange="this.form.submit()" {{ $tpl->is_active ? 'checked' : '' }}>
                </div>
              </form>
            </div>

            <div class="small text-muted mb-3 flex-grow-1">
              <strong>Oggetto:</strong>
              <div class="text-truncate">{{ $tpl->subject }}</div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <div>
                @if($tpl->is_custom)
                  <span class="badge bg-label-primary">Personalizzato</span>
                @else
                  <span class="badge bg-label-secondary">Default</span>
                @endif
                @if($tpl->updated_at)
                  <span class="text-muted small ms-1">
                    {{ \Carbon\Carbon::parse($tpl->updated_at)->format('d/m/Y') }}
                  </span>
                @endif
              </div>
              <div class="d-flex gap-1">
                <a href="{{ route('email-templates.preview', $tpl->slug) }}" target="_blank"
                   class="btn btn-sm btn-outline-info" title="Anteprima">
                  <i class="ri-eye-line"></i>
                </a>
                <a href="{{ route('email-templates.edit', $tpl->slug) }}"
                   class="btn btn-sm btn-outline-primary" title="Modifica">
                  <i class="ri-pencil-line"></i>
                </a>
                @if($tpl->is_custom)
                  <form method="POST" action="{{ route('email-templates.reset', $tpl->slug) }}"
                        onsubmit="return confirm('Ripristinare il template di default?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Ripristina default">
                      <i class="ri-restart-line"></i>
                    </button>
                  </form>
                @endif
              </div>
            </div>

          </div>
        </div>
      </div>
    @endforeach
  </div>

@endsection

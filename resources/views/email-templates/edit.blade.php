@extends('layouts.contentNavbarLayout')
@section('title', 'Modifica template — ' . $slug)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('email-templates.index') }}">Template Email</a></li>
  <li class="breadcrumb-item active">{{ $slug }}</li>
@endsection

@section('page-content')

  <x-page-header title="Modifica template" subtitle="{{ $meta['label'] }}" />

  <div class="row g-4">

    {{-- Editor --}}
    <div class="col-12 col-xl-8">
      <form method="POST" action="{{ route('email-templates.update', $slug) }}" id="templateForm">
        @csrf
        @method('PUT')

        <div class="card mb-4">
          <div class="card-header fw-semibold small">Configurazione</div>
          <div class="card-body">
            <div class="row g-3">

              <div class="col-12 col-md-7">
                <label class="form-label small" for="name">Nome template</label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $template?->name ?? $meta['label']) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 col-md-5 d-flex align-items-end">
                <div class="form-check form-switch mb-1">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                         value="1" @checked(old('is_active', $template?->is_active ?? true))>
                  <label class="form-check-label small" for="is_active">Template attivo</label>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small" for="subject">Oggetto email *</label>
                <input type="text" id="subject" name="subject"
                       class="form-control font-monospace @error('subject') is-invalid @enderror"
                       value="{{ old('subject', $template?->subject) }}" required>
                <div class="form-text">Usa <code>{{"{{"}}variabile{{"}}"}}</code> per inserire valori dinamici.</div>
                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">Corpo HTML</span>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="previewHtml()">
              <i class="ri-eye-line me-1"></i>Anteprima
            </button>
          </div>
          <div class="card-body p-0">
            <textarea id="body_html" name="body_html"
                      class="form-control border-0 rounded-0 font-monospace @error('body_html') is-invalid @enderror"
                      rows="20" style="font-size:12px;resize:vertical">{{ old('body_html', $template?->body_html) }}</textarea>
            @error('body_html')<div class="invalid-feedback px-3 pb-2">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header fw-semibold small">Corpo testo semplice</div>
          <div class="card-body p-0">
            <textarea id="body_text" name="body_text"
                      class="form-control border-0 rounded-0 @error('body_text') is-invalid @enderror"
                      rows="8" style="font-size:12px;resize:vertical">{{ old('body_text', $template?->body_text) }}</textarea>
            @error('body_text')<div class="invalid-feedback px-3 pb-2">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i>Salva template
          </button>
          <a href="{{ route('email-templates.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
      </form>
    </div>

    {{-- Pannello variabili + invio test --}}
    <div class="col-12 col-xl-4">

      {{-- Variabili disponibili --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">
          <i class="ri-code-line me-1"></i>Variabili disponibili
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3">
            Clicca su una variabile per copiarla. Incollala nell'oggetto o nel corpo con la sintassi
            <code>{{"{{"}}variabile{{"}}"}}</code>.
          </p>
          <div class="d-flex flex-wrap gap-2">
            {{-- Variabili comuni --}}
            @foreach(['tenant_name','tenant_email','current_date','customer_name'] as $v)
              <button type="button" class="btn btn-sm btn-outline-secondary var-btn"
                      data-var="{{ $v }}">
                <code>{{"{{"}}{{ $v }}{{"}}"}}</code>
              </button>
            @endforeach
            {{-- Variabili specifiche del template --}}
            @php
              $vars = $template ? json_decode($template->variables ?? '[]', true) : [];
              $common = ['tenant_name','tenant_email','current_date','customer_name'];
              $specific = array_diff($vars, $common);
            @endphp
            @foreach($specific as $v)
              <button type="button" class="btn btn-sm btn-outline-primary var-btn"
                      data-var="{{ $v }}">
                <code>{{"{{"}}{{ $v }}{{"}}"}}</code>
              </button>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Invio test --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold small">
          <i class="ri-send-plane-line me-1"></i>Invia email di test
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('email-templates.test', $slug) }}">
            @csrf
            <div class="mb-3">
              <label class="form-label small">Indirizzo email di test</label>
              <input type="email" name="test_email"
                     class="form-control form-control-sm"
                     value="{{ auth()->user()->email }}" required>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
              <i class="ri-send-plane-line me-1"></i>Invia test
            </button>
          </form>
        </div>
      </div>

      {{-- Anteprima live --}}
      <div class="card">
        <div class="card-header fw-semibold small">Anteprima</div>
        <div class="card-body p-0">
          <iframe id="previewFrame"
                  src="{{ route('email-templates.preview', $slug) }}"
                  style="width:100%;height:400px;border:0"
                  title="Anteprima email"></iframe>
        </div>
      </div>

    </div>
  </div>

@endsection

@push('scripts')
<script>
// Copy variable to clipboard and insert into focused field
document.querySelectorAll('.var-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const v   = '{{' + this.dataset.var + '}}';
    const el  = document.activeElement;
    const targets = [document.getElementById('subject'), document.getElementById('body_html'), document.getElementById('body_text')];
    const target  = targets.includes(el) ? el : document.getElementById('subject');

    const start = target.selectionStart ?? target.value.length;
    const end   = target.selectionEnd   ?? target.value.length;
    target.value = target.value.slice(0, start) + v + target.value.slice(end);
    target.focus();
    target.selectionStart = target.selectionEnd = start + v.length;

    // Flash feedback
    this.classList.add('btn-primary');
    setTimeout(() => this.classList.remove('btn-primary'), 500);
  });
});

// Live preview from current HTML content
function previewHtml() {
  const html  = document.getElementById('body_html').value;
  const frame = document.getElementById('previewFrame');
  const doc   = frame.contentDocument || frame.contentWindow.document;
  doc.open();
  doc.write(html);
  doc.close();
}

// Update preview on blur from body_html
document.getElementById('body_html').addEventListener('blur', previewHtml);
</script>
@endpush

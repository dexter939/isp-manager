@extends('layouts.contentNavbarLayout')

@section('title', 'API Documentation')

@section('breadcrumb')
  <li class="breadcrumb-item active">API Documentation</li>
@endsection

@section('page-content')

<x-page-header title="API Documentation" subtitle="Swagger UI — ISP Manager REST API v1.0" />

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="ri-code-s-slash-line me-2"></i>OpenAPI 3.1 Specification</h6>
    <div class="d-flex gap-2">
      <a href="{{ asset('api-docs/openapi.yaml') }}" download class="btn btn-sm btn-outline-secondary">
        <i class="ri-download-line me-1"></i>Download YAML
      </a>
      <span class="badge bg-success">v1.0.0</span>
    </div>
  </div>
  <div class="card-body p-0">
    {{-- Swagger UI container --}}
    <div id="swagger-ui" style="min-height: 600px;"></div>
  </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui.css">
<style>
  #swagger-ui .topbar            { display: none !important; }
  #swagger-ui .swagger-ui        { font-family: inherit; }
  #swagger-ui .info .title       { font-size: 1.4rem; }
  #swagger-ui .scheme-container  { padding: 1rem 1.5rem; background: #f8f9fa; }
  .swagger-ui .opblock-tag       { font-size: 1rem !important; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui-bundle.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
  SwaggerUIBundle({
    url:          '{{ asset("api-docs/openapi.yaml") }}',
    dom_id:       '#swagger-ui',
    presets:      [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
    layout:       'BaseLayout',
    deepLinking:  true,
    tryItOutEnabled: false,
    defaultModelsExpandDepth: 1,
    defaultModelExpandDepth:  2,
    docExpansion: 'list',
    filter:       true,
    requestInterceptor: function(req) {
      // Inject Sanctum token from meta if present
      const token = document.querySelector('meta[name="api-token"]')?.content;
      if (token) req.headers['Authorization'] = 'Bearer ' + token;
      return req;
    },
  });
});
</script>
@endpush

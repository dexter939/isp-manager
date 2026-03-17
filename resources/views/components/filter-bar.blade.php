@props(['resetRoute'])

<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      {{ $slot }}
      <div class="col-auto d-flex gap-1 ms-auto">
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="ri-search-line me-1"></i>Filtra
        </button>
        <a href="{{ $resetRoute }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

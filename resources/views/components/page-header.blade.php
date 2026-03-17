@props(['title', 'subtitle' => null])

<div class="page-header d-flex justify-content-between align-items-start mb-4">
  <div>
    <h4 class="mb-1">{{ $title }}</h4>
    @if($subtitle)
      <p class="text-muted mb-0 small">{{ $subtitle }}</p>
    @endif
  </div>
  @if(isset($action))
    <div class="flex-shrink-0">{{ $action }}</div>
  @endif
</div>

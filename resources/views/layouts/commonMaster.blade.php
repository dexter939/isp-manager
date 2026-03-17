<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style layout-menu-fixed" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>
    @hasSection('title')
      @yield('title') {{ config('variables.templateSuffix') }}
    @else
      {{ config('variables.templateName') }}
    @endif
  </title>

  @include('layouts.sections.styles')

  @stack('styles')
</head>
<body>
  @yield('content')

  @include('layouts.sections.scripts')

  @stack('scripts')
</body>
</html>

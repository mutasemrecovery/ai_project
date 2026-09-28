@php $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.dashboard')) — Admin · AH Group</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 (LTR / RTL) --}}
    @if($dir === 'rtl')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/style.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body>

    {{-- Overlay for mobile sidebar --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    @include('admin.includes.sidebar')

    {{-- Main wrapper --}}
    <div class="main-wrapper" id="mainWrapper">

        {{-- Navbar --}}
        @include('admin.includes.navbar')

        {{-- Page content --}}
        <main class="main-content">
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('admin.includes.footer')

    </div>{{-- /main-wrapper --}}

    {{-- Bootstrap JS (must be before panel.js) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/shared/js/panel.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(function () {
        var dir = document.documentElement.getAttribute('dir') || 'ltr';
        // Auto-apply Select2 to all form-select elements and any element with select2 class.
        // Add no-select2 class to opt out.
        $('select.form-select, select.select2').not('.no-select2').select2({
            theme: 'bootstrap-5',
            dir: dir,
            width: '100%',
        });
    });
    </script>
    @stack('scripts')
</body>
</html>

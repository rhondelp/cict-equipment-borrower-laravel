<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "CICT Equipment Borrower System")</title>

    @php
        $metaDescription = trim(View::yieldContent(
            'description',
            'Request, track and return laboratory equipment of the College of Information and Communications Technology, University of Northwestern Mindanao.'
        ));
    @endphp
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f8fafc">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="CICT Equipment Borrower System">
    <meta property="og:title" content="@yield('title', 'CICT Equipment Borrower System')">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png">
    <meta name="twitter:card" content="summary">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" type="image/x-icon">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="antialiased">
    @include('components.alerts')
    @yield("content")
    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const menuToggle = document.getElementById('menu-toggle');
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (menuToggle && sidebar && overlay) {
                    menuToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('active');
                        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                    });
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                    });
                    window.addEventListener('resize', function() {
                        if (window.innerWidth >= 768) {
                            sidebar.classList.remove('active');
                            overlay.style.display = 'none';
                            document.body.style.overflow = '';
                        }
                    });
                }
            } catch (e) { console.error('Sidebar init error', e); }

            try {
                document.querySelectorAll('.eye-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const wrap = btn.closest('.input-wrap');
                        const input = wrap ? wrap.querySelector('input') : null;
                        if (!input) return;
                        const isPwd = input.type === 'password';
                        input.type = isPwd ? 'text' : 'password';
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.className = isPwd
                                ? 'fa-solid fa-eye-slash'
                                : 'fa-solid fa-eye';
                        }
                    });
                });
            } catch (e) { console.error('eye toggle error', e); }

            window.addEventListener('error', function (ev) {
                console.error('Global JS error (non-blocking):', ev.message, ev.filename, ev.lineno);
            });
        });
    </script>
</body>
</html>

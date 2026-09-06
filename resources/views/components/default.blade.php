<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield("title", "CICT Equipment Borrower System")</title>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    {{-- Typography: Inter (primary) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" type="image/x-icon">

    {{-- DataTables (Responsive for mobile-friendly tables) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    {{-- SweetAlert2 (modals + toasts) --}}
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
            // Sidebar toggle (mobile)
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

            // Password visibility toggle
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

            // Global error guard so one failing listener doesn't kill others
            window.addEventListener('error', function (ev) {
                console.error('Global JS error (non-blocking):', ev.message, ev.filename, ev.lineno);
            });
        });
    </script>
</body>
</html>

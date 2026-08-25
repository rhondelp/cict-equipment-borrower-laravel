<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield("title", "Welcome")</title>

    <!-- Tailwind (runtime CDN build) -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    <!-- Fonts + icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
        type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables 2.x: single build (core + Tailwind skin) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.4/css/dataTables.tailwindcss.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.tailwindcss.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #111827;
        }

        /* Sidebar behavior */
        .sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-item {
            position: relative;
            transition: all 0.2s ease;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: var(--color-brand, #2563eb);
            border-radius: 0 2px 2px 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        /* Shared modal entrance */
        @keyframes modalFade {
            from { opacity: 0; transform: translateY(6px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-fade {
            animation: modalFade 0.18s ease-out;
        }

        @media (prefers-reduced-motion: reduce) {
            .modal-fade {
                animation: none;
            }
        }
    </style>

    {{-- Design system tokens (Tailwind v4 runtime theme) --}}
    <style type="text/tailwindcss">
        @theme {
            --color-brand: #2563eb;
            --color-brand-dark: #1d4ed8;
            --color-brand-light: #eff6ff;
        }
    </style>

    @stack('styles')
    <!-- Tailwind (via Vite) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body>
    @yield("content")
    @stack('scripts')
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (!menuToggle || !sidebar || !overlay) return;

            // Mobile menu toggle
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });

            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    {{-- Global UI helpers: toasts, modal dismissal, busy buttons --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1) Toast helper - use window.toast('Saved.', 'success' | 'error' | 'info')
            window.toast = function (message, type) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3200,
                    timerProgressBar: true,
                    didOpen: function (el) {
                        el.addEventListener('mouseenter', Swal.stopTimer);
                        el.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                Toast.fire({ icon: type || 'success', title: message });
            };

            // 2) Universal dismiss: any element with data-dismiss="#selector" hides the target
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-dismiss]');
                if (!trigger) return;
                const target = document.querySelector(trigger.getAttribute('data-dismiss'));
                if (target) target.classList.add('hidden');
            });

            // 3) Busy buttons on submit (runs after HTML5 validation passes)
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (form.hasAttribute('data-no-busy')) return;
                window.setTimeout(function () {
                    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                        if (btn.disabled) return;
                        btn.disabled = true;
                        btn.dataset.busyLabel = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i>' + btn.innerHTML;
                    });
                }, 0);
            }, true);

            // Restore buttons when returning via back/forward cache
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    document.querySelectorAll('button[disabled][data-busy-label]').forEach(function (btn) {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.busyLabel;
                        delete btn.dataset.busyLabel;
                    });
                }
            });
        });
    </script>
</body>

</html>

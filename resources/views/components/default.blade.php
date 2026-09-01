<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield("title", "CICT Equipment Borrower System")</title>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    {{-- Typography: Inter (primary) + Poppins fallback --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" type="image/x-icon">

    {{-- DataTables CSS & JS (standardized, responsive) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        window.STANDARD_DT_OPTIONS = {
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: '<i class="fas fa-angles-left text-xs"></i>',
                    previous: '<i class="fas fa-chevron-left text-xs"></i>',
                    next: '<i class="fas fa-chevron-right text-xs"></i>',
                    last: '<i class="fas fa-angles-right text-xs"></i>'
                }
            }
        };
        window.initAppTable = function(selector, extra = {}) {
            const $el = typeof selector === 'string' ? $(selector) : selector;
            if (!$el || !$el.length) return null;
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($el)) {
                return $el.DataTable();
            }
            try {
                return $el.DataTable(Object.assign({}, window.STANDARD_DT_OPTIONS, extra));
            } catch(e) {
                console.error('DataTable init failed (' + selector + ')', e);
                return null;
            }
        };
    </script>

    <style>
        :root{
            --bg-deep:#0a0e1a; --bg-deep-2:#0f1420; --bg-card:#131a2b;
            --bg-input:#0d1220; --border-subtle:rgba(255,255,255,0.07);
            --text-muted:#94a3b8;
            --text-placeholder:#7a8aaa;
        }
        *{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
        html{scroll-behavior:smooth}
        body{
            background: var(--bg-deep);
            color:#f8fafc;
            min-height:100vh;
            -webkit-font-smoothing:antialiased;
        }
        ::-webkit-scrollbar{width:6px;height:6px}
        ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.14);border-radius:999px}
        /* Sidebar */
        .sidebar{transition:all .3s cubic-bezier(.4,0,.2,1); background: #0c1222; border-right:1px solid rgba(255,255,255,0.06)}
        .nav-item{position:relative;transition:all .2s ease}
        .nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:22px;background:#3b82f6;border-radius:0 3px 3px 0}
        .nav-item.active{background:rgba(59,130,246,.12)!important;color:#fff!important}
        .nav-item.active i{color:#60a5fa!important}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.active{transform:translateX(0)}
        }
    </style>

    @stack('styles')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Fallback: load app.css directly so theme works even without vite build --}}
        <link rel="stylesheet" href="{{ asset('resources/css/app.css') }}">
        <style>
            /* Inline fallback for theme when vite not built — mirrors app.css core */
            .theme-shell{min-height:100vh;background:
                radial-gradient(ellipse 900px 600px at 70% 15%, rgba(59,130,246,.14) 0%, rgba(59,130,246,.06) 28%, transparent 62%),
                radial-gradient(ellipse 700px 500px at -5% 85%, rgba(59,130,246,.07) 0%, transparent 65%),
                linear-gradient(180deg, #0a0e1a 0%, #0f1420 100%);
                display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;overflow:hidden}
            .theme-shell::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 1200px 400px at 50% -8%, rgba(96,165,250,.08), transparent 70%);pointer-events:none}
            .auth-card{width:100%;max-width:460px;position:relative;background:#131a2b;border:1px solid rgba(255,255,255,.07);border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.03) inset;padding:36px 36px 28px}
            .auth-logo{width:48px;height:48px;border-radius:999px;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.35)}
            .auth-logo img{width:100%;height:100%;object-fit:cover}
            .auth-title{font-size:26px;line-height:1.2;font-weight:700;letter-spacing:-.02em;color:#fff}
            .auth-subtitle{font-size:14px;line-height:1.5;color:#94a3b8;margin-top:6px}
            .field-label-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
            .field-label{font-size:13px;font-weight:600;color:#f1f5f9;letter-spacing:-.01em}
            .field-hint{font-size:12px;color:#94a3b8}
            .input-wrap{position:relative;display:flex;align-items:center}
            .input-wrap .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7a99;font-size:14px;pointer-events:none}
            .input-wrap .eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#6b7a99;background:transparent;border:0;cursor:pointer;padding:4px;display:flex}
            .ds-input{width:100%;background:#0d1220;border:1px solid rgba(255,255,255,.09);border-radius:10px;color:#f1f5f9;font-size:14px;padding:13px 14px 13px 40px;outline:none;transition:border-color .18s,box-shadow .18s,background .18s}
            .ds-input::placeholder{color:#7a8aaa;opacity:1}
            .ds-input:focus{background:#111a33;border-color:rgba(96,165,250,.45);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
            .ds-input.has-trailing{padding-right:40px}
            select.ds-input{padding-right:36px;cursor:pointer}
            .ds-checkbox{appearance:none;width:18px;height:18px;border-radius:5px;border:1.5px solid rgba(255,255,255,.18);background:rgba(255,255,255,.92);display:inline-grid;place-content:center;cursor:pointer;flex-shrink:0}
            .ds-checkbox:checked{background:#3b82f6;border-color:#3b82f6}
            .ds-checkbox:checked::after{content:'✓';color:#fff;font-size:11px;font-weight:800}
            .btn-primary{width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 20px;border-radius:12px;background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:#fff;font-weight:600;font-size:14.5px;letter-spacing:-.01em;border:1px solid rgba(255,255,255,.08);box-shadow:0 8px 24px rgba(37,99,235,.35),0 1px 0 rgba(255,255,255,.12) inset;cursor:pointer;transition:filter .15s,transform .15s}
            .btn-primary:hover{filter:brightness(1.07);transform:translateY(-1px)}
            .auth-footer{text-align:center;font-size:13.5px;color:#cbd5e1;margin-top:18px}
            .auth-footer a{color:#60a5fa;font-weight:600;text-decoration:none}
            .auth-footer a:hover{color:#93c5fd;text-decoration:underline}
            .inline-link{color:#60a5fa;font-weight:600;text-decoration:underline;text-underline-offset:2px}
            .dash-bg{background:radial-gradient(ellipse 900px 600px at 72% 12%, rgba(59,130,246,.10) 0%, transparent 62%), linear-gradient(180deg,#0a0e1a 0%,#0c1222 100%);min-height:100vh}
            .dash-card{background:#131a2b;border:1px solid rgba(255,255,255,.06);border-radius:16px;box-shadow:0 10px 32px rgba(0,0,0,.35)}
            .dash-header{background:rgba(19,26,43,.85);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.06)}
            .dash-table-wrap{background:#131a2b;border:1px solid rgba(255,255,255,.06);border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.32)}
            .stat-card{background:linear-gradient(135deg,#131a2b 0%,#162040 100%);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:18px 20px;box-shadow:0 8px 24px rgba(0,0,0,.30)}
            .dash-bg .bg-white{background:#131a2b!important;color:#f1f5f9!important;border-color:rgba(255,255,255,.06)!important}
            .dash-bg .bg-gray-50,.dash-bg .bg-gray-100{background:#0d1220!important;color:#e2e8f0!important}
            .dash-bg .text-gray-900,.dash-bg .text-gray-800,.dash-bg .text-gray-700{color:#f1f5f9!important}
            .dash-bg .text-gray-600,.dash-bg .text-gray-500,.dash-bg .text-gray-400{color:#cbd5e1!important}
            /* Dark modals fallback — high contrast */
            #add-modal>div,#edit-modal>div,#delete-modal>div,#add-sched-modal>div,#emailModal>div,#returnLogModal>div,#logout-modal>div{background:#131a2b!important;border:1px solid rgba(255,255,255,.07)!important;color:#f1f5f9!important}
            #add-modal label,#edit-modal label,#delete-modal label,#add-sched-modal label,#emailModal label{color:#e2e8f0!important}
            #add-modal input,#edit-modal input,#add-sched-modal input,#emailModal input,#returnLogModal select{color:#f1f5f9!important}
            /* Low-contrast arbitrary colors — make readable */
            .text-\[\#8b93a8\]{color:#94a3b8!important}
            .text-\[\#6b7a99\]{color:#94a3b8!important}
            .dash-bg .text-\[\#8b93a8\]{color:#cbd5e1!important}
            .dash-bg .text-\[\#6b7a99\]{color:#94a3b8!important}
            @keyframes fadeInUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
            .animate-fade-in{animation:fadeInUp .7s ease both}
        </style>
        {{-- Fallback SweetAlert2 when Vite build not present (npm build not run) --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif
</head>
<body class="antialiased">
    {{-- Global consistent alerts (success/error/validation) — available on every page --}}
    @include('components.alerts')
    @yield("content")
    @stack('scripts')
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle — guard each element so one missing doesn't break later listeners
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
            } catch(e) { console.error('Sidebar init error', e); }

            // eye toggle for any .eye-btn
            try {
                document.querySelectorAll('.eye-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        const wrap = btn.closest('.input-wrap');
                        const input = wrap ? wrap.querySelector('input') : null;
                        if(!input) return;
                        const isPwd = input.type === 'password';
                        input.type = isPwd ? 'text' : 'password';
                        const icon = btn.querySelector('i');
                        if(icon){ icon.className = isPwd ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'; }
                    });
                });
            } catch(e) { console.error('eye toggle error', e); }

            // Global vanilla delegation fallback for the 6 critical admin buttons
            // Works even if jQuery/DataTables fails or rows are re-rendered (responsive child rows)
            // Uses e.target.closest so clicks on <i> inside button still trigger
            document.addEventListener('click', function(e) {
                // Add Equipment / Add User / Add Transaction all use #open-add-modal (one per page)
                const addBtn = e.target.closest('#open-add-modal');
                if (addBtn) {
                    const modal = document.getElementById('add-modal');
                    if (modal) modal.classList.remove('hidden');
                }
                // Add Schedule
                const addSchedBtn = e.target.closest('#open-add-sched-modal');
                if (addSchedBtn) {
                    const m = document.getElementById('add-sched-modal');
                    if (m) m.classList.remove('hidden');
                }
                // Edit Equipment / Edit User / Edit Transaction — all use .edit-btn, distinguish by closest table
                const editBtn = e.target.closest('.edit-btn');
                if (editBtn) {
                    // Equipment table
                    if (editBtn.closest('#equipmentTable')) {
                        const d = editBtn.dataset;
                        const el = (id) => document.getElementById(id);
                        if (el('edit-id')) el('edit-id').value = d.id || '';
                        if (el('edit-name')) el('edit-name').value = d.name || '';
                        if (el('edit-description')) el('edit-description').value = d.description || '';
                        if (el('edit-quantity')) el('edit-quantity').value = d.quantity || '';
                        if (el('edit-available')) el('edit-available').value = d.available || '';
                        if (el('edit-status')) el('edit-status').value = d.status || '';
                        document.getElementById('edit-modal')?.classList.remove('hidden');
                    }
                    // Users table
                    else if (editBtn.closest('#users-table')) {
                        const d = editBtn.dataset;
                        const get = (k) => editBtn.getAttribute('data-'+k) || d[k] || '';
                        const el = (id) => document.getElementById(id);
                        if (el('edit-id')) el('edit-id').value = get('id');
                        if (el('edit-name')) el('edit-name').value = get('name');
                        if (el('edit-email')) el('edit-email').value = get('email');
                        // user_type may be data-user-type (hyphen) or legacy data-user_type
                        const ut = get('user-type') || editBtn.getAttribute('data-user_type') || d.user_type || '';
                        const sel = el('edit-user-type') || el('edit-user_type');
                        if (sel) sel.value = ut;
                        if (el('edit-contact')) el('edit-contact').value = get('contact');
                        document.getElementById('edit-modal')?.classList.remove('hidden');
                    }
                    // Transactions table
                    else if (editBtn.closest('#transactions-table')) {
                        const d = editBtn.dataset;
                        const get = (k) => editBtn.getAttribute('data-'+k) || d[k] || '';
                        const el = (id) => document.getElementById(id);
                        if (el('edit-id')) el('edit-id').value = get('id');
                        if (el('edit-user')) el('edit-user').value = get('user');
                        if (el('edit-equipment')) el('edit-equipment').value = get('equipment');
                        if (el('edit-borrow')) el('edit-borrow').value = get('borrow');
                        if (el('edit-return')) el('edit-return').value = get('return');
                        if (el('edit-quantity')) el('edit-quantity').value = get('quantity');
                        if (el('edit-purpose')) el('edit-purpose').value = get('purpose');
                        if (el('edit-status')) el('edit-status').value = get('status');
                        if (el('edit-remarks')) el('edit-remarks').value = get('remarks');
                        if (el('edit-class')) el('edit-class').value = get('class');
                        document.getElementById('edit-modal')?.classList.remove('hidden');
                    }
                }
                // Cancel / close buttons (handle both id and class, header X and footer)
                if (e.target.closest('#cancel-add') || e.target.closest('.cancel-add')) {
                    document.getElementById('add-modal')?.classList.add('hidden');
                }
                if (e.target.closest('#cancel-edit') || e.target.closest('.cancel-edit')) {
                    document.getElementById('edit-modal')?.classList.add('hidden');
                }
                if (e.target.closest('#cancel-delete')) {
                    document.getElementById('delete-modal')?.classList.add('hidden');
                }
                if (e.target.closest('.cancel-sched')) {
                    document.getElementById('add-sched-modal')?.classList.add('hidden');
                }
                if (e.target.closest('#cancelReturn')) {
                    document.getElementById('returnLogModal')?.classList.add('hidden');
                }
                if (e.target.closest('#closeEmailModal')) {
                    document.getElementById('emailModal')?.classList.add('hidden');
                }
                // Click on backdrop itself to close (only if click target is the overlay)
                const addModal = e.target.closest('#add-modal');
                const editModal = e.target.closest('#edit-modal');
                const delModal = e.target.closest('#delete-modal');
                // Backdrop click already handled per-modal via direct check in page scripts; global fallback:
                if (e.target.id === 'add-modal' || e.target.id === 'edit-modal' || e.target.id === 'delete-modal' || e.target.id === 'add-sched-modal' || e.target.id === 'emailModal' || e.target.id === 'returnLogModal') {
                    e.target.classList.add('hidden');
                }
            });

            // Global error guard so one failing DataTable init doesn't kill other listeners
            window.addEventListener('error', function(ev) {
                console.error('Global JS error (non-blocking):', ev.message, ev.filename, ev.lineno);
            });
        });
    </script>
</body>
</html>

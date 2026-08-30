{{-- Reusable alerts: reads session('success'), session('welcome'), session('error'), $errors --}}
@php
    $flashSuccess = session('success') ?: session('welcome');
    $flashError = session('error');
@endphp

<div id="app-alerts" class="pointer-events-none fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-[92vw] max-w-md">
    @if ($flashSuccess)
        <div class="alert-item pointer-events-auto flex items-start gap-3 rounded-xl border border-emerald-500/20 bg-[#0d1f1a] px-4 py-3 text-sm text-emerald-300 shadow-2xl shadow-emerald-900/20" role="alert" data-auto-dismiss="5000">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-emerald-400"></i>
            <span class="flex-1 leading-snug">{{ $flashSuccess }}</span>
            <button type="button" class="ml-2 -mr-1 grid h-6 w-6 place-items-center rounded-lg text-emerald-300/70 hover:bg-white/10 hover:text-emerald-200" onclick="this.closest('.alert-item').remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    @if ($flashError)
        <div class="alert-item pointer-events-auto flex items-start gap-3 rounded-xl border border-red-500/20 bg-[#1f0d14] px-4 py-3 text-sm text-red-300 shadow-2xl shadow-red-900/20" role="alert" data-auto-dismiss="7000">
            <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-red-400"></i>
            <span class="flex-1 leading-snug">{{ $flashError }}</span>
            <button type="button" class="ml-2 -mr-1 grid h-6 w-6 place-items-center rounded-lg text-red-300/70 hover:bg-white/10 hover:text-red-200" onclick="this.closest('.alert-item').remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-item pointer-events-auto rounded-xl border border-red-500/20 bg-[#1f0d14] px-4 py-3 text-sm text-red-300 shadow-2xl shadow-red-900/20" role="alert" data-auto-dismiss="8000">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-red-400"></i>
                <div class="flex-1">
                    <p class="font-semibold text-red-200">Please fix the following:</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-300/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="ml-2 -mr-1 grid h-6 w-6 shrink-0 place-items-center rounded-lg text-red-300/70 hover:bg-white/10 hover:text-red-200" onclick="this.closest('.alert-item').remove()" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    // Auto-dismiss inline flash banners
    document.querySelectorAll('#app-alerts [data-auto-dismiss]').forEach(function(el) {
        var ms = parseInt(el.getAttribute('data-auto-dismiss'), 10) || 5000;
        setTimeout(function() {
            el.style.transition = 'opacity 220ms, transform 220ms';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function(){ el.remove(); }, 240);
        }, ms);
    });

    // Global toast helpers for AJAX actions (inlineUpdate, sendManualEmail etc.)
    // Uses SweetAlert2 if available, falls back to app-alerts container
    window.appToast = function(type, message) {
        type = (type || 'success').toLowerCase();
        if (window.Swal && typeof Swal.fire === 'function') {
            var icon = type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success';
            var color = type === 'error' ? '#ef4444' : '#10B981';
            Swal.fire({ title: type === 'error' ? 'Error!' : 'Success!', text: message, icon: icon, confirmButtonText: 'OK', confirmButtonColor: color });
            return;
        }
        // Fallback: inject into #app-alerts
        var container = document.getElementById('app-alerts');
        if (!container) return alert(message);
        var div = document.createElement('div');
        var isErr = type === 'error';
        div.className = 'alert-item pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-2xl ' + (isErr ? 'border-red-500/20 bg-[#1f0d14] text-red-300 shadow-red-900/20' : 'border-emerald-500/20 bg-[#0d1f1a] text-emerald-300 shadow-emerald-900/20');
        div.setAttribute('role','alert');
        div.innerHTML = '<i class="fa-solid '+(isErr ? 'fa-circle-exclamation text-red-400' : 'fa-circle-check text-emerald-400')+' mt-0.5 shrink-0"></i><span class="flex-1 leading-snug"></span><button type="button" class="ml-2 -mr-1 grid h-6 w-6 place-items-center rounded-lg hover:bg-white/10" onclick="this.parentElement.remove()">&times;</button>';
        div.querySelector('span').textContent = message;
        container.appendChild(div);
        setTimeout(function(){ div.remove(); }, isErr ? 7000 : 5000);
    };
    window.appToastSuccess = function(msg){ window.appToast('success', msg); };
    window.appToastError = function(msg){ window.appToast('error', msg); };
})();
</script>

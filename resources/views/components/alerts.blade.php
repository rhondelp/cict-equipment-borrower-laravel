{{-- SweetAlert2 flash alerts — single source for all server-rendered pages.
     Reads session('success') / session('welcome'), session('error'), validation $errors
     and triggers the reusable JS helper showAlert(type,message) on page load.
     No plain HTML alert divs — every path goes through SweetAlert2. --}}
@php
    $flashSuccess = session('success') ?: session('welcome');
    $flashError = session('error');
    $validationErrors = $errors->any() ? $errors->all() : [];
@endphp

<script>
(function() {
    const flashSuccess = @json($flashSuccess);
    const flashError = @json($flashError);
    const validationErrors = @json($validationErrors);

    function runAlerts() {
        const hasShowAlert = typeof window.showAlert === 'function';
        // If Vite bundle hasn't loaded yet, retry briefly; fallback to global Swal CDN if still missing
        if (!hasShowAlert) {
            if (typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function') {
                // Minimal fallback shimming showAlert if alert.js not yet loaded
                window.showAlert = function(type, msg, opts = {}) {
                    const t = (type||'info').toLowerCase();
                    const isHtml = opts.html === true;
                    const base = { background:'#131a2b', color:'#e2e8f0', confirmButtonColor:'#3b82f6', customClass:{popup:'rounded-2xl border border-white/10'} };
                    if (t==='success') return window.Swal.fire({...base, icon:'success', title:'Success!', text:isHtml?undefined:msg, html:isHtml?msg:undefined, timer:2600, timerProgressBar:true, showConfirmButton:false});
                    if (t==='error') return window.Swal.fire({...base, icon:'error', title:'Error!', text:isHtml?undefined:msg, html:isHtml?msg:undefined, confirmButtonColor:'#ef4444'});
                    if (t==='warning') return window.Swal.fire({...base, icon:'warning', title:'Validation Error', text:isHtml?undefined:msg, html:isHtml?msg:undefined, confirmButtonColor:'#f59e0b'});
                    return window.Swal.fire({...base, icon:'info', title:'Notice', text:isHtml?undefined:msg, html:isHtml?msg:undefined});
                };
            } else {
                // Still not ready — retry once
                setTimeout(runAlerts, 120);
                return;
            }
        }

        if (flashSuccess) {
            window.showAlert('success', flashSuccess);
        }
        if (flashError) {
            window.showAlert('error', flashError);
        }
        if (validationErrors && validationErrors.length) {
            const html = '<ul style="text-align:left;margin:0;padding-left:18px;">' + validationErrors.map(e => '<li>' + String(e).replace(/</g,'&lt;') + '</li>').join('') + '</ul>';
            window.showAlert('warning', html, { html: true, title: 'Validation Error' });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAlerts);
    } else {
        runAlerts();
    }
})();
</script>

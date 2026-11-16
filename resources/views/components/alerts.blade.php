@php
    $flashSuccess = session('success') ?: session('welcome');
    $flashError = session('error');

    $validationErrors = (! View::hasSection('inline-errors') && $errors->any())
        ? $errors->all()
        : [];
@endphp

<script>
(function() {
    const flashSuccess = @json($flashSuccess);
    const flashError = @json($flashError);
    const validationErrors = @json($validationErrors);

    function runAlerts() {
        const hasShowAlert = typeof window.showAlert === 'function';
        if (!hasShowAlert) {
            if (typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function') {
                window.showAlert = function(type, msg, opts = {}) {
                    const t = (type||'info').toLowerCase();
                    const isHtml = opts.html === true;
                    const base = {
                        background: '#ffffff',
                        color: '#0f172a',
                        confirmButtonColor: '#2563eb',
                        customClass: {
                            popup: 'rounded-lg border border-neutral-200 shadow-sm',
                            title: 'text-neutral-900',
                            htmlContainer: 'text-neutral-600 text-sm',
                            confirmButton: 'rounded-md',
                        },
                    };
                    if (t==='success') return window.Swal.fire({...base, icon:'success', title:'Saved', text:isHtml?undefined:msg, html:isHtml?msg:undefined, timer:2600, timerProgressBar:true, showConfirmButton:false, iconColor:'#10b981'});
                    if (t==='error') return window.Swal.fire({...base, icon:'error', title:'Something went wrong', text:isHtml?undefined:msg, html:isHtml?msg:undefined, confirmButtonColor:'#dc2626', iconColor:'#dc2626'});
                    if (t==='warning') return window.Swal.fire({...base, icon:'warning', title:'Check these fields', text:isHtml?undefined:msg, html:isHtml?msg:undefined, confirmButtonColor:'#d97706', iconColor:'#d97706'});
                    return window.Swal.fire({...base, icon:'info', title:'Notice', text:isHtml?undefined:msg, html:isHtml?msg:undefined});
                };
            } else {
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
            const html = '<ul class="text-left list-disc pl-5 m-0">' + validationErrors.map(e => '<li>' + String(e).replace(/</g,'&lt;') + '</li>').join('') + '</ul>';
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

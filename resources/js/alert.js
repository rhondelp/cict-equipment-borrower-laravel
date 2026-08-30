import Swal from 'sweetalert2';

/**
 * Reusable SweetAlert2 helper — single Swal.fire() config.
 * Do not duplicate Swal.fire() inline; call showAlert() everywhere.
 *
 * Icons/colors:
 *  success -> green check (auto-close)
 *  error   -> red X (no auto-close)
 *  warning -> amber warning for validation (no auto-close)
 */
export function showAlert(type, message, options = {}) {
    const t = (type || 'info').toLowerCase();
    const isHtml = options.html === true;

    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    const base = {
        background: isDark ? '#131a2b' : '#ffffff',
        color: isDark ? '#f1f5f9' : '#0f172a',
        confirmButtonColor: '#5b8de0',
        customClass: {
            popup: isDark ? 'rounded-xl border border-white/10 shadow-2xl' : 'rounded-xl border border-slate-200 shadow-2xl',
            title: isDark ? 'text-white tracking-tight' : 'text-slate-900 tracking-tight',
            htmlContainer: isDark ? 'text-slate-300 text-[13px] leading-relaxed' : 'text-slate-600 text-[13px] leading-relaxed',
        },
    };

    if (t === 'success') {
        return Swal.fire({
            ...base,
            icon: 'success',
            title: options.title || 'Success!',
            text: isHtml ? undefined : message,
            html: isHtml ? message : undefined,
            timer: options.timer ?? 2600,
            timerProgressBar: true,
            showConfirmButton: false,
            iconColor: '#10b981',
        });
    }

    if (t === 'error') {
        return Swal.fire({
            ...base,
            icon: 'error',
            title: options.title || 'Error!',
            text: isHtml ? undefined : message,
            html: isHtml ? message : undefined,
            showConfirmButton: true,
            confirmButtonText: options.confirmText || 'OK',
            confirmButtonColor: '#ef4444',
            iconColor: '#ef4444',
        });
    }

    if (t === 'warning') {
        return Swal.fire({
            ...base,
            icon: 'warning',
            title: options.title || 'Validation Error',
            text: isHtml ? undefined : message,
            html: isHtml ? message : undefined,
            showConfirmButton: true,
            confirmButtonText: options.confirmText || 'OK',
            confirmButtonColor: '#f59e0b',
            iconColor: '#f59e0b',
        });
    }

    // info / default
    return Swal.fire({
        ...base,
        icon: 'info',
        title: options.title || 'Notice',
        text: isHtml ? undefined : message,
        html: isHtml ? message : undefined,
        showConfirmButton: true,
        confirmButtonText: options.confirmText || 'OK',
    });
}

// Convenience shorthands
export function showSuccess(msg, opts) { return showAlert('success', msg, opts); }
export function showError(msg, opts) { return showAlert('error', msg, opts); }
export function showWarning(msg, opts) { return showAlert('warning', msg, opts); }

// Confirm dialog — theme-aware
export function showConfirm({ title = 'Are you sure?', text = '', icon = 'warning', confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    return Swal.fire({
        background: isDark ? '#131a2b' : '#ffffff',
        color: isDark ? '#f1f5f9' : '#0f172a',
        customClass: { popup: isDark ? 'rounded-xl border border-white/10 shadow-2xl' : 'rounded-xl border border-slate-200 shadow-xl', title: isDark ? 'text-white tracking-tight' : 'text-slate-900 tracking-tight', htmlContainer: isDark ? 'text-slate-300 text-[13px]' : 'text-slate-600 text-[13px]' },
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#5b8de0',
        cancelButtonColor: isDark ? '#1e293b' : '#e2e8f0',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        iconColor: icon === 'warning' ? '#f59e0b' : '#5b8de0',
    });
}

// Expose globally for inline Blade scripts and legacy callers
if (typeof window !== 'undefined') {
    window.showAlert = showAlert;
    window.showSuccess = showSuccess;
    window.showError = showError;
    window.showWarning = showWarning;
    window.showConfirm = showConfirm;
    // Backwards-compat aliases used by older transaction JS
    window.appToast = showAlert;
    window.appToastSuccess = (m, o) => showSuccess(m, o);
    window.appToastError = (m, o) => showError(m, o);
}

export default showAlert;

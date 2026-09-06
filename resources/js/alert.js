import Swal from 'sweetalert2';

/**
 * Reusable SweetAlert2 helper — single Swal.fire() config.
 * Do not duplicate Swal.fire() inline; call showAlert() everywhere.
 *
 * Light flat theme:
 *  success -> green check (auto-close)
 *  error   -> red X (no auto-close)
 *  warning -> amber warning for validation (no auto-close)
 */
export function showAlert(type, message, options = {}) {
    const t = (type || 'info').toLowerCase();
    const isHtml = options.html === true;

    const base = {
        background: '#ffffff',
        color: '#0f172a',
        confirmButtonColor: '#2563eb',
        customClass: {
            popup: 'rounded-lg border border-neutral-200 shadow-sm',
            title: 'text-neutral-900 tracking-tight',
            htmlContainer: 'text-neutral-600 text-sm leading-relaxed',
            confirmButton: 'rounded-md',
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
            confirmButtonColor: '#dc2626',
            iconColor: '#dc2626',
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
            confirmButtonColor: '#d97706',
            iconColor: '#d97706',
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
export function showError(msg, opts)   { return showAlert('error', msg, opts); }
export function showWarning(msg, opts) { return showAlert('warning', msg, opts); }

// Confirm dialog — light theme
export function showConfirm({ title = 'Are you sure?', text = '', icon = 'warning', confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
    return Swal.fire({
        background: '#ffffff',
        color: '#0f172a',
        customClass: {
            popup: 'rounded-lg border border-neutral-200 shadow-sm',
            title: 'text-neutral-900 tracking-tight',
            htmlContainer: 'text-neutral-600 text-sm',
            confirmButton: 'rounded-md',
            cancelButton: 'rounded-md',
        },
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#e2e8f0',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        iconColor: icon === 'warning' ? '#d97706' : '#2563eb',
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

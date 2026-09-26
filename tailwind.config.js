export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f5ff',
          100: '#e0ebff',
          200: '#c7d7ff',
          300: '#a8bff0',
          400: '#8aa8e8',
          500: '#5b8de0',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        neutral: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
          950: '#0a0e1a',
        },
        success: {
          50: '#ecfdf5',
          100: '#d1fae5',
          200: '#a7f3d0',
          300: '#6ee7b7',
          400: '#34d399',
          500: '#10b981',
          600: '#059669',
          700: '#047857',
          800: '#065f46',
        },
        warning: {
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
        },
        danger: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
        },
      },
      fontFamily: {
        sans: ['Poppins', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
        display: ['Poppins', 'sans-serif'],
        // Long-form reading only — the two legal documents. Poppins is a UI
        // face; it holds up over a label and falls apart over four hundred
        // words at a 66-character measure. Loaded by layouts/legal.blade.php
        // alone rather than on every page.
        serif: ['Source Serif 4', 'Source Serif Pro', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
        // Figures and step labels on the landing page only — never running
        // text. Loaded by welcome.blade.php alone.
        mono: ['IBM Plex Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
      },
      fontSize: {
        // Tracking is normal from `base` up: tight letter-spacing costs legibility,
        // and Poppins is already wider than Inter at the same px size.
        xs:   ['12px', { lineHeight: '1.5',   letterSpacing: '0.04em' }],
        sm:   ['13px', { lineHeight: '1.5',   letterSpacing: '0.01em' }],
        base: ['16px', { lineHeight: '1.6',   letterSpacing: 'normal' }],
        lg:   ['18px', { lineHeight: '1.5',   letterSpacing: 'normal' }],
        xl:   ['22px', { lineHeight: '1.35',  letterSpacing: 'normal' }],
        '2xl':['26px', { lineHeight: '1.25',  letterSpacing: 'normal' }],
        '3xl':['32px', { lineHeight: '1.2',   letterSpacing: 'normal' }],
      },
      spacing: {
        '18': '4.5rem',
        '112': '28rem',
        '128': '32rem',
      },
      maxWidth: {
        'content': '1440px',
      },
      borderRadius: {
        card: '8px',
        input: '6px',
        btn: '6px',
      },
      boxShadow: {
        // Tinted with the neutral-900 hue (15,23,42) rather than pure black, so a
        // shadow reads as the page's own grey darkening instead of a grey wash.
        flat: '0 1px 2px rgba(15, 23, 42, 0.06)',
        card: '0 1px 2px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.05)',
        pop:  '0 4px 12px rgba(15, 23, 42, 0.08), 0 2px 4px rgba(15, 23, 42, 0.04)',
      },
      // Named scale so nothing has to invent a z-index. Matches what the views
      // already use: sidebar overlay 40 / sidebar 50, header 30, modal 50.
      zIndex: {
        base: '0',
        sticky: '30',
        overlay: '40',
        modal: '50',
        toast: '60',
      },
    },
  },
  plugins: [],
}

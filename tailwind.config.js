export default {
  darkMode: 'class',
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
          600: '#4a6fa5',
          700: '#3d5a85',
          800: '#2e4466',
          900: '#1e2d42',
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
          500: '#10b981',
          600: '#059669',
          700: '#047857',
        },
        warning: {
          50: '#fffbeb',
          100: '#fef3c7',
          500: '#f59e0b',
          600: '#d97706',
        },
        danger: {
          50: '#fef2f2',
          100: '#fee2e2',
          500: '#ef4444',
          600: '#dc2626',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
        display: ['Inter', 'sans-serif'],
      },
      fontSize: {
        'xs': ['11px', { lineHeight: '1.4', letterSpacing: '0.06em' }],
        'sm': ['12px', { lineHeight: '1.5' }],
        'base': ['13px', { lineHeight: '1.6' }],
        'lg': ['15px', { lineHeight: '1.5' }],
        'xl': ['18px', { lineHeight: '1.3', letterSpacing: '-0.02em' }],
        '2xl': ['22px', { lineHeight: '1.2', letterSpacing: '-0.03em' }],
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
        'card': '14px',
        'input': '8px',
        'btn': '8px',
      },
      boxShadow: {
        'tinted': '0 8px 24px rgba(10,14,26,0.45), 0 1px 0 rgba(148,163,184,0.04) inset',
        'tinted-hover': '0 10px 28px rgba(10,14,26,0.50), 0 1px 0 rgba(255,255,255,0.06) inset',
      },
    },
  },
  plugins: [],
}

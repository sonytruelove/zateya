/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,ts}'],
  theme: {
    extend: {
      colors: {
        app: 'var(--z-bg-app)',
        surface: 'var(--z-surface)',
        'surface-muted': 'var(--z-surface-muted)',
        ink: {
          DEFAULT: 'var(--z-text)',
          soft: 'var(--z-text-soft)',
        },
        line: 'var(--z-border)',
        brand: {
          DEFAULT: 'var(--z-accent)',
          hover: 'var(--z-accent-hover)',
          soft: 'var(--z-accent-soft)',
        },
        success: 'var(--z-success)',
        warning: 'var(--z-warning)',
        danger: 'var(--z-danger)',
      },
      fontFamily: {
        sans: ['"Golos Text"', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      borderRadius: {
        DEFAULT: 'var(--z-radius)',
        card: 'var(--z-radius-card)',
      },
      boxShadow: {
        soft: '0 1px 2px rgba(16, 24, 40, 0.04), 0 1px 3px rgba(16, 24, 40, 0.06)',
        pop: '0 8px 24px rgba(16, 24, 40, 0.10)',
      },
      maxWidth: {
        content: '1200px',
      },
      transitionDuration: {
        DEFAULT: '160ms',
      },
    },
  },
  plugins: [],
}

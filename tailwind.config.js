/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.ts',
  ],
  theme: {
    extend: {
      colors: {
        canvas: {
          DEFAULT: '#0B0F19',
        },
        surface: {
          elevated: '#111827',
          card: '#1F2937',
          'card-hover': '#283548',
        },
        accent: {
          indigo: {
            DEFAULT: '#6366F1',
            hover: '#4F46E5',
          },
          cyan: {
            DEFAULT: '#0EA5E9',
            hover: '#0284C7',
          },
          linkedin: '#0A66C2',
          x: '#E7E9EA',
        },
        semantic: {
          success: '#10B981',
          warning: '#F59E0B',
          danger: '#F43F5E',
        },
        border: {
          subtle: 'rgba(255, 255, 255, 0.08)',
          strong: 'rgba(255, 255, 255, 0.16)',
          focus: 'rgba(99, 102, 241, 0.60)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      boxShadow: {
        glass: '0 8px 32px 0 rgba(0, 0, 0, 0.37)',
        elevated: '0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5)',
        glow: '0 0 20px -3px rgba(99, 102, 241, 0.45)',
      },
      backdropBlur: {
        glass: '12px',
        modal: '16px',
      },
    },
  },
  plugins: [],
};

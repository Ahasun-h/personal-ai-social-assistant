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
          DEFAULT: '#F4F6F8',
          alt: '#EEF2F6',
        },
        surface: {
          card: '#FFFFFF',
          elevated: '#F9FAFB',
          teal: '#162E35',
          'teal-dark': '#0F2126',
        },
        brand: {
          mint: '#9EE493',
          'mint-light': '#B8F2AF',
          'mint-dark': '#74C69D',
          emerald: '#10B981',
          teal: '#1E3A40',
        },
        accent: {
          indigo: {
            DEFAULT: '#4F46E5',
            hover: '#4338CA',
          },
          cyan: {
            DEFAULT: '#0284C7',
            hover: '#0369A1',
          },
          linkedin: '#0A66C2',
          x: '#0F1419',
        },
        semantic: {
          success: '#10B981',
          warning: '#F59E0B',
          danger: '#F43F5E',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      boxShadow: {
        soft: '0 4px 20px -2px rgba(15, 23, 42, 0.04)',
        card: '0 8px 30px -4px rgba(15, 23, 42, 0.05)',
        pill: '0 2px 10px rgba(0, 0, 0, 0.04)',
        contrast: '0 12px 36px -6px rgba(22, 46, 53, 0.25)',
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
        '4xl': '2rem',
      },
    },
  },
  plugins: [],
};

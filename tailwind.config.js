/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './templates/**/*.php',
    './template-parts/**/*.php',
    './src/blocks/**/*.{js,jsx,php}',
    './build/blocks/**/*.{js,jsx,php}',
    './inc/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          // Replace these with your project's brand colors
          primary: '#1a1a2e',
          secondary: '#16213e',
          accent: '#e94560',
          light: '#f5f5f5',
          dark: '#0f0f23',
        },
        text: {
          primary: '#1a1a1a',
          secondary: '#4a4a4a',
          muted: '#6a6a6a',
          light: '#FFFFFF',
        },
        border: {
          light: '#d4d2cd',
        },
      },
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      container: {
        center: true,
        padding: '1.5rem',
      },
      keyframes: {
        'fade-in': {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
        'fade-in-up': {
          from: { opacity: '0', transform: 'translateY(2rem)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        'slide-down': {
          from: { opacity: '0', transform: 'translateY(-10px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'fade-in': 'fade-in 0.6s ease-out forwards',
        'fade-in-up': 'fade-in-up 0.8s ease-out forwards',
        'slide-down': 'slide-down 0.3s ease-out forwards',
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}

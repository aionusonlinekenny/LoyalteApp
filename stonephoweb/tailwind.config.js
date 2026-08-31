/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        lux: {
          bg:       '#0F0D0B',
          surface:  '#1A1714',
          elevated: '#221F1B',
          border:   '#2A2520',
          gold:     '#C9A96E',
          'gold-h': '#DFC08A',
          cream:    '#F5EDD8',
          muted:    '#7A6E64',
          subtle:   '#4A4540',
        },
      },
      fontFamily: {
        display: ['"Cormorant Garamond"', 'Georgia', 'serif'],
      },
    },
  },
  plugins: [],
};

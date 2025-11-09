/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#FFF7E6',
          100: '#FFEBC2',
          200: '#FFD98A',
          300: '#FFC455',
          400: '#FFAD26',
          500: '#F59E0B',
          600: '#D98206',
          700: '#B56407',
          800: '#924F0B',
          900: '#783F0B',
          DEFAULT: '#F59E0B', 
        },
        surface: {
          light: '#ffffff',
          dark: '#0B0B0D',
        },
        background: {
          light: '#f8f8f6',
          dark: '#221d10', 
        },
        accent: '#0EA5E9',
      },
      fontFamily: {
        display: ["Work Sans"]
      },
      borderRadius: {
        DEFAULT: "0.25rem",
        lg: "0.5rem",
        xl: "0.75rem",
        xl2: "1rem",
        full: "9999px"
      },
      boxShadow: {
        card: '0 6px 20px rgba(0,0,0,0.06)',
      },
    },
  },
  plugins: [],
}

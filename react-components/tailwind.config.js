/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
    "./react-components/**/*.{js,jsx}",
  ],
  theme: {
    extend: {
      // Custom sidebar widths
      width: {
        'sidebar-expanded': '240px',
        'sidebar-collapsed': '64px',
      },
      // Custom transition durations
      transitionDuration: {
        'sidebar': '250ms',
      },
    },
  },
  plugins: [],
}


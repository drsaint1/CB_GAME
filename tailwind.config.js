/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        montserrat: ['Montserrat', 'sans-serif'],
        dmSans: ['DM Sans', 'sans-serif'],
      },
      colors: {
        primary: "#1D4ED8", // Define a primary brand color
        secondary: "#9333EA", // Define a secondary color
        accent: "#FACC15", // Accent color for highlights
        background: "#F9FAFB", // Background color
        textPrimary: "#1F2937", // Text color
        textSecondary: "#4B5563", // Muted text
      },
      spacing: {
        18: "4.5rem", // Custom spacing for design needs
        22: "5.5rem",
        30: "7.5rem",
      },
      screens: {
        'xs': '480px', // Add extra small screen size for tighter breakpoints
        '3xl': '1920px', // Extra large for very wide screens
      },
      boxShadow: {
        card: "0 4px 6px rgba(0, 0, 0, 0.1)", // Custom shadow for cards
        deep: "0 10px 15px rgba(0, 0, 0, 0.2)",
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'), // For rich text styles
    require('@tailwindcss/forms'), // For form styles
    require('@tailwindcss/aspect-ratio'), // For controlling aspect ratios
  ],
};

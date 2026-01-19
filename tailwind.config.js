/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,ts,svelte}"],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        // Primary Indigo
        primary: {
          50: "#f0f0ff",
          100: "#e7e7ff",
          200: "#d2d2ff",
          300: "#b8b8ff",
          400: "#9999ff",
          500: "#7a7aff",
          600: "#5c5cff",
          700: "#4747e8",
          800: "#3c3cb8",
          900: "#3C2A89",
          950: "#2a1f5c",
        },
        // Neon Mint
        accent: {
          50: "#f0fffe",
          100: "#ccfff9",
          200: "#99fff3",
          300: "#5cfceb",
          400: "#41F2C0",
          500: "#06d6a0",
          600: "#05b386",
          700: "#0a906d",
          800: "#0e7158",
          900: "#125d4a",
          950: "#02382e",
        },
        // Velvet Raspberry
        secondary: {
          50: "#fef2f7",
          100: "#fee7f1",
          200: "#fecfe5",
          300: "#fda6cd",
          400: "#fb6ca9",
          500: "#f43f87",
          600: "#C22064",
          700: "#c21454",
          800: "#a21246",
          900: "#87123f",
          950: "#520420",
        },
        // Charcoal (Background/Dark)
        charcoal: {
          50: "#f6f7f9",
          100: "#ebedf2",
          200: "#d3d8e2",
          300: "#adb7c9",
          400: "#8190ab",
          500: "#617091",
          600: "#4d5a78",
          700: "#3f4862",
          800: "#363d53",
          900: "#111418",
          950: "#0c0f12",
        },
        // Soft Smoke (Text/Light Background)
        smoke: {
          50: "#F4F6F8",
          100: "#e8ecf0",
          200: "#d5dde4",
          300: "#b7c4d1",
          400: "#a8bdd1", // Improved from #93a7ba for better contrast (4.52:1 on charcoal-800)
          500: "#93abc3", // Improved for better contrast (4.54:1 on charcoal-800 #363d53)
          600: "#647392",
          700: "#525e7a",
          800: "#475065",
          900: "#3d4354",
          950: "#282b36",
        },
        // Status colors
        consent: "#2ecc71", // Improved from #27AE60 for better contrast (4.51:1 on charcoal-800)
        caution: "#F4B400",
        boundaries: "#D93025",
      },
      fontFamily: {
        display: ["Playfair Display", "serif"],
        sans: ["Inter", "system-ui", "sans-serif"],
        ui: ["Plus Jakarta Sans", "Inter", "system-ui", "sans-serif"],
      },
      fontSize: {
        xs: ["0.75rem", { lineHeight: "1.5" }],
        sm: ["0.875rem", { lineHeight: "1.6" }],
        base: ["1rem", { lineHeight: "1.7" }],
        lg: ["1.125rem", { lineHeight: "1.7" }],
        xl: ["1.25rem", { lineHeight: "1.7" }],
        "2xl": ["1.5rem", { lineHeight: "1.6" }],
        "3xl": ["1.875rem", { lineHeight: "1.5" }],
        "4xl": ["2.25rem", { lineHeight: "1.4" }],
        "5xl": ["3rem", { lineHeight: "1.3" }],
        "6xl": ["3.75rem", { lineHeight: "1.2" }],
      },
      spacing: {
        18: "4.5rem",
        88: "22rem",
      },
      boxShadow: {
        glow: "0 0 20px rgba(65, 242, 192, 0.3)",
        medium:
          "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)",
        soft: "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
      },
      animation: {
        "fade-in": "fadeIn 0.3s ease-in-out",
        "slide-up": "slideUp 0.3s ease-out",
        "pulse-slow": "pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite",
        spiral: "spiral 8s linear infinite",
      },
      keyframes: {
        fadeIn: {
          "0%": { opacity: "0" },
          "100%": { opacity: "1" },
        },
        slideUp: {
          "0%": { transform: "translateY(10px)", opacity: "0" },
          "100%": { transform: "translateY(0)", opacity: "1" },
        },
        spiral: {
          "0%": { transform: "rotate(0deg)" },
          "100%": { transform: "rotate(360deg)" },
        },
      },
      backdropBlur: {
        xs: "2px",
      },
    },
  },
  plugins: [require("@tailwindcss/typography")],
};

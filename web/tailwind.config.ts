import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        gold: {
          50:  "#fff9e6",
          100: "#fef0bf",
          200: "#fde38a",
          300: "#fbd14d",
          400: "#f9c220",
          500: "#d4a017",   // brand primary
          600: "#a87c10",
          700: "#7c5a0b",
          800: "#513c08",
          900: "#2a1f04",
        },
      },
      fontFamily: {
        sans: ["Inter", "system-ui", "sans-serif"],
      },
    },
  },
  plugins: [],
};

export default config;

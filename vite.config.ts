import { svelte } from "@sveltejs/vite-plugin-svelte";
import { defineConfig } from "vite";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: "dist",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["svelte", "svelte-spa-router"],
          calendar: ["rrule", "dayjs", "ical.js"],
          ui: ["marked", "dompurify", "zod"],
        },
      },
    },
  },
  server: {
    proxy: {
      "/api": {
        target: "http://localhost:8000",
        changeOrigin: true,
      },
      "/calendar.ics": {
        target: "http://localhost:8000",
        changeOrigin: true,
      },
    },
    // Security headers für Entwicklungsserver
    headers: {
      "X-Frame-Options": "DENY",
      "X-Content-Type-Options": "nosniff",
      "X-XSS-Protection": "1; mode=block",
      "Referrer-Policy": "strict-origin-when-cross-origin",
    },
  },
  test: {
    globals: true,
    environment: "jsdom",
    include: ["src/**/*.test.ts"],
    exclude: ["tests/**/*.spec.ts"],
  },
});

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
  },
});

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
        // vite 8 bundles with rolldown, which only accepts the function form of
        // manualChunks (the object form throws "manualChunks is not a function").
        // Group the same vendor libraries by their node_modules path.
        manualChunks(id: string) {
          if (!id.includes("node_modules")) return;
          if (/[\\/]node_modules[\\/](svelte|svelte-spa-router)[\\/]/.test(id))
            return "vendor";
          if (/[\\/]node_modules[\\/](rrule|dayjs|ical\.js)[\\/]/.test(id))
            return "calendar";
          if (/[\\/]node_modules[\\/](marked|dompurify|zod)[\\/]/.test(id))
            return "ui";
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
      "/sitemap.xml": {
        target: "http://localhost:8000",
        changeOrigin: true,
      },
      "/robots.txt": {
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

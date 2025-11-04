import { defineConfig } from "vitepress";

export default defineConfig({
  title: "Hypnose Stammtisch Docs",
  description: "Technische Dokumentation für Hypnose-Stammtisch.de",
  lang: "de-DE",
  lastUpdated: true,
  cleanUrls: true,
  srcDir: ".",
  outDir: ".vitepress/dist",
  themeConfig: {
    siteTitle: "Hypnose Stammtisch",
    nav: [
      { text: "Übersicht", link: "/" },
      { text: "Admin", link: "/admin/management" },
      {
        text: "Security",
        items: [
          { text: "Implementierung", link: "/security/implementation" },
          { text: "Account Lockout", link: "/security/lockout" },
          { text: "Testkonfiguration", link: "/security/test-configuration" },
        ],
      },
      { text: "Features", link: "/features/map-feature" },
      { text: "Backend", link: "/backend/database-api-enhancement" },
      {
        text: "Entwicklung",
        items: [
          { text: "Code-Qualität", link: "/development/code-quality" },
          { text: "Benutzerstruktur", link: "/architecture/user-structure" },
        ],
      },
    ],
    sidebar: {
      "/admin/": [
        {
          text: "Admin",
          items: [{ text: "Management System", link: "/admin/management" }],
        },
      ],
      "/security/": [
        {
          text: "Security",
          items: [
            { text: "Implementierung", link: "/security/implementation" },
            { text: "Account Lockout", link: "/security/lockout" },
            { text: "Testkonfiguration", link: "/security/test-configuration" },
          ],
        },
      ],
      "/features/": [
        {
          text: "Features",
          items: [{ text: "Map Feature", link: "/features/map-feature" }],
        },
      ],
      "/backend/": [
        {
          text: "Backend",
          items: [
            {
              text: "Database API",
              link: "/backend/database-api-enhancement",
            },
          ],
        },
      ],
      "/architecture/": [
        {
          text: "Architektur",
          items: [
            { text: "Benutzerstruktur", link: "/architecture/user-structure" },
          ],
        },
      ],
      "/development/": [
        {
          text: "Entwicklung",
          items: [
            {
              text: "Code-Qualitätsstandards",
              link: "/development/code-quality",
            },
          ],
        },
      ],
    },
    socialLinks: [
      {
        icon: "github",
        link: "https://github.com/Kink-Development-Group/hypnose-stammtisch.de",
      },
    ],
    footer: {
      message: "Vertrauliche interne Dokumentation",
      copyright: `© ${new Date().getFullYear()} Hypnose Stammtisch`,
    },
  },
  markdown: {
    lineNumbers: true,
  },
});

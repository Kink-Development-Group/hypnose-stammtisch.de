<script lang="ts">
  import { link } from "svelte-spa-router";
  import Logo from "../ui/Logo.svelte";

  const currentYear = new Date().getFullYear();

  const footerLinks = {
    legal: [
      { href: "/privacy", label: "Datenschutz" },
      { href: "/imprint", label: "Impressum" },
      { href: "/code-of-conduct", label: "Verhaltenskodex" },
    ],
    community: [
      { href: "/about", label: "Über uns" },
      { href: "/resources", label: "Ressourcen" },
      { href: "/contact", label: "Kontakt" },
    ],
    social: [
      {
        href: "https://github.com/Kink-Development-Group/hypnose-stammtisch.de",
        label: "GitHub",
        external: true,
        icon: "github",
      },
    ],
  };
</script>

<footer class="bg-charcoal-800 border-t border-charcoal-700 mt-auto">
  <div class="container mx-auto px-4 py-12">
    <!-- Main footer content -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
      <!-- Brand section -->
      <div class="md:col-span-1">
        <div class="flex items-center space-x-3 mb-4">
          <Logo className="w-8 h-8" />
          <div>
            <div class="text-lg font-display font-bold text-smoke-50">
              Hypnose Stammtisch
            </div>
            <div class="text-sm text-accent-400">Deep Dive. Safe Play.</div>
          </div>
        </div>
        <p class="text-smoke-300 text-sm leading-relaxed">
          Eine community-fokussierte Plattform für sichere und respektvolle
          Hypnose-Veranstaltungen und Workshops.
        </p>
      </div>

      <!-- Community links -->
      <div class="md:col-span-1">
        <h3 class="text-smoke-50 font-semibold mb-4">Community</h3>
        <nav aria-label="Community-Navigation">
          <ul class="space-y-2">
            {#each footerLinks.community as linkItem}
              <li>
                <a
                  href={linkItem.href}
                  use:link
                  class="text-smoke-300 hover:text-accent-400 text-sm transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-800 rounded-sm"
                >
                  {linkItem.label}
                </a>
              </li>
            {/each}
          </ul>
        </nav>
      </div>

      <!-- Legal links -->
      <div class="md:col-span-1">
        <h3 class="text-smoke-50 font-semibold mb-4">Rechtliches</h3>
        <nav aria-label="Rechtliche Navigation">
          <ul class="space-y-2">
            {#each footerLinks.legal as linkItem}
              <li>
                <a
                  href={linkItem.href}
                  use:link
                  class="text-smoke-300 hover:text-accent-400 text-sm transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-800 rounded-sm"
                >
                  {linkItem.label}
                </a>
              </li>
            {/each}
          </ul>
        </nav>
      </div>

      <!-- Newsletter/Contact -->
      <div class="md:col-span-1">
        <h3 class="text-smoke-50 font-semibold mb-4">Kontakt</h3>
        <p class="text-smoke-300 text-sm mb-4">
          Möchtest du über neue Events informiert werden?
        </p>
        <a href="/contact" use:link class="btn btn-outline text-sm">
          Kontakt aufnehmen
        </a>
      </div>
    </div>

    <!-- Bottom section -->
    <div class="border-t border-charcoal-700 pt-8">
      <div
        class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0"
      >
        <!-- Copyright -->
        <div class="text-smoke-400 text-sm">
          © {currentYear} Hypnose Stammtisch. Alle Rechte vorbehalten.
        </div>

        <!-- Social links -->
        <div class="flex items-center space-x-4">
          {#each footerLinks.social as socialLink}
            <a
              href={socialLink.href}
              target="_blank"
              rel="noopener noreferrer"
              class="text-smoke-400 hover:text-accent-400 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-800 rounded-sm p-1"
              aria-label="{socialLink.label} (öffnet in neuem Tab)"
            >
              {#if socialLink.icon === "github"}
                <svg
                  class="w-5 h-5"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                  />
                </svg>
              {/if}
              <span class="sr-only">{socialLink.label}</span>
            </a>
          {/each}
        </div>

        <!-- GDPR/Privacy notice -->
        <div class="text-smoke-400 text-xs">
          <span class="inline-flex items-center">
            <svg
              class="w-3 h-3 mr-1"
              fill="currentColor"
              viewBox="0 0 20 20"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
            DSGVO-konform
          </span>
        </div>
      </div>
    </div>
  </div>
</footer>

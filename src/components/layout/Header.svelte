<script lang="ts">
  import { link } from "svelte-spa-router";
  import { isMobileMenuOpen } from "../../stores/ui";
  import Logo from "../ui/Logo.svelte";

  const navigation = [
    { href: "/", label: "Home", ariaLabel: "Zur Startseite" },
    { href: "/events", label: "Events", ariaLabel: "Zu den Veranstaltungen" },
    { href: "/resources", label: "Ressourcen", ariaLabel: "Zu den Ressourcen" },
    { href: "/about", label: "Über uns", ariaLabel: "Zur Über-uns-Seite" },
    {
      href: "/code-of-conduct",
      label: "Verhaltenskodex",
      ariaLabel: "Zum Verhaltenskodex",
    },
  ];

  const toggleMobileMenu = () => {
    isMobileMenuOpen.update((open) => !open);
  };

  const closeMobileMenu = () => {
    isMobileMenuOpen.set(false);
  };

  // Handle escape key for mobile menu
  const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape" && $isMobileMenuOpen) {
      closeMobileMenu();
    }
  };
</script>

<svelte:window on:keydown={handleKeydown} />

<header class="bg-charcoal-900 border-b border-charcoal-700 sticky top-0 z-30">
  <nav class="container mx-auto px-4 py-4" aria-label="Hauptnavigation">
    <div class="flex items-center justify-between">
      <!-- Logo and Brand -->
      <div class="flex items-center space-x-3">
        <a
          href="/"
          use:link
          class="flex items-center space-x-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-900 rounded-md p-1"
          aria-label="Hypnose Stammtisch - Zur Startseite"
        >
          <Logo className="w-10 h-10 md:w-12 md:h-12" />
          <div class="hidden sm:block">
            <div
              class="text-xl md:text-2xl font-display font-bold text-smoke-50"
            >
              Hypnose Stammtisch
            </div>
            <div class="text-sm text-accent-400 font-medium">
              Deep Dive. Safe Play.
            </div>
          </div>
        </a>
      </div>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center space-x-6">
        {#each navigation as item}
          <a
            href={item.href}
            use:link
            class="text-smoke-300 hover:text-smoke-50 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-900"
            aria-label={item.ariaLabel}
          >
            {item.label}
          </a>
        {/each}
      </div>

      <!-- Mobile menu button -->
      <button
        class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-smoke-400 hover:text-smoke-50 hover:bg-charcoal-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-900 transition-colors duration-200"
        aria-expanded={$isMobileMenuOpen}
        aria-controls="mobile-menu"
        aria-label="Hauptmenü {$isMobileMenuOpen ? 'schließen' : 'öffnen'}"
        on:click={toggleMobileMenu}
      >
        <span class="sr-only">Hauptmenü öffnen</span>
        <!-- Hamburger icon -->
        <svg
          class="block h-6 w-6 transition-transform duration-200 {$isMobileMenuOpen
            ? 'rotate-90'
            : ''}"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          {#if $isMobileMenuOpen}
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          {:else}
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          {/if}
        </svg>
      </button>
    </div>

    <!-- Mobile Navigation -->
    {#if $isMobileMenuOpen}
      <div
        id="mobile-menu"
        class="md:hidden mt-4 pb-4 border-t border-charcoal-700 pt-4"
        role="menu"
        aria-orientation="vertical"
      >
        <div class="space-y-2">
          {#each navigation as item}
            <a
              href={item.href}
              use:link
              class="block px-3 py-2 rounded-md text-base font-medium text-smoke-300 hover:text-smoke-50 hover:bg-charcoal-800 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-900"
              role="menuitem"
              aria-label={item.ariaLabel}
              on:click={closeMobileMenu}
            >
              {item.label}
            </a>
          {/each}
        </div>
      </div>
    {/if}
  </nav>
</header>

<style>
  /* Ensure smooth transitions */
  nav {
    transition: all 0.3s ease;
  }

  /* Mobile menu animation */
  #mobile-menu {
    animation: slideDown 0.2s ease-out;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    nav,
    #mobile-menu {
      transition: none;
      animation: none;
    }
  }
</style>

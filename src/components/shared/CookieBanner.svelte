<script lang="ts">
  import { link } from "svelte-spa-router";
  import { fade, fly } from "svelte/transition";
  import { complianceStore } from "../../stores/compliance";

  // Reactive state from store
  $: showBanner = $complianceStore.showCookieBanner;
</script>

{#if showBanner}
  <div
    class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
    transition:fly={{ y: 100, duration: 300 }}
    role="dialog"
    aria-modal="true"
    aria-labelledby="cookie-banner-title"
    aria-describedby="cookie-banner-description"
  >
    <div
      class="mx-auto max-w-4xl rounded-xl bg-charcoal-800 border border-charcoal-600 shadow-2xl"
    >
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-start gap-4 mb-4">
          <div
            class="flex-shrink-0 w-12 h-12 rounded-full bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-2xl" aria-hidden="true">🍪</span>
          </div>
          <div>
            <h2
              id="cookie-banner-title"
              class="text-lg font-display font-semibold text-smoke-50"
            >
              Diese Website verwendet Cookies
            </h2>
            <p
              id="cookie-banner-description"
              class="text-smoke-300 text-sm mt-1"
            >
              Wir nutzen Cookies, um dir die bestmögliche Erfahrung zu bieten
              und unsere Website zu verbessern.
            </p>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-6 pl-0 md:pl-16">
          <p class="text-smoke-400 text-sm leading-relaxed">
            Wir verwenden <strong class="text-smoke-200"
              >essenzielle Cookies</strong
            >, die für den Betrieb der Website notwendig sind. Zusätzlich können
            wir mit deiner Zustimmung
            <strong class="text-smoke-200">optionale Cookies</strong> für Präferenzen,
            Statistiken und Marketing einsetzen. Du kannst deine Einstellungen jederzeit
            anpassen.
          </p>
          <p class="text-smoke-400 text-sm mt-2">
            Mehr Informationen findest du in unserer
            <a
              href="/cookies"
              use:link
              class="text-accent-400 hover:text-accent-300 underline underline-offset-2"
            >
              Cookie-Richtlinie
            </a>
            und
            <a
              href="/privacy"
              use:link
              class="text-accent-400 hover:text-accent-300 underline underline-offset-2"
            >
              Datenschutzerklärung</a
            >.
          </p>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 pl-0 md:pl-16">
          <!-- Reject All - Same prominence as Accept -->
          <button
            type="button"
            on:click={() => complianceStore.rejectAll()}
            class="flex-1 px-6 py-3 rounded-lg border border-charcoal-500 bg-charcoal-700 text-smoke-100 font-medium hover:bg-charcoal-600 hover:border-charcoal-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200"
          >
            Nur Notwendige
          </button>

          <!-- Settings -->
          <button
            type="button"
            on:click={() => complianceStore.openSettings()}
            class="flex-1 px-6 py-3 rounded-lg border border-charcoal-500 bg-charcoal-700 text-smoke-100 font-medium hover:bg-charcoal-600 hover:border-charcoal-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200"
          >
            Einstellungen
          </button>

          <!-- Accept All -->
          <button
            type="button"
            on:click={() => complianceStore.acceptAll()}
            class="flex-1 px-6 py-3 rounded-lg bg-accent-500 text-charcoal-900 font-semibold hover:bg-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200"
          >
            Alle Akzeptieren
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Backdrop overlay -->
  <div
    class="fixed inset-0 bg-charcoal-950/60 z-40"
    transition:fade={{ duration: 200 }}
    aria-hidden="true"
  ></div>
{/if}

<script lang="ts">
  import { consentStore } from "../../stores/consent";
  import { link } from "svelte-spa-router";

  let showSettings = false;
  let isClosing = false;

  let preferences = {
    functional: false,
    analytics: false,
    marketing: false,
  };

  function handleAcceptAll() {
    isClosing = true;
    setTimeout(() => {
      consentStore.acceptAll();
    }, 300);
  }

  function handleAcceptEssential() {
    isClosing = true;
    setTimeout(() => {
      consentStore.acceptEssential();
    }, 300);
  }

  function handleSavePreferences() {
    isClosing = true;
    setTimeout(() => {
      consentStore.setPreferences(preferences);
    }, 300);
  }

  function toggleSettings() {
    showSettings = !showSettings;
  }
</script>

<!-- Cookie Banner -->
<div
  class="fixed bottom-0 left-0 right-0 z-40 p-4 md:p-6"
  class:opacity-0={isClosing}
  class:translate-y-full={isClosing}
  class:transition-all={isClosing}
  class:duration-300={isClosing}
  role="dialog"
  aria-labelledby="cookie-banner-title"
  aria-describedby="cookie-banner-description"
>
  <div class="container mx-auto max-w-5xl">
    <div class="bg-charcoal-800 border-2 border-accent-400/50 rounded-xl shadow-2xl overflow-hidden">
      <!-- Main Banner Content -->
      <div class="p-6 md:p-8">
        <div class="flex items-start gap-4">
          <!-- Cookie Icon -->
          <div class="flex-shrink-0 hidden md:block">
            <div
              class="w-12 h-12 bg-accent-400/20 rounded-full flex items-center justify-center"
            >
              <svg
                class="w-6 h-6 text-accent-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h2
              id="cookie-banner-title"
              class="text-xl font-display font-bold text-smoke-50 mb-2"
            >
              Cookie-Einstellungen
            </h2>
            <p
              id="cookie-banner-description"
              class="text-smoke-300 text-sm mb-4"
            >
              Wir verwenden nur technisch notwendige Cookies für den Betrieb
              dieser Website. Ihre Privatsphäre ist uns wichtig – wir setzen
              keine Tracking-Cookies ein.
            </p>

            <!-- Settings Toggle (Mobile-friendly) -->
            {#if !showSettings}
              <button
                type="button"
                class="text-accent-400 hover:text-accent-300 text-sm font-medium mb-4 md:mb-0"
                onclick={toggleSettings}
              >
                Einstellungen anzeigen →
              </button>
            {/if}
          </div>
        </div>

        <!-- Settings Panel -->
        {#if showSettings}
          <div class="mt-6 pt-6 border-t border-smoke-700">
            <div class="space-y-4">
              <!-- Essential Cookies (always enabled) -->
              <div
                class="flex items-start justify-between p-4 bg-charcoal-700 rounded-lg"
              >
                <div class="flex-1 pr-4">
                  <h3 class="font-semibold text-smoke-50 mb-1">
                    Notwendige Cookies
                  </h3>
                  <p class="text-xs text-smoke-400">
                    Erforderlich für grundlegende Funktionen wie Navigation und
                    Sicherheit. Diese Cookies können nicht deaktiviert werden.
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <div
                    class="w-12 h-6 bg-accent-400 rounded-full flex items-center justify-end px-1"
                    role="switch"
                    aria-checked="true"
                    aria-label="Notwendige Cookies"
                  >
                    <div class="w-4 h-4 bg-white rounded-full"></div>
                  </div>
                  <span class="sr-only">Immer aktiv</span>
                </div>
              </div>

              <!-- Functional Cookies -->
              <div
                class="flex items-start justify-between p-4 bg-charcoal-700 rounded-lg"
              >
                <div class="flex-1 pr-4">
                  <h3 class="font-semibold text-smoke-50 mb-1">
                    Funktionale Cookies
                  </h3>
                  <p class="text-xs text-smoke-400">
                    Verbessern die Nutzererfahrung durch Speicherung von
                    Präferenzen wie Sprache oder Theme.
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <button
                    type="button"
                    class="w-12 h-6 rounded-full flex items-center px-1 transition-colors"
                    class:bg-accent-400={preferences.functional}
                    class:justify-end={preferences.functional}
                    class:bg-smoke-600={!preferences.functional}
                    class:justify-start={!preferences.functional}
                    onclick={() =>
                      (preferences.functional = !preferences.functional)}
                    role="switch"
                    aria-checked={preferences.functional}
                    aria-label="Funktionale Cookies"
                  >
                    <div class="w-4 h-4 bg-white rounded-full"></div>
                  </button>
                </div>
              </div>

              <!-- Analytics Cookies -->
              <div
                class="flex items-start justify-between p-4 bg-charcoal-700 rounded-lg"
              >
                <div class="flex-1 pr-4">
                  <h3 class="font-semibold text-smoke-50 mb-1">
                    Analyse-Cookies
                  </h3>
                  <p class="text-xs text-smoke-400">
                    Helfen uns zu verstehen, wie Besucher mit der Website
                    interagieren. Aktuell nicht verwendet.
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <button
                    type="button"
                    class="w-12 h-6 rounded-full flex items-center px-1 transition-colors"
                    class:bg-accent-400={preferences.analytics}
                    class:justify-end={preferences.analytics}
                    class:bg-smoke-600={!preferences.analytics}
                    class:justify-start={!preferences.analytics}
                    onclick={() =>
                      (preferences.analytics = !preferences.analytics)}
                    role="switch"
                    aria-checked={preferences.analytics}
                    aria-label="Analyse-Cookies"
                  >
                    <div class="w-4 h-4 bg-white rounded-full"></div>
                  </button>
                </div>
              </div>

              <!-- Marketing Cookies -->
              <div
                class="flex items-start justify-between p-4 bg-charcoal-700 rounded-lg"
              >
                <div class="flex-1 pr-4">
                  <h3 class="font-semibold text-smoke-50 mb-1">
                    Marketing-Cookies
                  </h3>
                  <p class="text-xs text-smoke-400">
                    Werden verwendet, um personalisierte Werbung anzuzeigen.
                    Aktuell nicht verwendet.
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <button
                    type="button"
                    class="w-12 h-6 rounded-full flex items-center px-1 transition-colors"
                    class:bg-accent-400={preferences.marketing}
                    class:justify-end={preferences.marketing}
                    class:bg-smoke-600={!preferences.marketing}
                    class:justify-start={!preferences.marketing}
                    onclick={() =>
                      (preferences.marketing = !preferences.marketing)}
                    role="switch"
                    aria-checked={preferences.marketing}
                    aria-label="Marketing-Cookies"
                  >
                    <div class="w-4 h-4 bg-white rounded-full"></div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Settings Actions -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3">
              <button
                type="button"
                class="btn bg-accent-400 hover:bg-accent-500 text-charcoal-900 font-semibold flex-1"
                onclick={handleSavePreferences}
              >
                Auswahl speichern
              </button>
              <button
                type="button"
                class="btn btn-outline flex-1"
                onclick={toggleSettings}
              >
                Schließen
              </button>
            </div>
          </div>
        {/if}

        <!-- Action Buttons (when settings not shown) -->
        {#if !showSettings}
          <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button
              type="button"
              class="btn bg-accent-400 hover:bg-accent-500 text-charcoal-900 font-semibold flex-1 sm:flex-initial"
              onclick={handleAcceptAll}
            >
              Alle akzeptieren
            </button>
            <button
              type="button"
              class="btn btn-outline flex-1 sm:flex-initial"
              onclick={handleAcceptEssential}
            >
              Nur notwendige
            </button>
            <a
              href="/privacy"
              use:link
              class="btn btn-outline flex-1 sm:flex-initial text-center"
            >
              Mehr erfahren
            </a>
          </div>
        {/if}
      </div>

      <!-- Legal Links -->
      <div class="bg-charcoal-900 px-6 md:px-8 py-4 border-t border-smoke-800">
        <div class="flex flex-wrap gap-4 justify-center text-xs text-smoke-500">
          <a
            href="/privacy"
            use:link
            class="hover:text-accent-400 transition-colors"
          >
            Datenschutzerklärung
          </a>
          <span aria-hidden="true">•</span>
          <a
            href="/imprint"
            use:link
            class="hover:text-accent-400 transition-colors"
          >
            Impressum
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

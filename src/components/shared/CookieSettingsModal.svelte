<script lang="ts">
  import { link } from "svelte-spa-router";
  import { fade, scale } from "svelte/transition";
  import { complianceStore } from "../../stores/compliance";

  // Reactive state from store
  $: showSettings = $complianceStore.showCookieSettings;
  $: currentConsent = $complianceStore.cookieConsent;

  // Local state for form
  let preferences = currentConsent.preferences;
  let statistics = currentConsent.statistics;
  let marketing = currentConsent.marketing;

  // Update local state when store changes
  $: if (showSettings) {
    preferences = currentConsent.preferences;
    statistics = currentConsent.statistics;
    marketing = currentConsent.marketing;
  }

  function handleSave() {
    complianceStore.savePreferences({
      preferences,
      statistics,
      marketing,
    });
  }

  function handleAcceptAll() {
    complianceStore.acceptAll();
  }

  function handleRejectAll() {
    complianceStore.rejectAll();
  }

  function handleClose() {
    complianceStore.closeSettings();
  }

  // Handle escape key
  function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") {
      handleClose();
    }
  }

  // Cookie category definitions
  const cookieCategories = [
    {
      id: "essential",
      name: "Essenzielle Cookies",
      description:
        "Diese Cookies sind für die Grundfunktionen der Website erforderlich und können nicht deaktiviert werden. Sie ermöglichen grundlegende Funktionen wie Seitennavigation, Sicherheit und Zugangssteuerung.",
      examples: [
        "Session-Cookies",
        "CSRF-Schutz",
        "Altersverifikation",
        "Cookie-Einstellungen",
      ],
      required: true,
    },
    {
      id: "preferences",
      name: "Präferenz-Cookies",
      description:
        "Diese Cookies ermöglichen es der Website, sich an Entscheidungen zu erinnern, die du getroffen hast (z.B. deine bevorzugte Sprache oder Region). Sie verbessern dein Nutzererlebnis.",
      examples: [
        "Theme-Einstellungen",
        "Sprachpräferenzen",
        "Layout-Vorlieben",
      ],
      required: false,
    },
    {
      id: "statistics",
      name: "Statistik-Cookies",
      description:
        "Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren, indem sie Informationen anonym sammeln und melden. Damit können wir unser Angebot verbessern.",
      examples: [
        "Google Analytics (geplant)",
        "Seitenaufrufe",
        "Besuchszeiten",
      ],
      required: false,
    },
    {
      id: "marketing",
      name: "Marketing-Cookies",
      description:
        "Diese Cookies werden verwendet, um Besuchern relevante Werbung anzuzeigen. Sie verfolgen die Besucher über Websites hinweg und sammeln Informationen für personalisierte Werbung.",
      examples: ["Derzeit keine in Verwendung"],
      required: false,
    },
  ];
</script>

<svelte:window on:keydown={handleKeydown} />

{#if showSettings}
  <!-- Backdrop -->
  <div
    class="fixed inset-0 bg-charcoal-950/80 z-50"
    transition:fade={{ duration: 200 }}
    on:click={handleClose}
    on:keydown={(e) => e.key === "Enter" && handleClose()}
    role="button"
    tabindex="-1"
    aria-label="Dialog schließen"
  ></div>

  <!-- Modal -->
  <div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none"
  >
    <div
      class="relative w-full max-w-2xl max-h-[90vh] overflow-hidden rounded-xl bg-charcoal-800 border border-charcoal-600 shadow-2xl pointer-events-auto flex flex-col"
      transition:scale={{ duration: 200, start: 0.95 }}
      role="dialog"
      aria-modal="true"
      aria-labelledby="cookie-settings-title"
    >
      <!-- Header -->
      <div
        class="flex items-center justify-between p-6 border-b border-charcoal-600 flex-shrink-0"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-full bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-xl" aria-hidden="true">⚙️</span>
          </div>
          <h2
            id="cookie-settings-title"
            class="text-xl font-display font-semibold text-smoke-50"
          >
            Cookie-Einstellungen
          </h2>
        </div>
        <button
          type="button"
          on:click={handleClose}
          class="p-2 rounded-lg text-smoke-400 hover:text-smoke-100 hover:bg-charcoal-700 focus:outline-none focus:ring-2 focus:ring-accent-400 transition-colors"
          aria-label="Dialog schließen"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <p class="text-smoke-300 text-sm mb-6">
          Hier kannst du deine Cookie-Präferenzen verwalten. Essenzielle Cookies
          sind notwendig für den Betrieb der Website und können nicht
          deaktiviert werden. Weitere Informationen findest du in unserer
          <a
            href="/cookies"
            use:link
            class="text-accent-400 hover:text-accent-300 underline underline-offset-2"
            on:click={handleClose}
          >
            Cookie-Richtlinie</a
          >.
        </p>

        <!-- Cookie Categories -->
        <div class="space-y-4">
          {#each cookieCategories as category (category.id)}
            <div
              class="rounded-lg border border-charcoal-600 bg-charcoal-700/50 overflow-hidden"
            >
              <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <h3 class="font-semibold text-smoke-50">
                        {category.name}
                      </h3>
                      {#if category.required}
                        <span
                          class="px-2 py-0.5 text-xs rounded-full bg-accent-400/20 text-accent-300"
                        >
                          Erforderlich
                        </span>
                      {/if}
                    </div>
                    <p class="text-smoke-400 text-sm leading-relaxed">
                      {category.description}
                    </p>
                    <div class="mt-2">
                      <span class="text-smoke-500 text-xs">Beispiele: </span>
                      <span class="text-smoke-400 text-xs">
                        {category.examples.join(", ")}
                      </span>
                    </div>
                  </div>

                  <!-- Toggle Switch -->
                  <div class="flex-shrink-0 pt-1">
                    {#if category.id === "essential"}
                      <div
                        class="w-12 h-6 rounded-full bg-accent-500 relative cursor-not-allowed opacity-75"
                        title="Kann nicht deaktiviert werden"
                      >
                        <div
                          class="absolute right-1 top-1 w-4 h-4 rounded-full bg-white shadow-sm"
                        ></div>
                      </div>
                    {:else if category.id === "preferences"}
                      <button
                        type="button"
                        role="switch"
                        aria-checked={preferences}
                        aria-label="Präferenz-Cookies {preferences
                          ? 'deaktivieren'
                          : 'aktivieren'}"
                        on:click={() => (preferences = !preferences)}
                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-700 {preferences
                          ? 'bg-accent-500'
                          : 'bg-charcoal-500'}"
                      >
                        <div
                          class="absolute top-1 w-4 h-4 rounded-full bg-white shadow-sm transition-all duration-200 {preferences
                            ? 'right-1'
                            : 'left-1'}"
                        ></div>
                      </button>
                    {:else if category.id === "statistics"}
                      <button
                        type="button"
                        role="switch"
                        aria-checked={statistics}
                        aria-label="Statistik-Cookies {statistics
                          ? 'deaktivieren'
                          : 'aktivieren'}"
                        on:click={() => (statistics = !statistics)}
                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-700 {statistics
                          ? 'bg-accent-500'
                          : 'bg-charcoal-500'}"
                      >
                        <div
                          class="absolute top-1 w-4 h-4 rounded-full bg-white shadow-sm transition-all duration-200 {statistics
                            ? 'right-1'
                            : 'left-1'}"
                        ></div>
                      </button>
                    {:else if category.id === "marketing"}
                      <button
                        type="button"
                        role="switch"
                        aria-checked={marketing}
                        aria-label="Marketing-Cookies {marketing
                          ? 'deaktivieren'
                          : 'aktivieren'}"
                        on:click={() => (marketing = !marketing)}
                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-700 {marketing
                          ? 'bg-accent-500'
                          : 'bg-charcoal-500'}"
                      >
                        <div
                          class="absolute top-1 w-4 h-4 rounded-full bg-white shadow-sm transition-all duration-200 {marketing
                            ? 'right-1'
                            : 'left-1'}"
                        ></div>
                      </button>
                    {/if}
                  </div>
                </div>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <!-- Footer -->
      <div
        class="flex flex-col sm:flex-row gap-3 p-6 border-t border-charcoal-600 bg-charcoal-800/80 flex-shrink-0"
      >
        <button
          type="button"
          on:click={handleRejectAll}
          class="flex-1 px-4 py-2.5 rounded-lg border border-charcoal-500 bg-charcoal-700 text-smoke-100 font-medium hover:bg-charcoal-600 hover:border-charcoal-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200 text-sm"
        >
          Alle Ablehnen
        </button>
        <button
          type="button"
          on:click={handleSave}
          class="flex-1 px-4 py-2.5 rounded-lg border border-accent-500 bg-transparent text-accent-400 font-medium hover:bg-accent-500/10 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200 text-sm"
        >
          Auswahl Speichern
        </button>
        <button
          type="button"
          on:click={handleAcceptAll}
          class="flex-1 px-4 py-2.5 rounded-lg bg-accent-500 text-charcoal-900 font-semibold hover:bg-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200 text-sm"
        >
          Alle Akzeptieren
        </button>
      </div>
    </div>
  </div>
{/if}

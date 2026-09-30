<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import { fade, scale } from "svelte/transition";
  import { complianceStore } from "../../stores/compliance";

  // Reactive state from store
  $: showModal = $complianceStore.showAgeVerification;

  function handleConfirm() {
    // Reset overflow before verification
    document.body.style.overflow = "";
    complianceStore.verifyAge();
  }

  function handleDecline() {
    complianceStore.declineAge();
  }

  // Prevent scrolling when modal is open
  onMount(() => {
    if ($complianceStore.showAgeVerification) {
      document.body.style.overflow = "hidden";
    }
  });

  onDestroy(() => {
    document.body.style.overflow = "";
  });

  // Also reset on reactive change
  $: if (typeof document !== "undefined" && !showModal) {
    document.body.style.overflow = "";
  }
</script>

{#if showModal}
  <!-- Full-screen backdrop - cannot be clicked away -->
  <div
    class="fixed inset-0 bg-charcoal-950 z-[100]"
    transition:fade={{ duration: 300 }}
    aria-hidden="true"
  ></div>

  <!-- Modal container - scrollable so the buttons stay reachable on small screens -->
  <div
    class="fixed inset-0 z-[100] overflow-y-auto overscroll-contain"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="age-verification-title"
    aria-describedby="age-verification-description"
  >
    <div class="flex min-h-full items-center justify-center p-4">
      <div
        class="relative w-full max-w-lg rounded-2xl bg-charcoal-800 border border-charcoal-600 shadow-2xl overflow-hidden"
        transition:scale={{ duration: 300, start: 0.9 }}
      >
        <!-- Warning stripe -->
        <div
          class="h-2 bg-gradient-to-r from-boundaries via-boundaries-light to-boundaries"
        ></div>

        <!-- Content -->
        <div class="p-5 sm:p-8 text-center">
          <!-- Icon -->
          <div class="mb-4 sm:mb-6">
            <div
              class="inline-flex items-center justify-center w-14 h-14 sm:w-20 sm:h-20 rounded-full bg-boundaries/20 border-2 border-boundaries"
            >
              <span class="text-3xl sm:text-4xl" aria-hidden="true">🔞</span>
            </div>
          </div>

          <!-- Title - Using h2 since pages have their own h1 -->
          <h2
            id="age-verification-title"
            class="text-xl sm:text-2xl md:text-3xl font-display font-bold text-smoke-50 mb-3 sm:mb-4"
          >
            Altersbeschränkter Inhalt
          </h2>

          <!-- Description -->
          <div
            id="age-verification-description"
            class="space-y-3 sm:space-y-4 mb-6 sm:mb-8"
          >
            <p class="text-smoke-300 leading-relaxed">
              Diese Website enthält Inhalte, die <strong class="text-smoke-100"
                >ausschließlich für Erwachsene</strong
              > bestimmt sind. Erotische Hypnose und verwandte Themen werden behandelt.
            </p>
            <p class="text-smoke-300 leading-relaxed">
              Du musst mindestens <strong class="text-smoke-100"
                >18 Jahre alt</strong
              >
              sein, um auf diese Inhalte zugreifen zu können.
            </p>
            <div
              class="p-4 rounded-lg bg-charcoal-700/50 border border-charcoal-600"
            >
              <p class="text-smoke-400 text-sm">
                Mit dem Betreten dieser Website bestätigst du, dass du
                volljährig bist und in deinem Land/deiner Region die
                gesetzlichen Voraussetzungen erfüllst, um solche Inhalte
                anzusehen.
              </p>
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <!-- Decline Button -->
            <button
              type="button"
              on:click={handleDecline}
              class="flex-1 px-6 py-4 rounded-xl border-2 border-charcoal-500 bg-charcoal-700 text-smoke-100 font-medium hover:bg-charcoal-600 hover:border-charcoal-400 focus:outline-none focus:ring-2 focus:ring-smoke-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200"
            >
              <span class="block text-lg">Nein, verlassen</span>
              <span class="block text-sm text-smoke-400 mt-1"
                >Ich bin unter 18</span
              >
            </button>

            <!-- Confirm Button -->
            <button
              type="button"
              on:click={handleConfirm}
              class="flex-1 px-6 py-4 rounded-xl bg-gradient-to-r from-accent-500 to-accent-400 text-charcoal-900 font-semibold hover:from-accent-400 hover:to-accent-300 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200 shadow-lg shadow-accent-500/25"
            >
              <span class="block text-lg">Ja, ich bin 18+</span>
              <span class="block text-sm text-charcoal-700 mt-1"
                >Seite betreten</span
              >
            </button>
          </div>

          <!-- Legal note -->
          <p class="mt-4 sm:mt-6 text-smoke-500 text-xs leading-relaxed">
            Deine Bestätigung wird als Cookie gespeichert, damit du bei deinem
            nächsten Besuch nicht erneut gefragt wirst. Bei Missbrauch behalten
            wir uns rechtliche Schritte vor.
          </p>
        </div>
      </div>
    </div>
  </div>
{/if}

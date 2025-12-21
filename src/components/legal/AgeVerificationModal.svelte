<script lang="ts">
  import { ageVerificationStore, showAgeVerification } from "../../stores/consent";

  let isClosing = false;

  function handleVerify() {
    isClosing = true;
    setTimeout(() => {
      ageVerificationStore.verify();
      showAgeVerification.set(false);
    }, 300); // Wait for animation
  }

  function handleDecline() {
    // Redirect to a safe external page
    window.location.href = "https://www.google.com";
  }
</script>

<!-- Backdrop -->
<div
  class="fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4"
  class:opacity-0={isClosing}
  class:transition-opacity={isClosing}
  class:duration-300={isClosing}
  role="dialog"
  aria-modal="true"
  aria-labelledby="age-verify-title"
>
  <!-- Modal -->
  <div
    class="bg-charcoal-800 border-2 border-accent-400 rounded-xl max-w-md w-full p-8 shadow-2xl"
    class:scale-95={isClosing}
    class:opacity-0={isClosing}
    class:transition-all={isClosing}
    class:duration-300={isClosing}
  >
    <!-- Icon -->
    <div class="flex justify-center mb-6">
      <div
        class="w-20 h-20 bg-accent-400/20 rounded-full flex items-center justify-center"
      >
        <svg
          class="w-10 h-10 text-accent-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
    </div>

    <!-- Title -->
    <h2
      id="age-verify-title"
      class="text-2xl font-display font-bold text-smoke-50 text-center mb-4"
    >
      Altersverifikation erforderlich
    </h2>

    <!-- Description -->
    <div class="space-y-4 mb-6 text-smoke-300 text-sm">
      <p class="text-center">
        Diese Website enthält Inhalte für Erwachsene, die sich mit <strong
          >erotischer Hypnose</strong
        > und verwandten Themen beschäftigen.
      </p>

      <div class="bg-caution/10 border border-caution/30 rounded-lg p-4">
        <p class="text-sm">
          <strong class="text-caution">Wichtiger Hinweis:</strong> Der Zugang ist
          nur für Personen gestattet, die mindestens 18 Jahre alt sind.
        </p>
      </div>

      <p class="text-center text-xs text-smoke-400">
        Durch die Bestätigung erklären Sie, dass Sie volljährig sind und die
        rechtlichen Voraussetzungen erfüllen, um auf diese Inhalte zuzugreifen.
      </p>
    </div>

    <!-- Age Verification Question -->
    <div class="bg-charcoal-700 rounded-lg p-4 mb-6">
      <p class="text-center font-semibold text-smoke-50 text-lg">
        Sind Sie mindestens 18 Jahre alt?
      </p>
    </div>

    <!-- Buttons -->
    <div class="grid grid-cols-2 gap-4">
      <button
        type="button"
        class="btn btn-outline border-smoke-600 hover:bg-smoke-800 text-smoke-300"
        onclick={handleDecline}
      >
        <span class="text-base">Nein</span>
      </button>
      <button
        type="button"
        class="btn bg-accent-400 hover:bg-accent-500 text-charcoal-900 font-semibold"
        onclick={handleVerify}
      >
        <span class="text-base">Ja, ich bin 18+</span>
      </button>
    </div>

    <!-- Legal Note -->
    <div class="mt-6 pt-6 border-t border-smoke-700">
      <p class="text-xs text-smoke-500 text-center">
        Mit der Bestätigung wird ein Cookie gesetzt, um diese Abfrage zu
        speichern. Weitere Informationen finden Sie in unserer
        <a href="/#/privacy" class="text-accent-400 hover:text-accent-300"
          >Datenschutzerklärung</a
        >.
      </p>
    </div>
  </div>
</div>

<style>
  /* Prevent scrolling when modal is open */
  :global(body:has(.age-verification-modal)) {
    overflow: hidden;
  }
</style>

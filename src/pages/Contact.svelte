<script lang="ts">
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";

  // Contact form data
  let formData = {
    name: "",
    email: "",
    subject: "",
    message: "",
    consent: false,
  };

  let formStatus = "idle"; // idle, submitting, success, error
  let formMessage = "";

  // Form submission handler
  async function handleSubmit(e: Event) {
    e.preventDefault();

    if (!formData.consent) {
      formStatus = "error";
      formMessage = "Bitte stimme der Datenverarbeitung zu.";
      return;
    }

    formStatus = "submitting";

    try {
      const response = await fetch("/api/contact", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      if (response.ok) {
        formStatus = "success";
        formMessage =
          "Deine Nachricht wurde erfolgreich gesendet. Wir melden uns bald bei dir!";

        // Reset form
        formData = {
          name: "",
          email: "",
          subject: "",
          message: "",
          consent: false,
        };
      } else {
        throw new Error("Server error");
      }
    } catch (error) {
      formStatus = "error";
      formMessage =
        "Es gab einen Fehler beim Senden deiner Nachricht. Bitte versuche es später erneut.";
      console.error("Contact form error:", error);
    }
  }

  // Clear status messages after some time
  let timeout: NodeJS.Timeout;

  const clearMessage = () => {
    if (formStatus === "success" || formStatus === "error") {
      timeout = setTimeout(() => {
        formStatus = "idle";
        formMessage = "";
      }, 5000);
    }
  };

  $: if (formStatus && formMessage) {
    clearTimeout(timeout);
    clearMessage();
  }

  onMount(() => {
    return () => clearTimeout(timeout);
  });
</script>

<svelte:head>
  <title>Kontakt - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Kontaktiere uns bei Fragen, Anregungen oder wenn du dich unserer Hypnose-Community anschließen möchtest."
  />
</svelte:head>

<main class="container mx-auto px-4 py-8 max-w-4xl">
  <!-- Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Kontakt
    </h1>
    <p class="text-xl text-smoke-300">
      Wir freuen uns auf deine Nachricht und deine Fragen
    </p>
  </header>

  <!-- Quick Contact Info -->
  <section class="mb-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card text-center">
        <div class="text-3xl mb-3">📧</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">E-Mail</h3>
        <a
          href="mailto:info@hypnose-stammtisch.de"
          class="text-accent-400 hover:text-accent-300 transition-colors"
        >
          info@hypnose-stammtisch.de
        </a>
      </div>

      <div class="card text-center">
        <div class="text-3xl mb-3">📅</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">Events</h3>
        <a
          href="/events"
          use:link
          class="text-accent-400 hover:text-accent-300 transition-colors"
        >
          Nächste Termine
        </a>
      </div>

      <div class="card text-center">
        <div class="text-3xl mb-3">💬</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">Community</h3>
        <a
          href="/code-of-conduct"
          use:link
          class="text-accent-400 hover:text-accent-300 transition-colors"
        >
          Verhaltenskodex
        </a>
      </div>
    </div>
  </section>

  <!-- Contact Form -->
  <section class="mb-12">
    <div class="max-w-2xl mx-auto">
      <h2
        class="text-2xl font-display font-semibold text-smoke-50 mb-6 text-center"
      >
        Schreib uns eine Nachricht
      </h2>

      <!-- Form Status Messages -->
      {#if formMessage}
        <div
          class="mb-6 p-4 rounded-lg border {formStatus === 'success'
            ? 'bg-consent/10 border-consent/30 text-consent'
            : 'bg-boundaries/10 border-boundaries/30 text-boundaries'}"
          role="alert"
          aria-live="polite"
        >
          {formMessage}
        </div>
      {/if}

      <form on:submit={handleSubmit} class="card space-y-6" novalidate>
        <!-- Name Field -->
        <div>
          <label
            for="contact-name"
            class="block text-sm font-medium text-smoke-300 mb-2"
          >
            Name <span class="text-boundaries">*</span>
          </label>
          <input
            id="contact-name"
            type="text"
            bind:value={formData.name}
            required
            class="w-full px-4 py-3 rounded-lg bg-charcoal-800 border border-smoke-600 text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent transition-colors"
            placeholder="Dein Name"
            aria-describedby="name-help"
          />
          <div id="name-help" class="text-xs text-smoke-400 mt-1">
            Wie sollen wir dich ansprechen?
          </div>
        </div>

        <!-- Email Field -->
        <div>
          <label
            for="contact-email"
            class="block text-sm font-medium text-smoke-300 mb-2"
          >
            E-Mail <span class="text-boundaries">*</span>
          </label>
          <input
            id="contact-email"
            type="email"
            bind:value={formData.email}
            required
            class="w-full px-4 py-3 rounded-lg bg-charcoal-800 border border-smoke-600 text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent transition-colors"
            placeholder="deine@email.de"
            aria-describedby="email-help"
          />
          <div id="email-help" class="text-xs text-smoke-400 mt-1">
            Deine E-Mail-Adresse für unsere Antwort
          </div>
        </div>

        <!-- Subject Field -->
        <div>
          <label
            for="contact-subject"
            class="block text-sm font-medium text-smoke-300 mb-2"
          >
            Betreff <span class="text-boundaries">*</span>
          </label>
          <select
            id="contact-subject"
            bind:value={formData.subject}
            required
            class="w-full px-4 py-3 rounded-lg bg-charcoal-800 border border-smoke-600 text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent transition-colors"
            aria-describedby="subject-help"
          >
            <option value="">Wähle einen Betreff</option>
            <option value="teilnahme">Teilnahme an Events</option>
            <option value="organisation">Event-Organisation</option>
            <option value="feedback">Feedback & Verbesserungen</option>
            <option value="partnership">Kooperationen & Partnerschaften</option>
            <option value="support">Technischer Support</option>
            <option value="conduct">Verhaltenskodex & Sicherheit</option>
            <option value="other">Sonstiges</option>
          </select>
          <div id="subject-help" class="text-xs text-smoke-400 mt-1">
            Hilf uns, deine Anfrage richtig zu kategorisieren
          </div>
        </div>

        <!-- Message Field -->
        <div>
          <label
            for="contact-message"
            class="block text-sm font-medium text-smoke-300 mb-2"
          >
            Nachricht <span class="text-boundaries">*</span>
          </label>
          <textarea
            id="contact-message"
            bind:value={formData.message}
            required
            rows="5"
            class="w-full px-4 py-3 rounded-lg bg-charcoal-800 border border-smoke-600 text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent transition-colors resize-y"
            placeholder="Teile uns deine Fragen, Anregungen oder Kommentare mit..."
            aria-describedby="message-help"
          ></textarea>
          <div id="message-help" class="text-xs text-smoke-400 mt-1">
            Minimum 10 Zeichen • Sei so detailliert wie nötig
          </div>
        </div>

        <!-- Consent Checkbox -->
        <div>
          <label class="flex items-start space-x-3 cursor-pointer">
            <input
              type="checkbox"
              bind:checked={formData.consent}
              required
              class="mt-1 w-4 h-4 rounded border-smoke-600 bg-charcoal-800 text-accent-400 focus:ring-accent-400 focus:ring-offset-0"
              aria-describedby="consent-help"
            />
            <span class="text-sm text-smoke-300">
              Ich stimme der Verarbeitung meiner Daten zur Bearbeitung meiner
              Anfrage zu.
              <a
                href="/privacy"
                use:link
                class="text-accent-400 hover:text-accent-300 underline"
              >
                Datenschutzrichtlinien
              </a>
              <span class="text-boundaries ml-1">*</span>
            </span>
          </label>
          <div id="consent-help" class="text-xs text-smoke-400 mt-2 ml-7">
            Deine Daten werden nur zur Bearbeitung deiner Anfrage verwendet und
            nicht an Dritte weitergegeben.
          </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
          <button
            type="submit"
            disabled={formStatus === "submitting"}
            class="w-full btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {#if formStatus === "submitting"}
              <span class="inline-flex items-center">
                <svg
                  class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                Wird gesendet...
              </span>
            {:else}
              Nachricht senden
            {/if}
          </button>
        </div>
      </form>
    </div>
  </section>

  <!-- Additional Contact Information -->
  <section class="mb-12">
    <h2
      class="text-2xl font-display font-semibold text-smoke-50 mb-6 text-center"
    >
      Weitere Kontaktmöglichkeiten
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Organization Team -->
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-4">
          Organisationsteam
        </h3>
        <div class="space-y-3 text-sm">
          <div>
            <div class="font-medium text-smoke-300">Event-Koordination</div>
            <a
              href="mailto:events@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              events@hypnose-stammtisch.de
            </a>
          </div>
          <div>
            <div class="font-medium text-smoke-300">Presseanfragen</div>
            <a
              href="mailto:presse@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              presse@hypnose-stammtisch.de
            </a>
          </div>
          <div>
            <div class="font-medium text-smoke-300">Partnerschaften</div>
            <a
              href="mailto:partner@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              partner@hypnose-stammtisch.de
            </a>
          </div>
        </div>
      </div>

      <!-- Support Contacts -->
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-4">
          Support & Hilfe
        </h3>
        <div class="space-y-3 text-sm">
          <div>
            <div class="font-medium text-smoke-300">Technischer Support</div>
            <a
              href="mailto:support@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              support@hypnose-stammtisch.de
            </a>
          </div>
          <div>
            <div class="font-medium text-smoke-300">Verhaltenskodex</div>
            <a
              href="mailto:conduct@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              conduct@hypnose-stammtisch.de
            </a>
          </div>
          <div>
            <div class="font-medium text-smoke-300">Datenschutz</div>
            <a
              href="mailto:privacy@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              privacy@hypnose-stammtisch.de
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Preview -->
  <section class="mb-12">
    <div class="card bg-accent-400/10 border-accent-400/30">
      <h2 class="text-xl font-semibold text-smoke-50 mb-4">Häufige Fragen</h2>
      <p class="text-smoke-300 mb-4">
        Bevor du uns schreibst, schau gerne in unsere häufig gestellten Fragen.
        Vielleicht findest du dort bereits eine Antwort.
      </p>
      <div class="flex flex-wrap gap-3">
        <a href="/resources#faq" use:link class="btn btn-outline btn-sm">
          FAQ besuchen
        </a>
        <a href="/resources#safety" use:link class="btn btn-outline btn-sm">
          Sicherheitsleitfaden
        </a>
        <a href="/code-of-conduct" use:link class="btn btn-outline btn-sm">
          Verhaltenskodex
        </a>
      </div>
    </div>
  </section>

  <!-- Response Time -->
  <section class="text-center">
    <div class="inline-flex items-center space-x-2 text-smoke-400 text-sm">
      <svg
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        ></path>
      </svg>
      <span>Antwortzeit: Normalerweise innerhalb von 24-48 Stunden</span>
    </div>
  </section>
</main>

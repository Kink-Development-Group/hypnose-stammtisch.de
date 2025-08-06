<script lang="ts">
  import { z } from "zod";

  // Form validation schema
  const contactSchema = z.object({
    name: z
      .string()
      .min(2, "Name muss mindestens 2 Zeichen lang sein")
      .max(255, "Name ist zu lang"),
    email: z.string().email("Ungültige E-Mail-Adresse"),
    subject: z.enum(
      [
        "teilnahme",
        "organisation",
        "feedback",
        "partnership",
        "support",
        "conduct",
        "other",
      ],
      {
        message: "Bitte wählen Sie ein Thema",
      },
    ),
    message: z
      .string()
      .min(10, "Nachricht muss mindestens 10 Zeichen lang sein")
      .max(5000, "Nachricht ist zu lang"),
    consent_privacy: z
      .boolean()
      .refine(
        (val) => val === true,
        "Datenschutzerklärung muss akzeptiert werden",
      ),
  });

  type ContactData = z.infer<typeof contactSchema>;

  // Form state
  let formData: ContactData = {
    name: "",
    email: "",
    subject: "teilnahme",
    message: "",
    consent_privacy: false,
  };

  let errors: Record<string, string> = {};
  let isSubmitting = false;
  let submitSuccess = false;
  let submitError = "";
  let honeypot = ""; // Spam protection
  let timestamp = Math.floor(Date.now() / 1000); // Form load timestamp

  // Subject options
  const subjectOptions = [
    { value: "teilnahme", label: "Teilnahme an Events" },
    { value: "organisation", label: "Event organisieren" },
    { value: "feedback", label: "Feedback & Verbesserungsvorschläge" },
    { value: "partnership", label: "Partnerschaft & Kooperation" },
    { value: "support", label: "Technischer Support" },
    { value: "conduct", label: "Verhaltenskodex / Beschwerden" },
    { value: "other", label: "Sonstiges" },
  ];

  // Form submission
  const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (isSubmitting) return;

    // Reset previous state
    errors = {};
    submitError = "";
    isSubmitting = true;

    try {
      // Client-side validation
      const validation = contactSchema.safeParse(formData);

      if (!validation.success) {
        const fieldErrors: Record<string, string> = {};
        validation.error.issues.forEach((issue) => {
          const field = issue.path[0] as string;
          if (!fieldErrors[field]) {
            fieldErrors[field] = issue.message;
          }
        });
        errors = fieldErrors;
        isSubmitting = false;
        return;
      }

      // Prepare submission data
      const submissionData = {
        ...validation.data,
        honeypot, // Should be empty
        timestamp, // Form load time
      };

      // Submit to backend
      const response = await fetch("/api/forms/contact", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(submissionData),
      });

      const result = await response.json();

      if (response.ok && result.success) {
        submitSuccess = true;
        // Reset form
        formData = {
          name: "",
          email: "",
          subject: "teilnahme",
          message: "",
          consent_privacy: false,
        };

        // Scroll to success message
        document
          .getElementById("contact-success-message")
          ?.scrollIntoView({ behavior: "smooth" });
      } else {
        if (result.errors && Array.isArray(result.errors)) {
          // Handle field-specific errors
          const fieldErrors: Record<string, string> = {};
          result.errors.forEach((error: string) => {
            // Simple error mapping
            if (error.includes("email") || error.includes("E-Mail")) {
              fieldErrors.email = error;
            } else if (error.includes("name") || error.includes("Name")) {
              fieldErrors.name = error;
            } else if (
              error.includes("message") ||
              error.includes("Nachricht")
            ) {
              fieldErrors.message = error;
            } else {
              // General error
              submitError = error;
            }
          });
          errors = fieldErrors;
        } else {
          submitError =
            result.error || "Unbekannter Fehler beim Senden der Nachricht";
        }
      }
    } catch (error) {
      console.error("Contact form submission error:", error);
      submitError = "Netzwerkfehler. Bitte versuchen Sie es erneut.";
    } finally {
      isSubmitting = false;
    }
  };

  // Character counter for message
  $: messageLength = formData.message.length;
  $: messageCounterClass =
    messageLength > 4500
      ? "text-caution"
      : messageLength > 4800
        ? "text-boundaries"
        : "text-smoke-400";
</script>

<!-- Success Message -->
{#if submitSuccess}
  <div
    id="contact-success-message"
    class="mb-8 p-6 bg-consent/10 border border-consent rounded-lg"
    role="alert"
    aria-live="polite"
  >
    <div class="flex items-start">
      <svg
        class="w-5 h-5 text-consent mt-0.5 mr-3 flex-shrink-0"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
          clip-rule="evenodd"
        />
      </svg>
      <div>
        <h3 class="text-lg font-semibold text-consent mb-2">
          Nachricht erfolgreich gesendet!
        </h3>
        <p class="text-smoke-300">
          Vielen Dank für Ihre Nachricht. Wir werden uns zeitnah bei Ihnen
          melden.
        </p>
      </div>
    </div>
  </div>
{/if}

<form on:submit={handleSubmit} class="space-y-8" novalidate>
  <!-- Honeypot field for spam protection -->
  <input
    type="text"
    bind:value={honeypot}
    style="display: none;"
    tabindex="-1"
    autocomplete="off"
    aria-hidden="true"
  />

  <!-- Contact Information -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Kontaktinformationen
    </h2>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Name -->
      <div>
        <label
          for="contact-name"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Ihr Name <span class="text-boundaries" aria-label="Pflichtfeld"
            >*</span
          >
        </label>
        <input
          type="text"
          id="contact-name"
          bind:value={formData.name}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Vor- und Nachname"
          required
          autocomplete="name"
          aria-describedby={errors.name ? "name-error" : undefined}
          aria-invalid={errors.name ? "true" : "false"}
        />
        {#if errors.name}
          <p id="name-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.name}
          </p>
        {/if}
      </div>

      <!-- Email -->
      <div>
        <label
          for="contact-email"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          E-Mail-Adresse <span class="text-boundaries" aria-label="Pflichtfeld"
            >*</span
          >
        </label>
        <input
          type="email"
          id="contact-email"
          bind:value={formData.email}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="ihre.email@beispiel.de"
          required
          autocomplete="email"
          aria-describedby={errors.email ? "email-error" : "email-help"}
          aria-invalid={errors.email ? "true" : "false"}
        />
        <p id="email-help" class="mt-2 text-sm text-smoke-400">
          Wir verwenden Ihre E-Mail-Adresse nur für die Antwort auf Ihre
          Anfrage.
        </p>
        {#if errors.email}
          <p id="email-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.email}
          </p>
        {/if}
      </div>
    </div>
  </section>

  <!-- Message Content -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Ihre Nachricht
    </h2>

    <!-- Subject -->
    <div class="mb-6">
      <label
        for="contact-subject"
        class="block text-sm font-medium text-smoke-100 mb-2"
      >
        Thema <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
      </label>
      <select
        id="contact-subject"
        bind:value={formData.subject}
        class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
        required
        aria-describedby={errors.subject ? "subject-error" : undefined}
        aria-invalid={errors.subject ? "true" : "false"}
      >
        {#each subjectOptions as option}
          <option value={option.value}>{option.label}</option>
        {/each}
      </select>
      {#if errors.subject}
        <p id="subject-error" class="mt-2 text-sm text-boundaries" role="alert">
          {errors.subject}
        </p>
      {/if}
    </div>

    <!-- Message -->
    <div>
      <label
        for="contact-message"
        class="block text-sm font-medium text-smoke-100 mb-2"
      >
        Nachricht <span class="text-boundaries" aria-label="Pflichtfeld">*</span
        >
      </label>
      <textarea
        id="contact-message"
        bind:value={formData.message}
        rows="8"
        class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
        placeholder="Beschreiben Sie Ihr Anliegen so detailliert wie möglich..."
        required
        aria-describedby={errors.message ? "message-error" : "message-help"}
        aria-invalid={errors.message ? "true" : "false"}
      ></textarea>

      <div class="mt-2 flex items-center justify-between">
        <p id="message-help" class="text-sm text-smoke-400">
          Minimum 10 Zeichen, Maximum 5.000 Zeichen
        </p>
        <p class="text-sm {messageCounterClass}">
          {messageLength} / 5.000
        </p>
      </div>

      {#if errors.message}
        <p id="message-error" class="mt-2 text-sm text-boundaries" role="alert">
          {errors.message}
        </p>
      {/if}
    </div>
  </section>

  <!-- Privacy Consent -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Datenschutz
    </h2>

    <div class="space-y-4">
      <label class="flex items-start">
        <input
          type="checkbox"
          bind:checked={formData.consent_privacy}
          required
          class="mt-1 mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 rounded focus:ring-accent-400 focus:ring-2"
          aria-describedby={errors.consent_privacy
            ? "privacy-error"
            : undefined}
          aria-invalid={errors.consent_privacy ? "true" : "false"}
        />
        <span class="text-sm text-smoke-100">
          Ich habe die <a
            href="/privacy"
            class="text-accent-400 hover:text-accent-300 underline"
            target="_blank"
            rel="noopener">Datenschutzerklärung</a
          >
          gelesen und stimme der Verarbeitung meiner Daten zum Zweck der
          Bearbeitung meiner Anfrage zu.
          <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
        </span>
      </label>
      {#if errors.consent_privacy}
        <p id="privacy-error" class="text-sm text-boundaries" role="alert">
          {errors.consent_privacy}
        </p>
      {/if}
    </div>
  </section>

  <!-- Submit Button and Errors -->
  <section>
    {#if submitError}
      <div
        class="mb-6 p-4 bg-boundaries/10 border border-boundaries rounded-lg"
        role="alert"
        aria-live="polite"
      >
        <div class="flex items-start">
          <svg
            class="w-5 h-5 text-boundaries mt-0.5 mr-3 flex-shrink-0"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          <p class="text-boundaries">{submitError}</p>
        </div>
      </div>
    {/if}

    <div class="flex items-center justify-end space-x-4">
      <p class="text-sm text-smoke-400">
        <span class="text-boundaries">*</span> Pflichtfelder
      </p>

      <button
        type="submit"
        disabled={isSubmitting}
        class="px-8 py-3 bg-accent-600 hover:bg-accent-700 disabled:bg-charcoal-600 disabled:cursor-not-allowed text-charcoal-900 disabled:text-smoke-400 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        aria-describedby="submit-help"
      >
        {#if isSubmitting}
          <span class="flex items-center">
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5"
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

    <p id="submit-help" class="mt-3 text-sm text-smoke-400">
      Sie erhalten eine Bestätigung per E-Mail und wir melden uns zeitnah bei
      Ihnen.
    </p>
  </section>
</form>

<style>
  /* Ensure proper focus styles for accessibility */
  input:focus,
  textarea:focus,
  select:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
  }

  /* Custom checkbox styling for better accessibility */
  input[type="checkbox"]:focus {
    box-shadow: 0 0 0 2px rgba(65, 242, 192, 0.5);
  }
</style>

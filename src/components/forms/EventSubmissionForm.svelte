<script lang="ts">
  import dayjs from "dayjs";
  import timezone from "dayjs/plugin/timezone";
  import utc from "dayjs/plugin/utc";
  import { onMount } from "svelte";
  import { z } from "zod";
  import InvisibleCaptcha from "../shared/InvisibleCaptcha.svelte";
  import MarkdownEditor from "../shared/MarkdownEditor.svelte";

  // CAPTCHA component reference
  let captchaComponent: InvisibleCaptcha;

  dayjs.extend(utc);
  dayjs.extend(timezone);

  // Form validation schema
  const eventSubmissionSchema = z.object({
    title: z
      .string()
      .min(3, "Titel muss mindestens 3 Zeichen lang sein")
      .max(255, "Titel ist zu lang"),
    description: z
      .string()
      .min(20, "Beschreibung muss mindestens 20 Zeichen lang sein")
      .max(2000, "Beschreibung ist zu lang"),
    start_datetime: z.string().min(1, "Startdatum ist erforderlich"),
    end_datetime: z.string().min(1, "Enddatum ist erforderlich"),
    timezone: z.string().default("Europe/Berlin"),
    is_all_day: z.boolean().default(false),
    is_recurring: z.boolean().default(false),
    rrule: z.string().optional(),
    location_type: z.enum(["physical", "online", "hybrid"], {
      message: "Bitte wählen Sie einen Veranstaltungsort",
    }),
    location_name: z.string().optional(),
    location_address: z.string().optional(),
    location_url: z.string().url("Ungültige URL").optional().or(z.literal("")),
    location_instructions: z.string().optional(),
    category: z
      .enum(["workshop", "stammtisch", "practice", "lecture", "special"])
      .default("stammtisch"),
    difficulty_level: z
      .enum(["beginner", "intermediate", "advanced", "all"])
      .default("all"),
    max_participants: z
      .number()
      .positive("Anzahl muss positiv sein")
      .optional(),
    requirements: z.string().optional(),
    safety_notes: z.string().optional(),
    preparation_notes: z.string().optional(),
    organizer_name: z.string().min(2, "Name ist erforderlich").max(255),
    organizer_email: z.string().email("Ungültige E-Mail-Adresse"),
    organizer_bio: z.string().optional(),
    tags: z.array(z.string()).default([]),
    consent_data_processing: z
      .boolean()
      .refine(
        (val) => val === true,
        "Datenschutzerklärung muss akzeptiert werden",
      ),
    accepted_code_of_conduct: z
      .boolean()
      .refine((val) => val === true, "Verhaltenskodex muss akzeptiert werden"),
  });

  type EventSubmissionData = z.infer<typeof eventSubmissionSchema>;

  // Form state
  let formData: EventSubmissionData = {
    title: "",
    description: "",
    start_datetime: "",
    end_datetime: "",
    timezone: "Europe/Berlin",
    is_all_day: false,
    is_recurring: false,
    rrule: "",
    location_type: "physical",
    location_name: "",
    location_address: "",
    location_url: "",
    location_instructions: "",
    category: "stammtisch",
    difficulty_level: "all",
    max_participants: undefined,
    requirements: "",
    safety_notes: "",
    preparation_notes: "",
    organizer_name: "",
    organizer_email: "",
    organizer_bio: "",
    tags: [],
    consent_data_processing: false,
    accepted_code_of_conduct: false,
  };

  let errors: Record<string, string> = {};
  let isSubmitting = false;
  let submitSuccess = false;
  let submitError = "";
  let showRRuleHelp = false;
  let honeypot = ""; // Spam protection
  let timestamp = Math.floor(Date.now() / 1000); // Form load timestamp

  // Available tags
  const availableTags = [
    "Anfängerfreundlich",
    "Fortgeschritten",
    "Workshop",
    "Praxis",
    "Theorie",
    "Gruppenhypnose",
    "Selbsthypnose",
    "Entspannung",
    "Persönlichkeitsentwicklung",
    "Therapie",
    "Meditation",
    "BDSM",
    "Fetisch",
    "Erotik",
  ];

  // Reactive validations
  $: {
    if (formData.location_type === "physical") {
      formData.location_url = "";
    }
    if (formData.location_type === "online") {
      formData.location_address = "";
    }
  }

  $: isValidDateTime =
    formData.start_datetime &&
    formData.end_datetime &&
    dayjs(formData.end_datetime).isAfter(dayjs(formData.start_datetime));

  onMount(() => {
    // Set default start time to next hour
    const nextHour = dayjs().add(1, "hour").startOf("hour");
    formData.start_datetime = nextHour.format("YYYY-MM-DDTHH:mm");
    formData.end_datetime = nextHour.add(2, "hours").format("YYYY-MM-DDTHH:mm");
  });

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
      const validation = eventSubmissionSchema.safeParse(formData);

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

      // Execute CAPTCHA and get token
      let captchaToken = "";
      try {
        captchaToken = await captchaComponent.execute();
      } catch (captchaError) {
        console.error("CAPTCHA error:", captchaError);
        submitError =
          "CAPTCHA-Überprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.";
        isSubmitting = false;
        return;
      }

      // Prepare submission data
      const submissionData = {
        ...validation.data,
        honeypot, // Should be empty
        timestamp, // Form load time
        captcha_token: captchaToken, // CAPTCHA token
        tags: formData.tags.filter((tag) => tag.trim() !== ""),
      };

      // Submit to backend
      const response = await fetch("/api/forms/submit-event", {
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
          title: "",
          description: "",
          start_datetime: "",
          end_datetime: "",
          timezone: "Europe/Berlin",
          is_all_day: false,
          is_recurring: false,
          rrule: "",
          location_type: "physical",
          location_name: "",
          location_address: "",
          location_url: "",
          location_instructions: "",
          category: "stammtisch",
          difficulty_level: "all",
          max_participants: undefined,
          requirements: "",
          safety_notes: "",
          preparation_notes: "",
          organizer_name: "",
          organizer_email: "",
          organizer_bio: "",
          tags: [],
          consent_data_processing: false,
          accepted_code_of_conduct: false,
        };

        // Scroll to success message
        document
          .getElementById("success-message")
          ?.scrollIntoView({ behavior: "smooth" });
      } else {
        if (result.errors && Array.isArray(result.errors)) {
          // Handle field-specific errors
          const fieldErrors: Record<string, string> = {};
          result.errors.forEach((error: string) => {
            // Simple error mapping - could be more sophisticated
            if (error.includes("title") || error.includes("Titel")) {
              fieldErrors.title = error;
            } else if (error.includes("email") || error.includes("E-Mail")) {
              fieldErrors.organizer_email = error;
            } else {
              // General error
              submitError = error;
            }
          });
          errors = fieldErrors;
        } else {
          submitError =
            result.error || "Unbekannter Fehler beim Senden des Formulars";
        }
      }
    } catch (error) {
      console.error("Form submission error:", error);
      submitError = "Netzwerkfehler. Bitte versuchen Sie es erneut.";
    } finally {
      isSubmitting = false;
    }
  };

  // Add tag
  const addTag = (tag: string) => {
    if (!formData.tags.includes(tag)) {
      formData.tags = [...formData.tags, tag];
    }
  };

  // Remove tag
  const removeTag = (tagToRemove: string) => {
    formData.tags = formData.tags.filter((tag) => tag !== tagToRemove);
  };

  // RRULE helper
  const generateRRule = () => {
    // Simple RRULE generator - could be expanded
    const freq =
      (document.getElementById("rrule-freq") as HTMLSelectElement)?.value ||
      "WEEKLY";
    const interval =
      (document.getElementById("rrule-interval") as HTMLInputElement)?.value ||
      "1";
    const count = (document.getElementById("rrule-count") as HTMLInputElement)
      ?.value;

    let rrule = `FREQ=${freq};INTERVAL=${interval}`;
    if (count) {
      rrule += `;COUNT=${count}`;
    }

    formData.rrule = rrule;
    showRRuleHelp = false;
  };
</script>

<!-- Success Message -->
{#if submitSuccess}
  <div
    id="success-message"
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
          Event erfolgreich eingereicht!
        </h3>
        <p class="text-smoke-300">
          Vielen Dank für Ihre Einreichung. Wir werden Ihr Event zeitnah prüfen
          und Sie per E-Mail über die Freischaltung informieren.
        </p>
      </div>
    </div>
  </div>
{/if}

<form on:submit={handleSubmit} class="space-y-8" novalidate>
  <!-- Invisible CAPTCHA for spam protection -->
  <InvisibleCaptcha bind:this={captchaComponent} action="event_submission" />

  <!-- Honeypot field for spam protection -->
  <input
    type="text"
    bind:value={honeypot}
    style="display: none;"
    tabindex="-1"
    autocomplete="off"
    aria-hidden="true"
  />

  <!-- Basic Event Information -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Event-Informationen
    </h2>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Title -->
      <div class="md:col-span-2">
        <label
          for="title"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Event-Titel <span class="text-boundaries" aria-label="Pflichtfeld"
            >*</span
          >
        </label>
        <input
          type="text"
          id="title"
          bind:value={formData.title}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="z.B. Hypnose-Stammtisch für Einsteiger"
          required
          aria-describedby={errors.title ? "title-error" : undefined}
          aria-invalid={errors.title ? "true" : "false"}
        />
        {#if errors.title}
          <p id="title-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.title}
          </p>
        {/if}
      </div>

      <!-- Description -->
      <div class="md:col-span-2">
        <MarkdownEditor
          id="description"
          label="Beschreibung"
          bind:value={formData.description}
          rows={4}
          placeholder="Beschreiben Sie Ihr Event..."
          required={true}
          error={errors.description || ""}
          maxLength={2000}
          theme="dark"
        />
      </div>

      <!-- Category -->
      <div>
        <label
          for="category"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Kategorie
        </label>
        <select
          id="category"
          bind:value={formData.category}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
        >
          <option value="stammtisch">Stammtisch</option>
          <option value="workshop">Workshop</option>
          <option value="practice">Praxis-Session</option>
          <option value="lecture">Vortrag</option>
          <option value="special">Besondere Veranstaltung</option>
        </select>
      </div>

      <!-- Difficulty Level -->
      <div>
        <label
          for="difficulty_level"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Schwierigkeitsgrad
        </label>
        <select
          id="difficulty_level"
          bind:value={formData.difficulty_level}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
        >
          <option value="all">Für alle geeignet</option>
          <option value="beginner">Anfänger</option>
          <option value="intermediate">Fortgeschritten</option>
          <option value="advanced">Experten</option>
        </select>
      </div>
    </div>
  </section>

  <!-- Date and Time -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Datum und Uhrzeit
    </h2>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Start Date/Time -->
      <div>
        <label
          for="start_datetime"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Beginn <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
        </label>
        <input
          type="datetime-local"
          id="start_datetime"
          bind:value={formData.start_datetime}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          required
          aria-describedby={errors.start_datetime ? "start-error" : undefined}
          aria-invalid={errors.start_datetime ? "true" : "false"}
        />
        {#if errors.start_datetime}
          <p id="start-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.start_datetime}
          </p>
        {/if}
      </div>

      <!-- End Date/Time -->
      <div>
        <label
          for="end_datetime"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Ende <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
        </label>
        <input
          type="datetime-local"
          id="end_datetime"
          bind:value={formData.end_datetime}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          required
          aria-describedby={errors.end_datetime ? "end-error" : undefined}
          aria-invalid={errors.end_datetime ? "true" : "false"}
        />
        {#if errors.end_datetime}
          <p id="end-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.end_datetime}
          </p>
        {/if}
      </div>

      <!-- All Day Event -->
      <div class="md:col-span-2">
        <label class="flex items-center">
          <input
            type="checkbox"
            bind:checked={formData.is_all_day}
            class="mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 rounded focus:ring-accent-400 focus:ring-2"
          />
          <span class="text-sm text-smoke-100">Ganztägiges Event</span>
        </label>
      </div>

      <!-- Recurring Event -->
      <div class="md:col-span-2">
        <label class="flex items-center">
          <input
            type="checkbox"
            bind:checked={formData.is_recurring}
            class="mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 rounded focus:ring-accent-400 focus:ring-2"
          />
          <span class="text-sm text-smoke-100">Wiederkehrendes Event</span>
        </label>

        {#if formData.is_recurring}
          <div class="mt-4 p-4 bg-charcoal-800 rounded-lg">
            <div class="flex items-center justify-between mb-3">
              <label
                for="rrule"
                class="block text-sm font-medium text-smoke-100"
              >
                Wiederholungsregel (RRULE)
              </label>
              <button
                type="button"
                on:click={() => (showRRuleHelp = !showRRuleHelp)}
                class="text-accent-400 hover:text-accent-300 text-sm underline"
              >
                Hilfe
              </button>
            </div>

            <input
              type="text"
              id="rrule"
              bind:value={formData.rrule}
              class="w-full px-3 py-2 bg-charcoal-700 border border-charcoal-600 rounded text-smoke-50 placeholder-smoke-400 text-sm focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
              placeholder="z.B. FREQ=WEEKLY;INTERVAL=1;BYDAY=TU"
            />

            {#if showRRuleHelp}
              <div
                class="mt-3 p-3 bg-charcoal-700 rounded text-sm text-smoke-300"
              >
                <p class="font-medium mb-2">RRULE Beispiele:</p>
                <ul class="space-y-1 text-xs">
                  <li><code>FREQ=WEEKLY</code> - Wöchentlich</li>
                  <li>
                    <code>FREQ=WEEKLY;INTERVAL=2</code> - Alle zwei Wochen
                  </li>
                  <li><code>FREQ=WEEKLY;BYDAY=TU</code> - Jeden Dienstag</li>
                  <li>
                    <code>FREQ=MONTHLY;BYMONTHDAY=15</code> - Jeden 15. des Monats
                  </li>
                </ul>
                <div class="mt-3 pt-3 border-t border-charcoal-600">
                  <p class="font-medium mb-2">Schnell-Generator:</p>
                  <div class="grid grid-cols-3 gap-2">
                    <select
                      id="rrule-freq"
                      class="px-2 py-1 bg-charcoal-800 border border-charcoal-600 rounded text-xs"
                    >
                      <option value="WEEKLY">Wöchentlich</option>
                      <option value="MONTHLY">Monatlich</option>
                      <option value="YEARLY">Jährlich</option>
                    </select>
                    <input
                      type="number"
                      id="rrule-interval"
                      value="1"
                      min="1"
                      max="52"
                      class="px-2 py-1 bg-charcoal-800 border border-charcoal-600 rounded text-xs"
                      placeholder="Interval"
                    />
                    <input
                      type="number"
                      id="rrule-count"
                      min="1"
                      max="100"
                      class="px-2 py-1 bg-charcoal-800 border border-charcoal-600 rounded text-xs"
                      placeholder="Anzahl"
                    />
                  </div>
                  <button
                    type="button"
                    on:click={generateRRule}
                    class="mt-2 px-3 py-1 bg-accent-600 hover:bg-accent-700 text-charcoal-900 text-xs rounded"
                  >
                    Generieren
                  </button>
                </div>
              </div>
            {/if}
          </div>
        {/if}
      </div>
    </div>

    {#if !isValidDateTime && formData.start_datetime && formData.end_datetime}
      <p class="mt-3 text-sm text-boundaries" role="alert">
        Das Ende muss nach dem Beginn liegen.
      </p>
    {/if}
  </section>

  <!-- Location -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Veranstaltungsort
    </h2>

    <!-- Location Type -->
    <div class="mb-6">
      <fieldset>
        <legend class="text-sm font-medium text-smoke-100 mb-3">
          Veranstaltungstyp <span
            class="text-boundaries"
            aria-label="Pflichtfeld">*</span
          >
        </legend>
        <div class="grid gap-4 md:grid-cols-3">
          <label
            class="flex items-center p-4 bg-charcoal-800 border border-charcoal-600 rounded-lg cursor-pointer hover:border-accent-400 transition-colors"
          >
            <input
              type="radio"
              bind:group={formData.location_type}
              value="physical"
              class="mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 focus:ring-accent-400 focus:ring-2"
              required
            />
            <div>
              <div class="font-medium text-smoke-50">Vor Ort</div>
              <div class="text-sm text-smoke-400">Physische Veranstaltung</div>
            </div>
          </label>

          <label
            class="flex items-center p-4 bg-charcoal-800 border border-charcoal-600 rounded-lg cursor-pointer hover:border-accent-400 transition-colors"
          >
            <input
              type="radio"
              bind:group={formData.location_type}
              value="online"
              class="mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 focus:ring-accent-400 focus:ring-2"
              required
            />
            <div>
              <div class="font-medium text-smoke-50">Online</div>
              <div class="text-sm text-smoke-400">Video-Konferenz</div>
            </div>
          </label>

          <label
            class="flex items-center p-4 bg-charcoal-800 border border-charcoal-600 rounded-lg cursor-pointer hover:border-accent-400 transition-colors"
          >
            <input
              type="radio"
              bind:group={formData.location_type}
              value="hybrid"
              class="mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 focus:ring-accent-400 focus:ring-2"
              required
            />
            <div>
              <div class="font-medium text-smoke-50">Hybrid</div>
              <div class="text-sm text-smoke-400">Online & vor Ort</div>
            </div>
          </label>
        </div>
      </fieldset>
      {#if errors.location_type}
        <p class="mt-2 text-sm text-boundaries" role="alert">
          {errors.location_type}
        </p>
      {/if}
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Location Name -->
      <div class="md:col-span-2">
        <label
          for="location_name"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Name des Veranstaltungsortes
        </label>
        <input
          type="text"
          id="location_name"
          bind:value={formData.location_name}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="z.B. Community-Center, Hotel Beispiel, etc."
        />
      </div>

      <!-- Physical Address -->
      {#if formData.location_type === "physical" || formData.location_type === "hybrid"}
        <div class="md:col-span-2">
          <label
            for="location_address"
            class="block text-sm font-medium text-smoke-100 mb-2"
          >
            Adresse {formData.location_type === "physical"
              ? "(erforderlich)"
              : "(optional)"}
            {#if formData.location_type === "physical"}
              <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
            {/if}
          </label>
          <textarea
            id="location_address"
            bind:value={formData.location_address}
            rows="3"
            class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
            placeholder="Straße, Hausnummer&#10;PLZ Ort&#10;ggf. weitere Angaben"
            required={formData.location_type === "physical"}
            aria-describedby={errors.location_address
              ? "address-error"
              : undefined}
            aria-invalid={errors.location_address ? "true" : "false"}
          ></textarea>
          {#if errors.location_address}
            <p
              id="address-error"
              class="mt-2 text-sm text-boundaries"
              role="alert"
            >
              {errors.location_address}
            </p>
          {/if}
        </div>
      {/if}

      <!-- Online URL -->
      {#if formData.location_type === "online" || formData.location_type === "hybrid"}
        <div class="md:col-span-2">
          <label
            for="location_url"
            class="block text-sm font-medium text-smoke-100 mb-2"
          >
            Online-Link {formData.location_type === "online"
              ? "(erforderlich)"
              : "(optional)"}
            {#if formData.location_type === "online"}
              <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
            {/if}
          </label>
          <input
            type="url"
            id="location_url"
            bind:value={formData.location_url}
            class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
            placeholder="https://meet.example.com/raum123 oder Zoom-Link"
            required={formData.location_type === "online"}
            aria-describedby={errors.location_url ? "url-error" : "url-help"}
            aria-invalid={errors.location_url ? "true" : "false"}
          />
          <p id="url-help" class="mt-2 text-sm text-smoke-400">
            Der Link wird erst kurz vor der Veranstaltung an die Teilnehmer
            gesendet.
          </p>
          {#if errors.location_url}
            <p id="url-error" class="mt-2 text-sm text-boundaries" role="alert">
              {errors.location_url}
            </p>
          {/if}
        </div>
      {/if}

      <!-- Location Instructions -->
      <div class="md:col-span-2">
        <label
          for="location_instructions"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Anfahrt / Zugang
        </label>
        <textarea
          id="location_instructions"
          bind:value={formData.location_instructions}
          rows="2"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Besondere Hinweise zur Anfahrt, Parkmöglichkeiten, Eingänge, etc."
        ></textarea>
      </div>
    </div>
  </section>

  <!-- Additional Details -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Weitere Details
    </h2>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Max Participants -->
      <div>
        <label
          for="max_participants"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Maximale Teilnehmerzahl
        </label>
        <input
          type="number"
          id="max_participants"
          bind:value={formData.max_participants}
          min="1"
          max="200"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="z.B. 20"
        />
        <p class="mt-2 text-sm text-smoke-400">
          Leer lassen für unbegrenzte Teilnehmerzahl
        </p>
      </div>

      <!-- Tags -->
      <div>
        <label
          for="event-tags"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Tags / Schlagwörter
        </label>

        <!-- Hidden input for accessibility -->
        <input
          type="hidden"
          id="event-tags"
          value={formData.tags.join(",")}
          aria-describedby="tags-help"
        />

        <div class="mb-3">
          <div
            class="flex flex-wrap gap-2"
            role="group"
            aria-labelledby="event-tags"
          >
            {#each availableTags as tag, index (index)}
              <button
                type="button"
                on:click={() => addTag(tag)}
                class="px-3 py-1 text-sm bg-charcoal-700 hover:bg-accent-600 text-smoke-300 hover:text-charcoal-900 rounded-full border border-charcoal-600 hover:border-accent-400 transition-colors"
                class:bg-accent-600={formData.tags.includes(tag)}
                class:text-charcoal-900={formData.tags.includes(tag)}
                class:border-accent-400={formData.tags.includes(tag)}
                aria-pressed={formData.tags.includes(tag)}
              >
                {tag}
              </button>
            {/each}
          </div>
        </div>

        <p id="tags-help" class="text-sm text-smoke-400 mb-3">
          Klicken Sie auf Tags um sie auszuwählen. Ausgewählte Tags:
        </p>

        {#if formData.tags.length > 0}
          <div class="flex flex-wrap gap-2">
            {#each formData.tags as tag (tag)}
              <span
                class="inline-flex items-center px-3 py-1 text-sm bg-accent-600 text-charcoal-900 rounded-full"
              >
                {tag}
                <button
                  type="button"
                  on:click={() => removeTag(tag)}
                  class="ml-2 hover:text-boundaries"
                  aria-label="Tag '{tag}' entfernen"
                >
                  ×
                </button>
              </span>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Requirements -->
      <div class="md:col-span-2">
        <label
          for="requirements"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Voraussetzungen / Mitbringen
        </label>
        <textarea
          id="requirements"
          bind:value={formData.requirements}
          rows="3"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Was sollten die Teilnehmer mitbringen oder beachten?"
        ></textarea>
      </div>

      <!-- Safety Notes -->
      <div class="md:col-span-2">
        <label
          for="safety_notes"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Sicherheitshinweise
        </label>
        <textarea
          id="safety_notes"
          bind:value={formData.safety_notes}
          rows="3"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Wichtige Sicherheitshinweise für die Teilnehmer"
        ></textarea>
      </div>

      <!-- Preparation Notes -->
      <div class="md:col-span-2">
        <label
          for="preparation_notes"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Vorbereitung
        </label>
        <textarea
          id="preparation_notes"
          bind:value={formData.preparation_notes}
          rows="3"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Wie können sich die Teilnehmer optimal vorbereiten?"
        ></textarea>
      </div>
    </div>
  </section>

  <!-- Organizer Information -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Organisator-Informationen
    </h2>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Organizer Name -->
      <div>
        <label
          for="organizer_name"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Ihr Name <span class="text-boundaries" aria-label="Pflichtfeld"
            >*</span
          >
        </label>
        <input
          type="text"
          id="organizer_name"
          bind:value={formData.organizer_name}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Vor- und Nachname"
          required
          aria-describedby={errors.organizer_name ? "name-error" : undefined}
          aria-invalid={errors.organizer_name ? "true" : "false"}
        />
        {#if errors.organizer_name}
          <p id="name-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.organizer_name}
          </p>
        {/if}
      </div>

      <!-- Organizer Email -->
      <div>
        <label
          for="organizer_email"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          E-Mail-Adresse <span class="text-boundaries" aria-label="Pflichtfeld"
            >*</span
          >
        </label>
        <input
          type="email"
          id="organizer_email"
          bind:value={formData.organizer_email}
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="ihre.email@beispiel.de"
          required
          aria-describedby={errors.organizer_email
            ? "email-error"
            : "email-help"}
          aria-invalid={errors.organizer_email ? "true" : "false"}
        />
        <p id="email-help" class="mt-2 text-sm text-smoke-400">
          Diese E-Mail wird für die Kommunikation zum Event verwendet.
        </p>
        {#if errors.organizer_email}
          <p id="email-error" class="mt-2 text-sm text-boundaries" role="alert">
            {errors.organizer_email}
          </p>
        {/if}
      </div>

      <!-- Organizer Bio -->
      <div class="md:col-span-2">
        <label
          for="organizer_bio"
          class="block text-sm font-medium text-smoke-100 mb-2"
        >
          Kurze Beschreibung zu Ihrer Person (optional)
        </label>
        <textarea
          id="organizer_bio"
          bind:value={formData.organizer_bio}
          rows="3"
          class="w-full px-4 py-3 bg-charcoal-800 border border-charcoal-600 rounded-lg text-smoke-50 placeholder-smoke-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:border-transparent"
          placeholder="Ihre Erfahrung mit Hypnose, Qualifikationen, etc."
        ></textarea>
      </div>
    </div>
  </section>

  <!-- Consent and Terms -->
  <section>
    <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
      Zustimmung und Bedingungen
    </h2>

    <div class="space-y-4">
      <!-- Data Processing Consent -->
      <label class="flex items-start">
        <input
          type="checkbox"
          bind:checked={formData.consent_data_processing}
          required
          class="mt-1 mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 rounded focus:ring-accent-400 focus:ring-2"
          aria-describedby={errors.consent_data_processing
            ? "consent-error"
            : undefined}
          aria-invalid={errors.consent_data_processing ? "true" : "false"}
        />
        <span class="text-sm text-smoke-100">
          Ich stimme der <a
            href="/privacy"
            class="text-accent-400 hover:text-accent-300 underline"
            target="_blank"
            rel="noopener">Verarbeitung meiner Daten</a
          >
          zu und habe die
          <a
            href="/privacy"
            class="text-accent-400 hover:text-accent-300 underline"
            target="_blank"
            rel="noopener">Datenschutzerklärung</a
          >
          zur Kenntnis genommen.
          <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
        </span>
      </label>
      {#if errors.consent_data_processing}
        <p id="consent-error" class="text-sm text-boundaries" role="alert">
          {errors.consent_data_processing}
        </p>
      {/if}

      <!-- Code of Conduct Acceptance -->
      <label class="flex items-start">
        <input
          type="checkbox"
          bind:checked={formData.accepted_code_of_conduct}
          required
          class="mt-1 mr-3 w-4 h-4 text-accent-400 bg-charcoal-800 border-charcoal-600 rounded focus:ring-accent-400 focus:ring-2"
          aria-describedby={errors.accepted_code_of_conduct
            ? "conduct-error"
            : undefined}
          aria-invalid={errors.accepted_code_of_conduct ? "true" : "false"}
        />
        <span class="text-sm text-smoke-100">
          Ich habe den <a
            href="/code-of-conduct"
            class="text-accent-400 hover:text-accent-300 underline"
            target="_blank"
            rel="noopener">Verhaltenskodex</a
          >
          gelesen und erkenne ihn für mein Event als verbindlich an.
          <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
        </span>
      </label>
      {#if errors.accepted_code_of_conduct}
        <p id="conduct-error" class="text-sm text-boundaries" role="alert">
          {errors.accepted_code_of_conduct}
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
        <p class="text-boundaries">{submitError}</p>
      </div>
    {/if}

    <div class="flex items-center justify-end space-x-4">
      <p class="text-sm text-smoke-400">
        <span class="text-boundaries">*</span> Pflichtfelder
      </p>

      <button
        type="submit"
        disabled={isSubmitting || !isValidDateTime}
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
          Event einreichen
        {/if}
      </button>
    </div>

    <p id="submit-help" class="mt-3 text-sm text-smoke-400">
      Ihr Event wird vor der Veröffentlichung geprüft. Sie erhalten eine
      Bestätigung per E-Mail.
    </p>
  </section>
</form>

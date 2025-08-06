<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import DOMPurify from "dompurify";
  import ICAL from "ical.js";
  import { marked } from "marked";
  import { onMount } from "svelte";
  import {
    closeEventModal,
    selectedEvent,
    showEventModal,
  } from "../../stores/calendar";

  let modalElement: HTMLElement;
  let previousFocus: HTMLElement | null = null;

  dayjs.locale("de");

  // Focus trap elements
  let focusableElements: HTMLElement[] = [];
  let firstFocusableElement: HTMLElement | null = null;
  let lastFocusableElement: HTMLElement | null = null;

  $: event = $selectedEvent;
  $: isOpen = $showEventModal;

  // Format event details
  $: formattedDate = event
    ? dayjs(event.startDate).format("dddd, DD. MMMM YYYY")
    : "";
  $: formattedTime =
    event && !event.isAllDay
      ? `${dayjs(event.startDate).format("HH:mm")} - ${dayjs(event.endDate).format("HH:mm")} Uhr`
      : "Ganztägig";

  $: locationText = event
    ? (() => {
        switch (event.locationType) {
          case "online":
            return "Online-Veranstaltung";
          case "hybrid":
            return "Hybrid (Online & Vor Ort)";
          case "physical":
            return event.locationAddress || "Vor Ort";
          default:
            return "Ort wird noch bekannt gegeben";
        }
      })()
    : "";

  // Process markdown description
  $: processedDescription = event
    ? DOMPurify.sanitize(marked.parse(event.description || "") as string)
    : "";

  // Handle modal open/close
  $: if (isOpen && modalElement) {
    openModal();
  }

  const openModal = () => {
    // Store currently focused element
    previousFocus = document.activeElement as HTMLElement;

    // Get focusable elements
    updateFocusableElements();

    // Focus first element
    if (firstFocusableElement) {
      firstFocusableElement.focus();
    }

    // Lock body scroll
    document.body.style.overflow = "hidden";
  };

  const closeModal = () => {
    closeEventModal();

    // Restore focus
    if (previousFocus) {
      previousFocus.focus();
    }

    // Unlock body scroll
    document.body.style.overflow = "auto";
  };

  const updateFocusableElements = () => {
    if (!modalElement) return;

    const focusableElementsString =
      'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex="0"], [contenteditable]';
    focusableElements = Array.from(
      modalElement.querySelectorAll(focusableElementsString),
    );
    firstFocusableElement = focusableElements[0] || null;
    lastFocusableElement =
      focusableElements[focusableElements.length - 1] || null;
  };

  // Handle keyboard events
  const handleKeydown = (e: KeyboardEvent) => {
    if (!isOpen) return;

    if (e.key === "Escape") {
      e.preventDefault();
      closeModal();
      return;
    }

    // Focus trap
    if (e.key === "Tab") {
      if (e.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstFocusableElement) {
          e.preventDefault();
          lastFocusableElement?.focus();
        }
      } else {
        // Tab
        if (document.activeElement === lastFocusableElement) {
          e.preventDefault();
          firstFocusableElement?.focus();
        }
      }
    }
  };

  // Handle backdrop click
  const handleBackdropClick = (e: MouseEvent) => {
    if (e.target === e.currentTarget) {
      closeModal();
    }
  };

  // Generate ICS file for single event
  const downloadICS = () => {
    if (!event) return;

    try {
      const comp = new ICAL.Component(["vcalendar", [], []]);
      comp.updatePropertyWithValue("version", "2.0");
      comp.updatePropertyWithValue(
        "prodid",
        "-//Hypnose Stammtisch//Event//EN",
      );

      const vevent = new ICAL.Component("vevent");
      const icalEvent = new ICAL.Event(vevent);

      icalEvent.summary = event.title;
      icalEvent.description = event.description;
      icalEvent.location = locationText;
      icalEvent.startDate = ICAL.Time.fromJSDate(event.startDate, false);
      icalEvent.endDate = ICAL.Time.fromJSDate(event.endDate, false);
      icalEvent.uid = `event-${event.id}@hypnose-stammtisch.de`;

      comp.addSubcomponent(vevent);

      const icsData = comp.toString();
      const blob = new Blob([icsData], { type: "text/calendar" });
      const url = URL.createObjectURL(blob);

      const a = document.createElement("a");
      a.href = url;
      a.download = `${event.title.replace(/[^a-z0-9]/gi, "_").toLowerCase()}.ics`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (error) {
      console.error("Error generating ICS file:", error);
      alert("Fehler beim Generieren der Kalenderdatei");
    }
  };

  // Copy event link
  const copyEventLink = async () => {
    if (!event) return;

    const url = `${window.location.origin}/events/${event.id}`;

    try {
      await navigator.clipboard.writeText(url);
      // Show success feedback
      alert("Link kopiert!");
    } catch (error) {
      console.error("Error copying link:", error);
      alert("Fehler beim Kopieren des Links");
    }
  };

  onMount(() => {
    return () => {
      // Cleanup: restore body scroll
      document.body.style.overflow = "auto";
    };
  });
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen && event}
  <!-- Modal backdrop -->
  <div
    class="modal-backdrop"
    on:click={handleBackdropClick}
    role="presentation"
  >
    <!-- Modal content -->
    <div
      bind:this={modalElement}
      class="modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      aria-describedby="modal-description"
    >
      <div class="modal-content">
        <!-- Modal header -->
        <header
          class="flex items-start justify-between p-6 border-b border-charcoal-700"
        >
          <div class="flex-1 mr-4">
            <h2
              id="modal-title"
              class="text-2xl font-display font-bold text-smoke-50 mb-2"
            >
              {event.title}
            </h2>
            <div class="flex flex-wrap gap-2">
              {#each event.tags as tag}
                <span class="badge badge-primary">
                  {tag}
                </span>
              {/each}
              {#if event.beginnerFriendly}
                <span class="badge badge-consent"> ✨ Anfängerfreundlich </span>
              {/if}
            </div>
          </div>

          <button
            class="btn btn-ghost p-2"
            on:click={closeModal}
            aria-label="Modal schließen"
          >
            <svg
              class="w-6 h-6"
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
        </header>

        <!-- Modal body -->
        <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
          <!-- Event details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Date and time -->
            <div class="space-y-4">
              <div>
                <h3
                  class="text-sm font-semibold text-smoke-400 uppercase tracking-wide mb-2"
                >
                  Datum & Zeit
                </h3>
                <div class="space-y-1">
                  <div class="flex items-center text-smoke-200">
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                      />
                    </svg>
                    <time datetime={event.startDate.toISOString()}>
                      {formattedDate}
                    </time>
                  </div>
                  <div class="flex items-center text-smoke-200">
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                    <span>{formattedTime}</span>
                  </div>
                  <div class="text-sm text-smoke-400">
                    Zeitzone: {event.timezone}
                  </div>
                </div>
              </div>

              <!-- Recurring info -->
              {#if event.seriesId}
                <div>
                  <h3
                    class="text-sm font-semibold text-smoke-400 uppercase tracking-wide mb-2"
                  >
                    Wiederholung
                  </h3>
                  <div class="flex items-center text-smoke-200">
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      aria-hidden="true"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Wiederkehrende Veranstaltung
                  </div>
                </div>
              {/if}
            </div>

            <!-- Location -->
            <div>
              <h3
                class="text-sm font-semibold text-smoke-400 uppercase tracking-wide mb-2"
              >
                Ort
              </h3>
              <div class="space-y-2">
                <div class="flex items-start text-smoke-200">
                  <svg
                    class="w-5 h-5 mr-2 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                  <span>{locationText}</span>
                </div>

                {#if event.locationType === "online" && event.onlineUrl}
                  <div class="text-sm text-smoke-400">
                    Link wird vor dem Event bereitgestellt
                  </div>
                {:else if event.locationType === "physical" && event.locationAddress}
                  <address class="text-sm text-smoke-400 not-italic">
                    {event.locationAddress}
                  </address>
                {/if}
              </div>
            </div>
          </div>

          <!-- Description -->
          <div>
            <h3
              class="text-sm font-semibold text-smoke-400 uppercase tracking-wide mb-3"
            >
              Beschreibung
            </h3>
            <div
              id="modal-description"
              class="prose prose-invert prose-sm max-w-none prose-a:text-accent-400 prose-a:hover:text-accent-300"
            >
              {@html processedDescription}
            </div>
          </div>
        </div>

        <!-- Modal footer -->
        <footer
          class="flex flex-col sm:flex-row gap-3 p-6 border-t border-charcoal-700"
        >
          <button class="btn btn-primary flex-1" on:click={downloadICS}>
            📅 Kalendereintrag herunterladen
          </button>
          <button class="btn btn-outline flex-1" on:click={copyEventLink}>
            🔗 Link kopieren
          </button>
          <button class="btn btn-ghost" on:click={closeModal}>
            Schließen
          </button>
        </footer>
      </div>
    </div>
  </div>
{/if}

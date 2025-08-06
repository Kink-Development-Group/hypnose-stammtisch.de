<script lang="ts">
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";
  import AboutPlatform from "../components/sections/AboutPlatform.svelte";
  import ConsentSafety from "../components/sections/ConsentSafety.svelte";
  import EventSeries from "../components/sections/EventSeries.svelte";
  import Hero from "../components/sections/Hero.svelte";
  import IntroGuide from "../components/sections/IntroGuide.svelte";
  import UpcomingEvents from "../components/sections/UpcomingEvents.svelte";
  // Import stores
  import { events, isLoading } from "../stores/calendar";
  import { addNotification } from "../stores/ui";
  import { transformApiEvents } from "../utils/eventTransform";

  // Load upcoming events
  onMount(async () => {
    try {
      isLoading.set(true);

      // In a real app, this would be an API call
      const response = await fetch("/api/events?limit=6&upcoming=true");
      if (response.ok) {
        const result = await response.json();
        const apiEvents = result.success ? result.data : [];
        const transformedEvents = transformApiEvents(apiEvents);
        events.set(transformedEvents);
      } else {
        throw new Error("Failed to load events");
      }
    } catch (error) {
      console.error("Error loading events:", error);
      addNotification({
        type: "error",
        title: "Fehler beim Laden",
        message: "Die Veranstaltungen konnten nicht geladen werden.",
      });
    } finally {
      isLoading.set(false);
    }
  });
</script>

<svelte:head>
  <title>Hypnose Stammtisch - Deep Dive. Safe Play.</title>
  <meta
    name="description"
    content="Community-focused hypnosis events and workshops. Sichere und respektvolle Hypnose-Veranstaltungen in einer einladenden Gemeinschaft."
  />
  <meta
    property="og:title"
    content="Hypnose Stammtisch - Deep Dive. Safe Play."
  />
  <meta
    property="og:description"
    content="Community-focused hypnosis events and workshops. Sichere und respektvolle Hypnose-Veranstaltungen in einer einladenden Gemeinschaft."
  />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://hypnose-stammtisch.de/" />
</svelte:head>

<main class="min-h-screen">
  <!-- Hero Section -->
  <Hero />

  <!-- Event Series Section -->
  <EventSeries />

  <!-- Upcoming Events Section -->
  <section
    class="py-16 bg-charcoal-800"
    aria-labelledby="upcoming-events-heading"
  >
    <div class="container mx-auto px-4">
      <h2
        id="upcoming-events-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center mb-12"
      >
        Kommende Veranstaltungen
      </h2>
      <UpcomingEvents />

      <div class="text-center mt-12">
        <a href="/events" use:link class="btn btn-primary text-lg px-8 py-3">
          Alle Events anzeigen
        </a>
      </div>
    </div>
  </section>

  <!-- Consent & Safety Section -->
  <section class="py-16 bg-charcoal-900" aria-labelledby="safety-heading">
    <div class="container mx-auto px-4">
      <h2
        id="safety-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center mb-12"
      >
        Sicherheit & Einverständnis
      </h2>
      <ConsentSafety />
    </div>
  </section>

  <!-- Intro Guide Section -->
  <section class="py-16 bg-charcoal-800" aria-labelledby="intro-guide-heading">
    <div class="container mx-auto px-4">
      <h2
        id="intro-guide-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center mb-12"
      >
        Neu hier? Ein Leitfaden für Einsteiger
      </h2>
      <IntroGuide />
    </div>
  </section>

  <!-- About Platform Section -->
  <AboutPlatform />

  <!-- Consent & Safety Section -->
  <ConsentSafety />

  <!-- Call to Action Section -->
  <section class="py-16 bg-primary-900" aria-labelledby="cta-heading">
    <div class="container mx-auto px-4 text-center">
      <h2
        id="cta-heading"
        class="text-3xl md:text-4xl font-display font-bold mb-8"
      >
        Bereit, Teil unserer Community zu werden?
      </h2>
      <p class="text-xl text-primary-100 mb-8 max-w-2xl mx-auto">
        Tauche ein in eine Welt der sicheren Erkundung und des respektvollen
        Lernens. Unsere Community heißt alle willkommen, die mit Offenheit und
        Respekt kommen.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/events" use:link class="btn btn-primary text-lg px-8 py-3">
          Nächstes Event finden
        </a>
        <a href="/about" use:link class="btn btn-outline text-lg px-8 py-3">
          Mehr über uns erfahren
        </a>
      </div>
    </div>
  </section>
</main>

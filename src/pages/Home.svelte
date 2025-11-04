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
  <title>Hypnose-Stammtisch.de Deep Dive. Safe Play.</title>
  <meta
    name="description"
    content="Community-focused hypnosis events and workshops. Sichere und respektvolle Hypnose-Veranstaltungen in einer einladenden Gemeinschaft."
  />
  <meta
    property="og:title"
    content="Hypnose-Stammtisch.de - Deep Dive. Safe Play."
  />
  <meta
    property="og:description"
    content="Community-focused hypnosis events and workshops. Sichere und respektvolle Hypnose-Veranstaltungen in einer einladenden Gemeinschaft."
  />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://hypnose-stammtisch.de/" />
</svelte:head>

<div class="min-h-screen">
  <!-- Hero Section -->
  <Hero />

  <!-- Upcoming Events Section -->
  <section
    class="py-16 bg-charcoal-800"
    aria-labelledby="upcoming-events-heading"
  >
    <div class="container mx-auto px-4">
      <h2
        id="upcoming-events-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center text-smoke-50 mb-12"
      >
        Kommende Veranstaltungen
      </h2>
      <UpcomingEvents />

      <div class="text-center mt-12">
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="/events" use:link class="btn btn-primary text-lg px-8 py-3">
            📅 Alle Events anzeigen
          </a>
          <a href="/map" use:link class="btn btn-outline text-lg px-8 py-3">
            🗺️ Stammtisch-Karte
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Event Series Section -->
  <section class="py-16 bg-charcoal-900" aria-labelledby="event-series-heading">
    <div class="container mx-auto px-4">
      <h2
        id="event-series-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center text-smoke-50 mb-12"
      >
        Unsere Event-Reihen
      </h2>
      <EventSeries />
    </div>
  </section>

  <!-- Intro Guide Section -->
  <section class="py-16 bg-charcoal-800" aria-labelledby="intro-guide-heading">
    <div class="container mx-auto px-4">
      <h2
        id="intro-guide-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center text-smoke-50 mb-12"
      >
        Neu hier? Ein Leitfaden für Einsteiger
      </h2>
      <IntroGuide />
    </div>
  </section>

  <!-- Consent & Safety Section -->
  <section class="py-16 bg-charcoal-900" aria-labelledby="safety-heading">
    <div class="container mx-auto px-4">
      <h2
        id="safety-heading"
        class="text-3xl md:text-4xl font-display font-bold text-center text-smoke-50 mb-12"
      >
        Sicherheit & Einverständnis
      </h2>
      <ConsentSafety />
    </div>
  </section>

  <!-- About Platform Section -->
  <section
    class="py-16 bg-charcoal-800"
    aria-labelledby="about-platform-heading"
  >
    <div class="container mx-auto px-4">
      <AboutPlatform />
    </div>
  </section>

  <!-- Learning Resources Teaser -->
  <section
    class="py-16 bg-gradient-to-r from-accent-900 to-accent-800"
    aria-labelledby="resources-teaser-heading"
  >
    <div class="container mx-auto px-4 text-center">
      <h2
        id="resources-teaser-heading"
        class="text-3xl md:text-4xl font-display font-bold text-smoke-50 mb-8"
      >
        📚 Erweitere dein Wissen
      </h2>
      <p class="text-xl text-smoke-100 mb-8 max-w-2xl mx-auto">
        Entdecke unsere kuratierte Sammlung von Hypnose-Lernmaterialien: Bücher,
        Videos, Podcasts und interaktive Tools für alle Erfahrungslevel.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 max-w-4xl mx-auto">
        <div class="bg-charcoal-800/50 rounded-lg p-6">
          <div class="text-3xl mb-3">🇩🇪</div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">
            Deutsche Ressourcen
          </h3>
          <p class="text-sm text-smoke-300">
            Podcasts, Videos und Bücher auf Deutsch
          </p>
        </div>
        <div class="bg-charcoal-800/50 rounded-lg p-6">
          <div class="text-3xl mb-3">🇺🇸</div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">
            English Resources
          </h3>
          <p class="text-sm text-smoke-300">
            International books and tutorials
          </p>
        </div>
        <div class="bg-charcoal-800/50 rounded-lg p-6">
          <div class="text-3xl mb-3">🛠️</div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">
            Interactive Tools
          </h3>
          <p class="text-sm text-smoke-300">
            Praktische Hilfsmittel und Checklisten
          </p>
        </div>
      </div>
      <a
        href="/learning-resources"
        use:link
        class="btn btn-primary text-lg px-8 py-3"
      >
        Lernressourcen entdecken
      </a>
    </div>
  </section>

  <!-- Call to Action Section -->
  <section
    class="py-20 bg-gradient-to-r from-primary-900 to-primary-800"
    aria-labelledby="cta-heading"
  >
    <div class="container mx-auto px-4 text-center">
      <div class="max-w-4xl mx-auto">
        <h2
          id="cta-heading"
          class="text-4xl md:text-5xl font-display font-bold mb-8 text-smoke-50"
        >
          Bereit, Teil unserer Community zu werden?
        </h2>
        <p
          class="text-xl text-primary-100 mb-10 max-w-2xl mx-auto leading-relaxed"
        >
          Tauche ein in eine Welt der sicheren Erkundung und des respektvollen
          Lernens. Unsere Community heißt alle willkommen, die mit Offenheit und
          Respekt kommen.
        </p>

        <!-- Feature highlights -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-left">
          <div class="bg-primary-800/50 rounded-lg p-6">
            <div class="text-3xl mb-3">🛡️</div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Safe & Consensual
            </h3>
            <p class="text-sm text-primary-100">
              Alle Aktivitäten basieren auf explizitem Einverständnis und
              Sicherheitsprotokollen
            </p>
          </div>
          <div class="bg-primary-800/50 rounded-lg p-6">
            <div class="text-3xl mb-3">🎓</div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Lernfokussiert
            </h3>
            <p class="text-sm text-primary-100">
              Workshops, Demos und Lernmaterialien für alle Erfahrungslevel
            </p>
          </div>
          <div class="bg-primary-800/50 rounded-lg p-6">
            <div class="text-3xl mb-3">🤝</div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Inklusive Community
            </h3>
            <p class="text-sm text-primary-100">
              Respektvolle und einladende Atmosphäre für alle Teilnehmenden
            </p>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="/events" use:link class="btn btn-primary text-lg px-8 py-3">
            Nächstes Event finden
          </a>
          <a
            href="/about"
            use:link
            class="btn btn-outline-light text-lg px-8 py-3"
          >
            Mehr über uns erfahren
          </a>
          <a
            href="/learning-resources"
            use:link
            class="btn btn-outline-light text-lg px-8 py-3"
          >
            Lernressourcen ansehen
          </a>
        </div>

        <!-- Contact prompt -->
        <div class="mt-8 pt-6 border-t border-primary-700">
          <p class="text-sm text-primary-200 mb-3">
            Fragen? Unsicher, ob unsere Events das Richtige für dich sind?
          </p>
          <a
            href="/contact"
            use:link
            class="text-accent-400 hover:text-accent-300 transition-colors font-medium"
          >
            Kontaktiere uns - wir helfen gerne! →
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Seite nicht gefunden - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Die angeforderte Seite konnte nicht gefunden werden. Kehre zur Startseite zurück oder nutze die Navigation."
  />
</svelte:head>

<main class="container mx-auto px-4 py-16 max-w-4xl text-center">
  <!-- 404 Hero -->
  <div class="mb-12">
    <!-- Large 404 Text -->
    <div
      class="text-9xl md:text-[12rem] font-display font-bold text-smoke-800 leading-none mb-4"
    >
      404
    </div>

    <!-- Error Message -->
    <h1 class="text-3xl md:text-4xl font-display font-bold text-smoke-50 mb-4">
      Seite nicht gefunden
    </h1>

    <p class="text-xl text-smoke-300 mb-8 max-w-2xl mx-auto">
      Die Seite, die du suchst, existiert nicht oder wurde möglicherweise
      verschoben. Aber keine Sorge – wir helfen dir, den richtigen Weg zu
      finden.
    </p>
  </div>

  <!-- Navigation Suggestions -->
  <section class="mb-16">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-8">
      Wohin möchtest du gehen?
    </h2>

    <div
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto"
    >
      <!-- Home -->
      <a href="/" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">🏠</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Startseite
          </h3>
          <p class="text-smoke-300 text-sm">
            Zurück zur Übersicht und aktuellen Neuigkeiten
          </p>
        </div>
      </a>

      <!-- Events -->
      <a href="/events" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📅</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Events
          </h3>
          <p class="text-smoke-300 text-sm">
            Aktuelle Termine und kommende Veranstaltungen
          </p>
        </div>
      </a>

      <!-- About -->
      <a href="/about" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">ℹ️</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Über uns
          </h3>
          <p class="text-smoke-300 text-sm">
            Erfahre mehr über unsere Community und Mission
          </p>
        </div>
      </a>

      <!-- Resources -->
      <a href="/resources" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📚</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Ressourcen
          </h3>
          <p class="text-smoke-300 text-sm">
            Leitfäden, FAQ und hilfreiche Materialien
          </p>
        </div>
      </a>

      <!-- Contact -->
      <a href="/contact" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">💬</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Kontakt
          </h3>
          <p class="text-smoke-300 text-sm">
            Fragen oder Feedback? Schreib uns eine Nachricht
          </p>
        </div>
      </a>

      <!-- Code of Conduct -->
      <a href="/code-of-conduct" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📋</div>
          <h3
            class="text-lg font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
          >
            Verhaltenskodex
          </h3>
          <p class="text-smoke-300 text-sm">
            Unsere Community-Richtlinien und Werte
          </p>
        </div>
      </a>
    </div>
  </section>

  <!-- Search Suggestion -->
  <section class="mb-16">
    <div class="card bg-accent-400/10 border-accent-400/30 max-w-2xl mx-auto">
      <h2 class="text-xl font-semibold text-smoke-50 mb-4">
        Suchst du etwas Bestimmtes?
      </h2>
      <p class="text-smoke-300 text-sm mb-6">
        Falls du über einen Link oder eine Suchmaschine hierher gekommen bist,
        versuche es mit diesen Optionen:
      </p>

      <div class="space-y-3 text-sm text-left">
        <div class="flex items-start space-x-3">
          <span class="text-accent-400 mt-0.5">•</span>
          <div>
            <strong class="text-smoke-50">Schau in unsere Ressourcen:</strong>
            <span class="text-smoke-300">
              FAQ, Sicherheitsleitfaden und Bildungsmaterialien</span
            >
          </div>
        </div>

        <div class="flex items-start space-x-3">
          <span class="text-accent-400 mt-0.5">•</span>
          <div>
            <strong class="text-smoke-50">Kontaktiere uns direkt:</strong>
            <span class="text-smoke-300"> Wir helfen gerne bei der Suche</span>
          </div>
        </div>

        <div class="flex items-start space-x-3">
          <span class="text-accent-400 mt-0.5">•</span>
          <div>
            <strong class="text-smoke-50">Prüfe die URL:</strong>
            <span class="text-smoke-300">
              Möglicherweise ist ein Tippfehler drin</span
            >
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Help Section -->
  <section class="mb-16">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Brauchst du Hilfe?
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Technische Probleme
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Falls du immer wieder auf diese Seite kommst oder andere technische
          Probleme hast, lass es uns wissen.
        </p>
        <a
          href="mailto:support@hypnose-stammtisch.de"
          class="text-accent-400 hover:text-accent-300 text-sm"
        >
          support@hypnose-stammtisch.de
        </a>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Inhaltliche Fragen
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Für Fragen zu unseren Events, der Community oder anderen Themen sind
          wir gerne da.
        </p>
        <a
          href="mailto:info@hypnose-stammtisch.de"
          class="text-accent-400 hover:text-accent-300 text-sm"
        >
          info@hypnose-stammtisch.de
        </a>
      </div>
    </div>
  </section>

  <!-- Action Buttons -->
  <section>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/" use:link class="btn btn-primary"> Zur Startseite </a>
      <a href="/contact" use:link class="btn btn-outline"> Hilfe anfordern </a>
      <button onclick="history.back()" class="btn btn-outline">
        Zurück zur vorherigen Seite
      </button>
    </div>
  </section>

  <!-- Spiral Decoration -->
  <div class="mt-16 opacity-20">
    <svg viewBox="0 0 200 200" class="w-32 h-32 mx-auto text-accent-400">
      <path
        d="M100,100 m0,-80 a80,80 0 1,1 0,160 a70,70 0 1,1 0,-140 a60,60 0 1,1 0,120 a50,50 0 1,1 0,-100 a40,40 0 1,1 0,80 a30,30 0 1,1 0,-60 a20,20 0 1,1 0,40 a10,10 0 1,1 0,-20"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
      />
    </svg>
  </div>
</main>

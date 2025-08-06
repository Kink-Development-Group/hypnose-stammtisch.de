<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Über uns - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Erfahre mehr über unsere Community, Mission und die Menschen hinter dem Hypnose Stammtisch."
  />
</svelte:head>

<main class="container mx-auto px-4 py-8">
  <!-- Hero Section -->
  <section class="text-center mb-16">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-6">
      Über den Hypnose Stammtisch
    </h1>
    <p class="text-xl text-smoke-300 max-w-3xl mx-auto leading-relaxed">
      Wir sind eine Community von Menschen, die sich für die Kunst und
      Wissenschaft der Hypnose interessieren - in einem sicheren, respektvollen
      und einverständnis-zentrierten Umfeld.
    </p>
  </section>

  <!-- Mission Section -->
  <section class="mb-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div>
        <h2 class="text-3xl font-display font-semibold text-smoke-50 mb-6">
          Unsere Mission
        </h2>
        <div class="space-y-4 text-smoke-300 leading-relaxed">
          <p>
            Der Hypnose Stammtisch wurde gegründet, um einen sicheren Raum für
            Menschen zu schaffen, die sich für Hypnose interessieren - sei es
            als Hobby, zur persönlichen Entwicklung oder aus professionellem
            Interesse.
          </p>
          <p>
            Wir glauben fest daran, dass Hypnose eine faszinierende und
            wertvolle Fähigkeit ist, die mit Respekt, Wissen und vor allem mit
            dem ausdrücklichen Einverständnis aller Beteiligten praktiziert
            werden sollte.
          </p>
          <p>
            Unsere Community ist ein Ort des Lernens, des Experimentierens und
            des persönlichen Wachstums - immer unter Einhaltung höchster
            Sicherheits- und Ethikstandards.
          </p>
        </div>
      </div>

      <div class="relative">
        <div
          class="bg-gradient-to-br from-primary-900 to-secondary-900 rounded-lg p-8 text-center"
        >
          <div class="text-6xl mb-4" aria-hidden="true">🌟</div>
          <h3 class="text-xl font-semibold text-smoke-50 mb-4">
            Unsere Grundwerte
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-center space-x-2">
              <span class="text-consent">✓</span>
              <span>Informiertes Einverständnis</span>
            </div>
            <div class="flex items-center justify-center space-x-2">
              <span class="text-consent">✓</span>
              <span>Respektvolle Kommunikation</span>
            </div>
            <div class="flex items-center justify-center space-x-2">
              <span class="text-consent">✓</span>
              <span>Kontinuierliches Lernen</span>
            </div>
            <div class="flex items-center justify-center space-x-2">
              <span class="text-consent">✓</span>
              <span>Sichere Praktiken</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- History Section -->
  <section class="mb-16">
    <h2
      class="text-3xl font-display font-semibold text-smoke-50 mb-8 text-center"
    >
      Unsere Geschichte
    </h2>

    <div class="max-w-4xl mx-auto">
      <div class="space-y-8">
        <div class="flex items-start space-x-6">
          <div
            class="flex-shrink-0 w-16 h-16 bg-primary-900 text-primary-100 rounded-full flex items-center justify-center font-bold text-lg"
          >
            2020
          </div>
          <div>
            <h3 class="text-xl font-semibold text-smoke-50 mb-2">
              Gründung während der Pandemie
            </h3>
            <p class="text-smoke-300">
              Was als kleine Online-Gruppe begann, entwickelte sich schnell zu
              einer lebendigen Community von Hypnose-Enthusiasten aus ganz
              Deutschland.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-6">
          <div
            class="flex-shrink-0 w-16 h-16 bg-accent-400 text-charcoal-900 rounded-full flex items-center justify-center font-bold text-lg"
          >
            2021
          </div>
          <div>
            <h3 class="text-xl font-semibold text-smoke-50 mb-2">
              Erste Präsenz-Events
            </h3>
            <p class="text-smoke-300">
              Mit verbesserter Situation organisierten wir unsere ersten
              persönlichen Treffen - immer mit Fokus auf Sicherheit und
              Einverständnis.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-6">
          <div
            class="flex-shrink-0 w-16 h-16 bg-secondary-600 text-smoke-50 rounded-full flex items-center justify-center font-bold text-lg"
          >
            2023
          </div>
          <div>
            <h3 class="text-xl font-semibold text-smoke-50 mb-2">
              Professionalisierung und Wachstum
            </h3>
            <p class="text-smoke-300">
              Entwicklung unseres Verhaltenskodex, Schulung von Moderatoren und
              Aufbau einer robusten Gemeinschaftsstruktur.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-6">
          <div
            class="flex-shrink-0 w-16 h-16 bg-gradient-to-r from-accent-400 to-secondary-400 text-charcoal-900 rounded-full flex items-center justify-center font-bold text-lg"
          >
            2025
          </div>
          <div>
            <h3 class="text-xl font-semibold text-smoke-50 mb-2">
              Neue Website und erweiterte Angebote
            </h3>
            <p class="text-smoke-300">
              Launch dieser neuen Plattform mit verbesserter Zugänglichkeit,
              erweiterten Bildungsressourcen und hybriden Event-Formaten.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="mb-16">
    <h2
      class="text-3xl font-display font-semibold text-smoke-50 mb-8 text-center"
    >
      Unser Team
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Placeholder team members -->
      <div class="card text-center">
        <div
          class="w-24 h-24 bg-gradient-to-br from-primary-600 to-primary-800 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl"
        >
          👤
        </div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Community-Moderatoren
        </h3>
        <p class="text-smoke-300 text-sm">
          Geschulte Moderatoren, die bei allen Events für Sicherheit und
          Einhaltung unserer Standards sorgen.
        </p>
      </div>

      <div class="card text-center">
        <div
          class="w-24 h-24 bg-gradient-to-br from-accent-400 to-accent-600 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl"
        >
          🎓
        </div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Ausbilder & Mentoren
        </h3>
        <p class="text-smoke-300 text-sm">
          Erfahrene Praktiker, die ihr Wissen und ihre Expertise mit der
          Community teilen.
        </p>
      </div>

      <div class="card text-center">
        <div
          class="w-24 h-24 bg-gradient-to-br from-secondary-600 to-secondary-800 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl"
        >
          🛡️
        </div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Sicherheitsbeauftragte
        </h3>
        <p class="text-smoke-300 text-sm">
          Speziell geschulte Ansprechpartner für alle Fragen rund um Sicherheit
          und Beschwerdemanagement.
        </p>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="mb-16">
    <h2
      class="text-3xl font-display font-semibold text-smoke-50 mb-8 text-center"
    >
      Was uns wichtig ist
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="space-y-6">
        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">🤝</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Inklusive Gemeinschaft
            </h3>
            <p class="text-smoke-300 text-sm">
              Wir heißen Menschen aller Hintergründe, Orientierungen und
              Erfahrungslevel willkommen. Diversität macht unsere Community
              stärker.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">📚</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Evidenzbasiertes Lernen
            </h3>
            <p class="text-smoke-300 text-sm">
              Unsere Praktiken basieren auf wissenschaftlichen Erkenntnissen und
              bewährten Methoden der Hypnose-Community.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">🌱</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Persönliches Wachstum
            </h3>
            <p class="text-smoke-300 text-sm">
              Wir unterstützen jeden dabei, seine Ziele zu erreichen - sei es
              persönliche Entwicklung oder fachliche Weiterbildung.
            </p>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">🔒</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Datenschutz & Vertraulichkeit
            </h3>
            <p class="text-smoke-300 text-sm">
              Was in unserer Community besprochen wird, bleibt in unserer
              Community. Datenschutz ist ein Grundrecht.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">⚖️</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Ethische Standards
            </h3>
            <p class="text-smoke-300 text-sm">
              Wir halten uns an höchste ethische Standards und erwarten das
              gleiche von allen Community-Mitgliedern.
            </p>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <div class="text-3xl" aria-hidden="true">🎯</div>
          <div>
            <h3 class="text-lg font-semibold text-smoke-50 mb-2">
              Kontinuierliche Verbesserung
            </h3>
            <p class="text-smoke-300 text-sm">
              Wir hören auf Feedback, lernen aus Erfahrungen und verbessern
              kontinuierlich unsere Angebote und Prozesse.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="text-center bg-charcoal-800 rounded-lg p-8">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
      Möchtest du Teil unserer Community werden?
    </h2>
    <p class="text-smoke-300 mb-6 max-w-2xl mx-auto">
      Egal ob du komplett neu in der Welt der Hypnose bist oder bereits
      Erfahrung mitbringst - wir freuen uns darauf, dich kennenzulernen.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/events" use:link class="btn btn-primary">
        Nächstes Event finden
      </a>
      <a href="/contact" use:link class="btn btn-outline">
        Kontakt aufnehmen
      </a>
      <a href="/code-of-conduct" use:link class="btn btn-ghost">
        Verhaltenskodex lesen
      </a>
    </div>
  </section>
</main>

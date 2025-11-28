<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Über uns - Hypnose Stammtisch.de</title>
  <meta
    name="description"
    content="Erfahre mehr über unsere Community, Mission und die Menschen hinter dem Hypnose Stammtisch."
  />
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <!-- Hero Section -->
  <section class="text-center mb-16">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-6">
      Über Hypnose Stammtisch.de
    </h1>
    <p class="text-xl text-smoke-300 max-w-3xl mx-auto leading-relaxed">
      Unsere Mission ist es, eine einheitliche Plattform für Hypnose Events und
      Stammtische im deutschsprachigen Raum zu schaffen!
      <br />
      <br />
      Entstanden durch die Hamburger Kinky Hypnose Szene, und in ständigem Austausch
      mit vielen anderen Communitys möchten wir so vielen Menschen wie möglich einen
      sicheren und einfachen Einstieg in die Szene der Kink- und Freizeithypnose ermöglichen!
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
            Wir möchten eine sichere, inklusive und respektvolle Umgebung für
            alle Hypnose-Enthusiasten schaffen. Unser Ziel ist es, Wissen zu
            teilen, Erfahrungen auszutauschen und eine starke Community
            aufzubauen, die auf Vertrauen und gegenseitigem Respekt basiert.
          </p>
          <p>Unsere Grundsätze sind Konsens, Offenheit und Sicherheit!</p>
          <p>
            Wir setzen uns dafür ein, dass alle Mitglieder die Möglichkeit
            haben, sich aktiv einzubringen und ihre Perspektiven zu teilen.
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
</div>

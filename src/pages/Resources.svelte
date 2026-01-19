<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Ressourcen - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Entdecke Leitfäden, FAQs und wertvolle Ressourcen für sichere Hypnose-Praktiken."
  />
</svelte:head>

<main class="container mx-auto px-4 py-8">
  <!-- Page Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Ressourcen & Leitfäden
    </h1>
    <p class="text-xl text-smoke-300 max-w-3xl mx-auto">
      Hier findest du alle wichtigen Informationen, Leitfäden und Ressourcen für
      eine sichere und respektvolle Teilnahme an unserer Community.
    </p>
  </header>

  <!-- Quick Navigation -->
  <nav class="mb-12" aria-label="Ressourcen-Navigation">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <a
        href="/ressourcen/safety-guide"
        use:link
        class="btn btn-outline text-center"
      >
        🛡️ Sicherheitsleitfaden
      </a>
      <a href="/ressourcen/faq" use:link class="btn btn-outline text-center">
        ❓ Häufige Fragen
      </a>
      <a
        href="/learning-resources"
        use:link
        class="btn btn-outline text-center"
      >
        📚 Lern- & Wissensquellen
      </a>
      <a href="/contact" use:link class="btn btn-outline text-center">
        📞 Hilfe & Kontakt
      </a>
    </div>
  </nav>

  <!-- Main Resource Cards -->
  <section class="mb-16">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Safety Guide Card -->
      <a href="/ressourcen/safety-guide" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">🛡️</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Sicherheitsleitfaden
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Grundlegende Hinweise für sichere, respektvolle und inklusive
            Hypnose-Community-Events.
          </p>
          <div class="text-accent-400 text-sm font-medium">Mehr erfahren →</div>
        </div>
      </a>

      <!-- FAQ Card -->
      <a href="/ressourcen/faq" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">❓</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Häufige Fragen
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Antworten auf die am häufigsten gestellten Fragen zu Events,
            Sicherheit und Community.
          </p>
          <div class="text-accent-400 text-sm font-medium">FAQ ansehen →</div>
        </div>
      </a>

      <!-- Learning Resources Card -->
      <a href="/learning-resources" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📚</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Lern- & Wissensquellen
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Kuratierte Sammlung von Büchern, Videos, Podcasts und
            Online-Ressourcen zum Thema Hypnose.
          </p>
          <div class="text-accent-400 text-sm font-medium">
            Ressourcen entdecken →
          </div>
        </div>
      </a>

      <!-- Code of Conduct Card -->
      <a href="/code-of-conduct" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📋</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Verhaltenskodex
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Unsere Community-Richtlinien und Werte für ein respektvolles
            Miteinander.
          </p>
          <div class="text-accent-400 text-sm font-medium">Kodex lesen →</div>
        </div>
      </a>

      <!-- Contact Card -->
      <a href="/contact" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📞</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Kontakt & Hilfe
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Fragen oder Anliegen? Wir sind für dich da und helfen gerne weiter.
          </p>
          <div class="text-accent-400 text-sm font-medium">
            Kontakt aufnehmen →
          </div>
        </div>
      </a>

      <!-- Events Card -->
      <a href="/events" use:link class="card-hover group">
        <div class="card">
          <div class="text-4xl mb-4">📅</div>
          <h3
            class="text-xl font-semibold text-smoke-50 mb-3 group-hover:text-accent-400 transition-colors"
          >
            Events & Stammtische
          </h3>
          <p class="text-smoke-300 text-sm mb-4">
            Aktuelle Termine und kommende Veranstaltungen in deiner Region.
          </p>
          <div class="text-accent-400 text-sm font-medium">
            Events ansehen →
          </div>
        </div>
      </a>
    </div>
  </section>

  <!-- Important Notice -->
  <section class="mb-16">
    <div class="card bg-primary-400/10 border-primary-400/30">
      <h2
        class="text-2xl font-display font-semibold text-smoke-50 mb-4 flex items-center"
      >
        <span class="text-primary-400 mr-2">💡</span>
        Neu in der Community?
      </h2>
      <p class="text-smoke-300 mb-4">
        Wenn du neu bei uns bist, empfehlen wir dir, zunächst unseren
        Verhaltenskodex und den Sicherheitsleitfaden zu lesen. Diese geben dir
        einen guten Überblick über unsere Community-Werte und helfen dir, dich
        sicher und wohl zu fühlen.
      </p>
      <div class="flex flex-col sm:flex-row gap-4">
        <a href="/code-of-conduct" use:link class="btn btn-primary">
          Verhaltenskodex lesen
        </a>
        <a href="/ressourcen/safety-guide" use:link class="btn btn-outline">
          Sicherheitsleitfaden
        </a>
      </div>
    </div>
  </section>

  <!-- Emergency & Support -->
  <section class="mb-16">
    <h2 class="text-3xl font-display font-semibold text-smoke-50 mb-8">
      🚨 Notfall & Unterstützung
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Community-Support
        </h3>
        <div class="space-y-4 text-sm text-smoke-300">
          <div>
            <strong class="text-smoke-50">E-Mail:</strong> support@hypnose-stammtisch.de
          </div>
          <div>
            <strong class="text-smoke-50">Antwortzeit:</strong> In der Regel innerhalb
            von 24 Stunden
          </div>
          <div>
            <strong class="text-smoke-50">Sprachen:</strong> Deutsch, Englisch
          </div>
        </div>
      </div>

      <div class="card bg-caution/10 border-caution/30">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Krisenunterstützung
        </h3>
        <div class="space-y-4 text-sm text-smoke-300">
          <div>
            <strong class="text-smoke-50">Telefonseelsorge:</strong> 0800 111 0 111
          </div>
          <div>
            <strong class="text-smoke-50">Nummer gegen Kummer:</strong> 116 111
          </div>
          <div>
            <strong class="text-smoke-50"
              >Ärztlicher Bereitschaftsdienst:</strong
            > 116 117
          </div>
        </div>
        <div
          class="mt-4 p-3 bg-boundaries/20 border border-boundaries/40 rounded-lg"
        >
          <p class="text-xs text-smoke-400">
            Bei akuten Notfällen wähle 112 oder wende dich an die nächste
            Notaufnahme.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer CTA -->
  <section class="text-center bg-charcoal-800 rounded-lg p-8">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
      Bereit für dein erstes Event?
    </h2>
    <p class="text-smoke-300 mb-6">
      Mit all diesem Wissen bist du bestens vorbereitet. Schau dir unsere
      kommenden Events an und melde dich gerne bei Fragen.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/events" use:link class="btn btn-primary"> Events anzeigen </a>
      <a href="/contact" use:link class="btn btn-outline"> Fragen stellen </a>
    </div>
  </section>
</main>

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
      <a href="#safety-guide" class="btn btn-outline text-center">
        🛡️ Sicherheitsleitfaden
      </a>
      <a href="#faq" class="btn btn-outline text-center"> ❓ Häufige Fragen </a>
      <a href="#educational" class="btn btn-outline text-center">
        📚 Lernmaterialien
      </a>
      <a href="#contact-help" class="btn btn-outline text-center">
        📞 Hilfe & Kontakt
      </a>
    </div>
  </nav>

  <!-- Safety Guide Section -->
  <section
    id="safety-guide"
    class="mb-16"
    aria-labelledby="safety-guide-heading"
  >
    <h2
      id="safety-guide-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8"
    >
      🛡️ Sicherheitsleitfaden
    </h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="space-y-6">
        <div class="card">
          <h3 class="text-xl font-semibold text-smoke-50 mb-4">
            Vor deinem ersten Event
          </h3>
          <ul class="space-y-2 text-smoke-300">
            <li class="flex items-start">
              <span class="text-consent mr-2">✓</span>
              Lies unseren Verhaltenskodex aufmerksam durch
            </li>
            <li class="flex items-start">
              <span class="text-consent mr-2">✓</span>
              Überlege dir deine persönlichen Grenzen
            </li>
            <li class="flex items-start">
              <span class="text-consent mr-2">✓</span>
              Kontaktiere uns bei Fragen oder Unsicherheiten
            </li>
            <li class="flex items-start">
              <span class="text-consent mr-2">✓</span>
              Komme ausgeruht und nüchtern zum Event
            </li>
          </ul>
        </div>

        <div class="card">
          <h3 class="text-xl font-semibold text-smoke-50 mb-4">
            Während des Events
          </h3>
          <ul class="space-y-2 text-smoke-300">
            <li class="flex items-start">
              <span class="text-caution mr-2">⚠️</span>
              Kommuniziere deine Grenzen klar und deutlich
            </li>
            <li class="flex items-start">
              <span class="text-caution mr-2">⚠️</span>
              Du kannst jederzeit "Stopp" sagen
            </li>
            <li class="flex items-start">
              <span class="text-caution mr-2">⚠️</span>
              Wende dich bei Problemen sofort an einen Moderator
            </li>
            <li class="flex items-start">
              <span class="text-caution mr-2">⚠️</span>
              Respektiere die Grenzen anderer
            </li>
          </ul>
        </div>
      </div>

      <div class="space-y-6">
        <div class="card bg-boundaries/10 border-boundaries/30">
          <h3
            class="text-xl font-semibold text-smoke-50 mb-4 flex items-center"
          >
            <span class="text-boundaries mr-2">🚨</span>
            Notfall-Kontakte
          </h3>
          <div class="space-y-4 text-sm">
            <div>
              <strong class="text-smoke-50">24/7 Krisenhotline:</strong>
              <p class="text-smoke-300">
                0800 111 0 111 (kostenlose Telefonseelsorge) / 116 117
                (ärztlicher Bereitschaftsdienst)
              </p>
            </div>
            <div>
              <strong class="text-smoke-50">Event-Moderator:</strong>
              <p class="text-smoke-300">Immer vor Ort ansprechbar</p>
            </div>
            <div>
              <strong class="text-smoke-50">Community-Support:</strong>
              <p class="text-smoke-300">support@hypnose-stammtisch.de</p>
            </div>
          </div>
        </div>

        <div class="card">
          <h3 class="text-xl font-semibold text-smoke-50 mb-4">
            Wichtige Safewords
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
              <span class="font-medium text-smoke-50">"Rot / Mayday"</span>
              <span class="text-smoke-300">Sofortiger Abbruch</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="font-medium text-smoke-50">"Gelb"</span>
              <span class="text-smoke-300">Vorsicht, Grenze erreicht</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="font-medium text-smoke-50">"Grün"</span>
              <span class="text-smoke-300">Alles in Ordnung</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section id="faq" class="mb-16" aria-labelledby="faq-heading">
    <h2
      id="faq-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8"
    >
      ❓ Häufig gestellte Fragen
    </h2>

    <div class="space-y-6">
      <details class="card">
        <summary
          class="cursor-pointer text-lg font-semibold text-smoke-50 mb-4"
        >
          Ist Hypnose sicher?
        </summary>
        <div class="text-smoke-300 space-y-3">
          <p>
            Ja, bei korrekter Anwendung und in unserer kontrollierten Umgebung
            ist Hypnose sehr sicher. Alle unsere Moderatoren sind ausgebildet
            und folgen strengen Sicherheitsprotokollen.
          </p>
          <p>
            Du behältst immer die Kontrolle und kannst jederzeit die Hypnose
            beenden. Niemand kann dich zu etwas zwingen, was du nicht möchtest.
          </p>
        </div>
      </details>

      <details class="card">
        <summary
          class="cursor-pointer text-lg font-semibold text-smoke-50 mb-4"
        >
          Muss ich Erfahrung mit Hypnose haben?
        </summary>
        <div class="text-smoke-300 space-y-3">
          <p>
            Nein! Wir begrüßen Menschen aller Erfahrungsstufen. Viele unserer
            Events sind speziell für Anfänger konzipiert.
          </p>
          <p>
            Bei deinem ersten Besuch erhältst du eine persönliche Einführung und
            wirst von erfahrenen Community-Mitgliedern begleitet.
          </p>
        </div>
      </details>

      <details class="card">
        <summary
          class="cursor-pointer text-lg font-semibold text-smoke-50 mb-4"
        >
          Was passiert, wenn ich nicht hypnotisiert werden kann?
        </summary>
        <div class="text-smoke-300 space-y-3">
          <p>
            Das ist völlig normal! Nicht jeder ist gleich empfänglich für
            Hypnose, und das ist in Ordnung. Du kannst trotzdem wertvolle
            Erfahrungen machen.
          </p>
          <p>
            Du kannst zuschauen, lernen und dich als Hypnotiseur versuchen. Oft
            entwickelt sich die Empfänglichkeit auch über Zeit.
          </p>
        </div>
      </details>

      <details class="card">
        <summary
          class="cursor-pointer text-lg font-semibold text-smoke-50 mb-4"
        >
          Wie läuft ein typisches Event ab?
        </summary>
        <div class="text-smoke-300 space-y-3">
          <p>
            Events beginnen mit einer Begrüßung und Sicherheitseinweisung. Dann
            folgen je nach Event-Typ Workshops, Demonstrationen oder freie
            Praxis.
          </p>
          <p>
            Es gibt immer Zeit für Fragen, Diskussionen und Pausen. Events enden
            mit einer Nachbesprechung.
          </p>
        </div>
      </details>

      <details class="card">
        <summary
          class="cursor-pointer text-lg font-semibold text-smoke-50 mb-4"
        >
          Kostet die Teilnahme etwas?
        </summary>
        <div class="text-smoke-300 space-y-3">
          <p>
            Einige der Community-Events sind kostenlos. Manche Stammtische sind
            aufgrund des Veranstaltungsortes dazu genötigt, einen geringen
            Eintritt zu nehmen. Für spezielle Workshops oder Events mit externen
            Trainern oder Referenten kann eine Gebühr anfallen.
          </p>
          <p>
            Geld sollte niemals ein Hindernis für die Teilnahme sein.
            Kontaktiere gerne die Organisatoren, wenn du finanzielle
            Unterstützung benötigst.
          </p>
        </div>
      </details>
    </div>

    <div class="text-center mt-8">
      <p class="text-smoke-400 mb-4">
        Weitere Fragen? Wir helfen gerne weiter!
      </p>
      <a href="/contact" use:link class="btn btn-primary">
        Kontakt aufnehmen
      </a>
    </div>
  </section>

  <!-- Educational Resources -->
  <section id="educational" class="mb-16" aria-labelledby="educational-heading">
    <h2
      id="educational-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8"
    >
      📚 Lernmaterialien
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">📖</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Grundlagen der Hypnose
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Einführung in die Geschichte, Theorie und grundlegenden Techniken der
          Hypnose.
        </p>
        <a href="/resources/basics" use:link class="btn btn-outline text-sm">
          Weiterlesen
        </a>
      </div>

      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">🧠</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Psychologie & Neurowissenschaft
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Wissenschaftliche Grundlagen: Wie Hypnose im Gehirn funktioniert.
        </p>
        <a href="/resources/science" use:link class="btn btn-outline text-sm">
          Weiterlesen
        </a>
      </div>

      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">🎯</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Praktische Techniken
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Schritt-für-Schritt-Anleitungen für grundlegende Hypnose-Techniken.
        </p>
        <a
          href="/resources/techniques"
          use:link
          class="btn btn-outline text-sm"
        >
          Weiterlesen
        </a>
      </div>

      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">⚖️</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Ethik & Einverständnis
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Wichtige ethische Überlegungen und Best Practices für
          einverständnis-zentrierte Praxis.
        </p>
        <a href="/resources/ethics" use:link class="btn btn-outline text-sm">
          Weiterlesen
        </a>
      </div>

      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">📚</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Empfohlene Literatur
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Kuratierte Liste von Büchern, Studien und Online-Ressourcen.
        </p>
        <a
          href="/resources/reading-list"
          use:link
          class="btn btn-outline text-sm"
        >
          Zur Liste
        </a>
      </div>

      <div class="card">
        <div class="text-3xl mb-4" aria-hidden="true">🎥</div>
        <h3 class="text-lg font-semibold text-smoke-50 mb-2">
          Video-Tutorials
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Lehrvideos und Aufzeichnungen vergangener Workshops (nur für
          Mitglieder).
        </p>
        <a href="/resources/videos" use:link class="btn btn-outline text-sm">
          Ansehen
        </a>
      </div>
    </div>
  </section>

  <!-- Contact & Help -->
  <section
    id="contact-help"
    class="mb-16"
    aria-labelledby="contact-help-heading"
  >
    <h2
      id="contact-help-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8"
    >
      📞 Hilfe & Kontakt
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
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
        <div class="mt-6">
          <a href="/contact" use:link class="btn btn-primary">
            Nachricht senden
          </a>
        </div>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Notfall & Krisenunterstützung
        </h3>
        <div class="space-y-4 text-sm text-smoke-300">
          <div>
            <strong class="text-smoke-50">Telefonseelsorge:</strong> 0800 111 0 111
          </div>
          <div>
            <strong class="text-smoke-50">Nummer gegen Kummer:</strong> 116 111
          </div>
          <div>
            <strong class="text-smoke-50">Online-Beratung:</strong> youth-life-line.de
          </div>
        </div>
        <div class="mt-6 p-4 bg-caution/10 border border-caution/30 rounded-lg">
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

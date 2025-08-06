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
        📚 Lern- & Wissensquellen
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
      📚 Lern- & Wissensquellen
    </h2>

    <div class="mb-8 p-6 bg-charcoal-800 rounded-lg border border-charcoal-600">
      <p class="text-smoke-300 mb-4">
        Diese Sammlung an Ressourcen zum Lernen und Auseinandersetzen mit dem
        Thema Hypnose/Kinky Hypnose wurde und wird vom Team des Hamburger
        Hypnose-Munches zusammengetragen und kuratiert.
      </p>
      <p class="text-smoke-300 mb-4">
        Wir arbeiten ständig an der Verbesserung und freuen uns immer über
        weitere Quellen!
      </p>
      <p class="text-smoke-400 text-sm">
        Weitere Quellen können immer an <a
          href="mailto:support@hypnose-stammtisch.de"
          class="text-accent hover:text-accent-light transition-colors"
          >support@hypnose-stammtisch.de</a
        > eingeschickt werden!
      </p>
    </div>

    <!-- German Resources -->
    <div class="mb-12">
      <h3
        class="text-2xl font-display font-semibold text-smoke-50 mb-6 flex items-center"
      >
        🇩🇪 Deutsche Ressourcen
      </h3>

      <!-- Podcasts & Websites -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          🎧 Podcasts & Websites
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Die HypnoNerds!
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Ein fantastischer Podcast zum Thema erotische Hypnose!
            </p>
            <a
              href="https://hypnonerds.com/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Website besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              HypnoKink.de
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Erotische Hypnose mit Undine de Rivière! Audios und lesenswerte
              Newsletter!
            </p>
            <a
              href="https://hypnokink.de/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Website besuchen
            </a>
          </div>
        </div>
      </div>

      <!-- Video Tutorials -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          🎥 Video-Tutorials
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Handdrop Induktion
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Blitzhypnose lernen: Die Hand Drop Induktion
            </p>
            <a
              href="https://www.youtube.com/watch?v=fevtmp-huAk"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Raketenstart Induktion
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Hypnose lernen: Raketenstart Blitzhypnose Tutorial
            </p>
            <a
              href="https://www.youtube.com/watch?v=YGByd3EnDYs"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Arm Pull Induktion
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Blitzhypnose: Arm Pull Induktion
            </p>
            <a
              href="https://www.youtube.com/watch?v=wQ-YBlZlYi8"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
        </div>
      </div>

      <!-- German Books -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          📖 Buchempfehlungen
        </h4>
        <div class="space-y-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Der Hypnosebaukasten
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Eine Ansammlung von Induktionen, Vertiefungen und Ausleitungen.
              Eher für therapeutischen Gebrauch, aber mit vielen nützlichen
              Tricks.
            </p>
            <a
              href="https://www.amazon.de/gp/product/B00BMYVYS0/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Der Hypnotherapeutische Werkzeugkasten
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Christian Schwegler - Umfassende Sammlung therapeutischer
              Techniken
            </p>
            <a
              href="https://www.amazon.de/Hypnotherapeutische-Werkzeugkasten-Techniken-Induktionen-Interventionen/dp/3033048072/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Das große Handbuch der Hypnose
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Meinhold - Grundlegendes Werk zu Hypnose und Selbsthypnose
            </p>
            <a
              href="https://www.amazon.de/Das-gro%C3%9Fe-Handbuch-Hypnose-Selbsthypnose/dp/3945695384/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Was in Hypnose möglich ist
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Karl F. Stadler - Einblicke in die Möglichkeiten der Hypnose
            </p>
            <a
              href="https://www.amazon.de/Hypnose-Was-m%C3%B6glich-Karl-Stadler/dp/3929626969/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              101 Dinge, die ich gern gewusst hätte...
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Dabney M. Erwin - Praktische Tipps für den Einstieg in die Hypnose
            </p>
            <a
              href="https://www.amazon.de/Dinge-gewusst-anfing-Hypnose-arbeiten/dp/3896707868/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
        </div>
      </div>

      <!-- Other German Sources -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          🎭 Weitere Quellen
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Christo Showhypnose
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              YouTube-Kanal für Showhypnose mit interessanten Ideen
            </p>
            <a
              href="https://www.youtube.com/@ChristoShowhypnose"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              YouTube besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              BDSM Podcast "Not Vanilla"
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Hypnose-Folgen und Entspannungshypnose zum Ausprobieren
            </p>
            <div class="space-y-2 mt-3">
              <a
                href="https://open.spotify.com/episode/1Jou5b9WskwfmAkmYhMTmg"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline text-xs block"
              >
                Hypnose Folge 1
              </a>
              <a
                href="https://open.spotify.com/episode/3xEAZuNlo4oLJGnbG0H2Bh"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline text-xs block"
              >
                Hypnose Folge 2
              </a>
              <a
                href="https://open.spotify.com/episode/0ZVGAN99eO44VNIxXkjewr"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline text-xs block"
              >
                Entspannungshypnose
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- English Resources -->
    <div class="mb-12">
      <h3
        class="text-2xl font-display font-semibold text-smoke-50 mb-6 flex items-center"
      >
        🇺🇸 English Resources
      </h3>

      <!-- Tools & Interactive -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          🛠️ Tools & Interactive
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Hypnose Präferenzen Liste
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Umfassende Liste für Präferenzen zum Thema Hypnose
            </p>
            <a
              href="https://kink.hypnose-stammtisch.de/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Liste öffnen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Fernsteuerbares Metronom
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Für Online-Spiele und Hypnose-Sessions
            </p>
            <a
              href="https://foobar.institute/hypno/metro/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Metronom öffnen
            </a>
          </div>
        </div>
      </div>

      <!-- Video Tutorials English -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          🎥 Video Tutorials
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Body Language
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Reading minds through body language - TEDx Talk
            </p>
            <a
              href="https://www.youtube.com/watch?v=W3P3rT0j2gQ"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Dave Elman Induktion
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Mike Mandel demonstriert die Elman Induktion
            </p>
            <a
              href="https://www.youtube.com/watch?v=W5rXsq3ZL9o"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Hand Drop Induction
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Kostenlose Hypnose Induktions-Schulung
            </p>
            <a
              href="https://www.youtube.com/watch?v=mPgifhbFG_Y"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Video ansehen
            </a>
          </div>
        </div>
      </div>

      <!-- Learning Cards -->
      <div class="mb-8">
        <div class="card">
          <h4 class="text-xl font-semibold text-smoke-50 mb-4">
            🃏 Lernkarten
          </h4>
          <h5 class="text-lg font-semibold text-smoke-50 mb-2">
            Mike Mandel Hypnotic Language Cards
          </h5>
          <p class="text-smoke-300 text-sm mb-3">
            Professionelle Lernkarten für hypnotische Sprache
          </p>
          <a
            href="https://mikemandelhypnosis.com/hypnotic-language-cards-3/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline text-sm"
          >
            Mehr erfahren
          </a>
        </div>
      </div>

      <!-- English Books -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          📖 Book Recommendations
        </h4>
        <div class="space-y-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">Mind Play</h5>
            <p class="text-smoke-300 text-sm mb-3">
              DER Klassiker der Hypnosebücher. Einfacher Englischstil, perfekt
              für Einsteiger.
            </p>
            <a
              href="https://www.amazon.de/gp/product/1975987373/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Mind Play Study Guide
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Zweiter Teil von Mind Play, mehr für Personen die anderen etwas
              beibringen wollen.
            </p>
            <a
              href="https://www.amazon.de/gp/product/1512230278/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              The Brainwashing Book
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Hypnotic, Erotic Behaviorism and Beyond - Für Fortgeschrittene
            </p>
            <a
              href="https://www.amazon.de/gp/product/B08162XL65/"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Hypnotebook
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              A Practical Beginner's Guide to Hypnosis - Neutraler Einstieg
            </p>
            <a
              href="https://www.amazon.de/-/en/Hypnotebook-Practical-Beginners-Guide-Hypnosis/dp/9464687983"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Bei Amazon ansehen
            </a>
          </div>
        </div>
      </div>

      <!-- YouTube Channels -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-smoke-50 mb-4">
          📺 YouTube Channels
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Feel Good Hypnosis
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Hochwertige Hypnose-Inhalte
            </p>
            <a
              href="https://www.youtube.com/@feelgoodhypnosis"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Mike Mandel Hypnosis
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Professionelle Hypnose-Techniken
            </p>
            <a
              href="https://www.youtube.com/@MikeMandelHypnosis"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Secret Subject
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Interessante Hypnose-Perspektiven
            </p>
            <a
              href="https://www.youtube.com/@TheSecretSubject"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Hypno Hedonist
            </h5>
            <p class="text-smoke-300 text-sm mb-3">Kreative Hypnose-Ansätze</p>
            <a
              href="https://www.youtube.com/@HypnoHedonist"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Karl Smith Hypnosis
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              UK Hypnosis Academy - How to Hypnotize
            </p>
            <a
              href="https://www.youtube.com/@thehypnotist"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
          <div class="card">
            <h5 class="text-lg font-semibold text-smoke-50 mb-2">
              Anthony Galie
            </h5>
            <p class="text-smoke-300 text-sm mb-3">
              Showhypnose - wunderbare Ideen für Unfug
            </p>
            <a
              href="https://www.youtube.com/@AnthonyGalie"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-outline text-sm"
            >
              Kanal besuchen
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Call to Action -->
    <div
      class="text-center bg-charcoal-800 rounded-lg p-6 border border-charcoal-600"
    >
      <h3 class="text-xl font-display font-semibold text-smoke-50 mb-4">
        🚀 Bereit zum Lernen?
      </h3>
      <p class="text-smoke-300 mb-6">
        Mit diesen Ressourcen kannst du dein Wissen über Hypnose systematisch
        erweitern. Teile gerne deine eigenen Entdeckungen mit der Community!
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/events" use:link class="btn btn-primary"> Events besuchen </a>
        <a href="mailto:support@hypnose-stammtisch.de" class="btn btn-outline">
          Ressource vorschlagen
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

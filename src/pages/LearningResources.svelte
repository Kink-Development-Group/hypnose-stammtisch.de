<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Lern- & Wissensquellen - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Umfassende Sammlung von Hypnose-Lernmaterialien: Bücher, Videos, Podcasts und Online-Ressourcen für Einsteiger und Fortgeschrittene."
  />
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <!-- Page Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      📚 Lern- & Wissensquellen
    </h1>
    <p class="text-xl text-smoke-300 max-w-3xl mx-auto">
      Entdecke kuratierte Ressourcen zum Lernen und Vertiefen von Hypnose und
      kinky Hypnose - zusammengestellt und gepflegt von der Hamburger
      Hypnose-Community.
    </p>
  </header>

  <!-- Introduction -->
  <div class="mb-12 p-6 bg-charcoal-800 rounded-lg border border-charcoal-600">
    <div class="text-center mb-4">
      <div class="text-4xl mb-3">🧠✨</div>
      <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-3">
        Kuratiert von der Community
      </h2>
    </div>
    <p class="text-smoke-300 text-center mb-3">
      Diese Sammlung wird vom Team des Hamburger Hypnose-Munches gepflegt und
      ständig erweitert. Hast du Vorschläge? Schreib uns an
      <a
        href="mailto:support@hypnose-stammtisch.de?subject=Neue%20Lernressource"
        class="text-accent hover:text-accent-light transition-colors underline"
      >
        support@hypnose-stammtisch.de
      </a>
    </p>
  </div>

  <!-- Safety & Contraindications Section -->
  <section id="safety" class="mb-16" aria-labelledby="safety-heading">
    <div class="card bg-caution/10 border-caution/30">
      <div class="flex items-center mb-6">
        <div class="text-4xl mr-4">⚕️</div>
        <h2
          id="safety-heading"
          class="text-3xl font-display font-semibold text-smoke-50"
        >
          Sicherheit & Kontraindikationen
        </h2>
      </div>

      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 leading-relaxed mb-6">
          Hypnose ist ein wissenschaftlich anerkanntes und bei fachgerechter
          Anwendung sehr sicheres Verfahren. Dennoch gibt es bestimmte Zustände
          und Umstände, bei denen von einer Hypnose abzuraten ist oder diese nur
          mit besonderer Vorsicht und durch speziell qualifizierte Fachpersonen
          (z.B. Psychotherapeuten, Ärzte) durchgeführt werden darf.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <!-- Absolute Kontraindikationen -->
          <div
            class="bg-boundaries/10 border border-boundaries/30 rounded-lg p-6"
          >
            <h3
              class="text-xl font-semibold text-boundaries mb-4 flex items-center"
            >
              <span class="mr-2">🚫</span>
              Absolute Kontraindikationen
            </h3>
            <p class="text-sm text-smoke-300 mb-4">
              Hier sollte Hypnose grundsätzlich nicht angewendet werden:
            </p>
            <ul class="space-y-3 text-sm text-smoke-300">
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Akute Psychosen:</strong>
                  Schizophrenie, schwere wahnhafte Störungen oder akute manische Phasen.
                  Die hypnotische Trance könnte die Realitätswahrnehmung weiter stören.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Paranoide Störungen:</strong>
                  Hypnose kann als manipulativer Kontrollverlust erlebt werden und
                  die Symptomatik verstärken.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50"
                    >Dissoziative Identitätsstörung:</strong
                  >
                  Unsachgemäße Anwendung kann zu weiterer Destabilisierung führen.
                  Erfordert hochspezialisierte Kenntnisse.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Akute Suizidalität:</strong>
                  Krisenintervention und Sicherung haben oberste Priorität. Hypnose
                  ist nicht das Mittel der ersten Wahl.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Alkohol-/Drogeneinfluss:</strong
                  >
                  Klare Wahrnehmung und Kooperationsfähigkeit sind unerlässlich.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-boundaries mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Fehlende Bereitschaft:</strong>
                  Hypnose funktioniert nicht gegen den Willen einer Person. Eine Anwendung
                  wäre sinnlos und unethisch.
                </div>
              </li>
            </ul>
          </div>

          <!-- Relative Kontraindikationen -->
          <div class="bg-caution/10 border border-caution/30 rounded-lg p-6">
            <h3
              class="text-xl font-semibold text-caution mb-4 flex items-center"
            >
              <span class="mr-2">⚠️</span>
              Relative Kontraindikationen
            </h3>
            <p class="text-sm text-smoke-300 mb-4">
              Besondere Vorsicht geboten – nur durch erfahrene Therapeuten:
            </p>
            <ul class="space-y-3 text-sm text-smoke-300">
              <li class="flex items-start">
                <span class="text-caution mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Schwere Depressionen:</strong>
                  Regressive Techniken könnten den Zustand verschlimmern. Erst Stabilisierung
                  anstreben.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-caution mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50">Epilepsie:</strong>
                  Theoretisches Risiko von Anfallsauslösung. Nur nach Rücksprache
                  mit Neurologen.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-caution mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50"
                    >Borderline-Persönlichkeitsstörung:</strong
                  >
                  Risiko der Destabilisierung. Erfordert stabilen therapeutischen
                  Rahmen.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-caution mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50"
                    >Schwere Herz-Kreislauf-Erkrankungen:</strong
                  >
                  Intensive emotionale Abreaktionen könnten körperlich belasten.
                </div>
              </li>
              <li class="flex items-start">
                <span class="text-caution mr-2 mt-0.5">•</span>
                <div>
                  <strong class="text-smoke-50"
                    >Kognitive Einschränkungen:</strong
                  >
                  Bei Demenz oder stark eingeschränkter Intelligenz ist die Fähigkeit
                  zur Konzentration oft nicht gegeben.
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Wichtiger Hinweis -->
        <div
          class="bg-primary-400/10 border border-primary-400/30 rounded-lg p-6"
        >
          <h3
            class="text-lg font-semibold text-primary-400 mb-3 flex items-center"
          >
            <span class="mr-2">💡</span>
            Wichtiger Hinweis zur Qualifikation
          </h3>
          <div class="space-y-3 text-sm text-smoke-300">
            <p>
              Die wichtigste Voraussetzung für eine sichere Anwendung ist die
              <strong class="text-smoke-50"
                >Qualifikation des Hypnotiseurs/Therapeuten</strong
              >. Ein verantwortungsvoller Anwender wird immer eine sorgfältige
              Anamnese und Diagnostik durchführen, um mögliche
              Kontraindikationen zu erkennen.
            </p>
            <p>
              <strong class="text-smoke-50"
                >Für unsere Community-Events gilt:</strong
              >
              Wir praktizieren <em>Freizeithypnose</em> und keine Therapie. Bei gesundheitlichen
              Vorbelastungen solltest du immer vorher mit qualifizierten Fachpersonen
              sprechen. Informiere die Event-Moderatoren über relevante Vorerkrankungen.
            </p>
            <p class="text-xs text-smoke-400 italic">
              Diese Liste dient der allgemeinen Information und ersetzt keine
              ärztliche oder psychotherapeutische Abklärung. Vor Beginn einer
              Hypnosetherapie sollte immer ein ausführliches Vorgespräch mit
              einer qualifizierten Fachperson stattfinden.
            </p>
          </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center">
          <a href="/ressourcen/safety-guide" use:link class="btn btn-outline">
            🛡️ Sicherheitsleitfaden
          </a>
          <a href="/ressourcen/faq" use:link class="btn btn-outline">
            ❓ Häufige Fragen
          </a>
          <a href="/code-of-conduct" use:link class="btn btn-outline">
            📋 Verhaltenskodex
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Navigation -->
  <nav class="mb-12" aria-label="Lernressourcen-Navigation">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <button
        onclick={() =>
          document
            .getElementById("safety")
            ?.scrollIntoView({ behavior: "smooth" })}
        class="btn btn-outline text-center"
      >
        ⚕️ Sicherheit
      </button>
      <button
        onclick={() =>
          document
            .getElementById("german-resources")
            ?.scrollIntoView({ behavior: "smooth" })}
        class="btn btn-outline text-center"
      >
        🇩🇪 Deutsche Ressourcen
      </button>
      <button
        onclick={() =>
          document
            .getElementById("english-resources")
            ?.scrollIntoView({ behavior: "smooth" })}
        class="btn btn-outline text-center"
      >
        🇺🇸 English Resources
      </button>
      <button
        onclick={() =>
          document
            .getElementById("interactive-tools")
            ?.scrollIntoView({ behavior: "smooth" })}
        class="btn btn-outline text-center"
      >
        🛠️ Tools & Interaktiv
      </button>
      <button
        onclick={() =>
          document
            .getElementById("community-contribute")
            ?.scrollIntoView({ behavior: "smooth" })}
        class="btn btn-outline text-center"
      >
        🤝 Zur Community beitragen
      </button>
    </div>
  </nav>

  <!-- German Resources -->
  <section
    id="german-resources"
    class="mb-16"
    aria-labelledby="german-resources-heading"
  >
    <h2
      id="german-resources-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8 flex items-center"
    >
      🇩🇪 Deutsche Ressourcen
    </h2>

    <!-- Podcasts & Websites -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        🎧 Podcasts & Websites
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">🎙️</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            Die HypnoNerds!
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Ein fantastischer Podcast zum Thema erotische Hypnose! Regelmäßige
            Folgen mit tiefgehenden Diskussionen und praktischen Tipps.
          </p>
          <a
            href="https://hypnonerds.com/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Website besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🌹</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">HypnoKink.de</h4>
          <p class="text-smoke-300 text-sm mb-4">
            Erotische Hypnose mit Undine de Rivière! Hier findest du Audios und
            einen wirklich lesenswerten Newsletter.
          </p>
          <a
            href="https://hypnokink.de/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Website besuchen
          </a>
        </div>
      </div>
    </div>

    <!-- Video Tutorials -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        🎥 Video-Tutorials
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">✋</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Handdrop Induktion
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Blitzhypnose lernen: Die Hand Drop Induktion
          </p>
          <a
            href="https://www.youtube.com/watch?v=fevtmp-huAk"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🚀</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Raketenstart Induktion
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Hypnose lernen: Raketenstart Blitzhypnose Tutorial
          </p>
          <a
            href="https://www.youtube.com/watch?v=YGByd3EnDYs"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">💪</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Arm Pull Induktion
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Blitzhypnose: Arm Pull Induktion
          </p>
          <a
            href="https://www.youtube.com/watch?v=wQ-YBlZlYi8"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
      </div>
    </div>

    <!-- German Books -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        📖 Buchempfehlungen
      </h3>
      <div class="space-y-6">
        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">📚</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Der Hypnosebaukasten
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Eine Ansammlung von Induktionen, Vertiefungen und Ausleitungen.
                Es wurde eher für den therapeutischen Gebrauch geschrieben, aber
                man kann sich trotzdem ein paar Tricks hier raus holen.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">🛠️</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Der Hypnotherapeutische Werkzeugkasten
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Christian Schwegler - Umfassende Sammlung therapeutischer
                Techniken, Induktionen und Interventionen.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">📖</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Das große Handbuch der Hypnose
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Meinhold - Grundlegendes Werk zu Hypnose und Selbsthypnose mit
                umfassenden theoretischen und praktischen Grundlagen.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">🌟</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Was in Hypnose möglich ist
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Karl F. Stadler - Faszinierende Einblicke in die vielfältigen
                Möglichkeiten und Anwendungen der Hypnose.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">💡</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                101 Dinge, die ich gern gewusst hätte...
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Dabney M. Erwin - Praktische Tipps und Erkenntnisse für alle,
                die mit Hypnose arbeiten möchten. Sehr hilfreich für Einsteiger.
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
      </div>
    </div>

    <!-- Other German Sources -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        🎭 Weitere deutsche Quellen
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">🎪</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            Christo Showhypnose
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            YouTube-Kanal für Showhypnose mit interessanten Ideen und Techniken
            für unterhaltsame Hypnose-Demonstrationen.
          </p>
          <a
            href="https://www.youtube.com/@ChristoShowhypnose"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            YouTube besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🎧</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            BDSM Podcast "Not Vanilla"
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Hypnose-Folgen und Entspannungshypnose zum Ausprobieren in diesem
            beliebten BDSM-Podcast.
          </p>
          <div class="space-y-2">
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
  </section>

  <!-- English Resources -->
  <section
    id="english-resources"
    class="mb-16"
    aria-labelledby="english-resources-heading"
  >
    <h2
      id="english-resources-heading"
      class="text-3xl font-display font-semibold text-smoke-50 mb-8 flex items-center"
    >
      🇺🇸 English Resources
    </h2>

    <!-- Interactive Tools -->
    <div class="mb-10" id="interactive-tools">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        🛠️ Tools & Interactive
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">📋</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            Hypnose Präferenzen Liste
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Umfassende Liste für Präferenzen zum Thema Hypnose - perfekt für die
            Kommunikation zwischen Partnern.
          </p>
          <a
            href="https://kink.hypnose-stammtisch.de/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Liste öffnen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🎵</div>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            Fernsteuerbares Metronom
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Für Online-Spiele und Hypnose-Sessions - synchronisiert und
            fernsteuerbar für Hypnotiseur und Subject.
          </p>
          <a
            href="https://foobar.institute/hypno/metro/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Metronom öffnen
          </a>
        </div>
      </div>
    </div>

    <!-- Video Tutorials English -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        🎥 Video Tutorials
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">👁️</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Body Language
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Reading minds through body language - TEDx Talk von Lynne Franklin
          </p>
          <a
            href="https://www.youtube.com/watch?v=W3P3rT0j2gQ"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">⚡</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Dave Elman Induktion
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Mike Mandel demonstriert die klassische Elman Induktion
          </p>
          <a
            href="https://www.youtube.com/watch?v=W5rXsq3ZL9o"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">✋</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Hand Drop Induction
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Kostenlose Hypnose Induktions-Schulung
          </p>
          <a
            href="https://www.youtube.com/watch?v=mPgifhbFG_Y"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Video ansehen
          </a>
        </div>
      </div>
    </div>

    <!-- Learning Cards -->
    <div class="mb-10">
      <div class="card">
        <div class="text-center">
          <div class="text-4xl mb-4">🃏</div>
          <h3 class="text-2xl font-semibold text-smoke-50 mb-4">Lernkarten</h3>
          <h4 class="text-xl font-semibold text-smoke-50 mb-3">
            Mike Mandel Hypnotic Language Cards
          </h4>
          <p class="text-smoke-300 text-sm mb-4 max-w-2xl mx-auto">
            Professionelle Lernkarten für hypnotische Sprache - ein praktisches
            Tool zum Erlernen und Üben hypnotischer Sprachmuster.
          </p>
          <a
            href="https://mikemandelhypnosis.com/hypnotic-language-cards-3/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
          >
            Mehr erfahren
          </a>
        </div>
      </div>
    </div>

    <!-- English Books -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        📖 Book Recommendations
      </h3>
      <div class="space-y-6">
        <div class="card highlight-card border-accent/20 bg-accent/5">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">⭐</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Mind Play
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                <strong>DER Klassiker</strong> der Hypnosebücher! Wer Englisch versteht,
                sollte sich auf jeden Fall dieses Buch kaufen. Hier wird man wirklich
                gut an die Hand genommen und mit einem sehr einfachen Englischstil
                werden die wichtigsten Sachen erklärt.
              </p>
              <a
                href="https://www.amazon.de/gp/product/1975987373/"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-primary text-sm"
              >
                Bei Amazon ansehen
              </a>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">📝</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Mind Play Study Guide
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Der zweite Teil von Mind Play. Dieser richtet sich mehr an
                Personen, die anderen etwas beibringen wollen. Allerdings gibt
                es auch viele neue Sachen, die im ersten Teil nicht beschrieben
                wurden.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">🧠</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                The Brainwashing Book
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                Hypnotic, Erotic Behaviorism and Beyond - Ein Buch zum Thema
                Brainwashing mit Hilfe von Hypnose. <strong
                  >Eher für Fortgeschrittene</strong
                >, da hier nicht erklärt wird, wie Hypnose funktioniert, sondern
                mehr auf die Anwendungsmöglichkeiten eingegangen wird.
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
          </div>
        </div>

        <div class="card">
          <div class="flex items-start space-x-4">
            <div class="text-3xl">📚</div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-smoke-50 mb-2">
                Hypnotebook
              </h4>
              <p class="text-smoke-300 text-sm mb-3">
                A Practical Beginner's Guide to Hypnosis - Ein Buch zum Thema
                Einstieg in die Hypnose: Was ist möglich, was nicht? Eine
                neutrale, nicht-kinky Lektüre für den Grundlagen-Einstieg.
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
      </div>
    </div>

    <!-- YouTube Channels -->
    <div class="mb-10">
      <h3 class="text-2xl font-semibold text-smoke-50 mb-6">
        📺 YouTube Channels
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="card">
          <div class="text-3xl mb-4">😌</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Feel Good Hypnosis
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Hochwertige Hypnose-Inhalte mit Fokus auf Wohlbefinden
          </p>
          <a
            href="https://www.youtube.com/@feelgoodhypnosis"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🎯</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Mike Mandel Hypnosis
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Professionelle Hypnose-Techniken vom Experten
          </p>
          <a
            href="https://www.youtube.com/@MikeMandelHypnosis"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🔮</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Secret Subject
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Interessante Hypnose-Perspektiven und Experimente
          </p>
          <a
            href="https://www.youtube.com/@TheSecretSubject"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🌈</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Hypno Hedonist
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Kreative Hypnose-Ansätze und innovative Techniken
          </p>
          <a
            href="https://www.youtube.com/@HypnoHedonist"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🎓</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Karl Smith Hypnosis
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            UK Hypnosis Academy - How to Hypnotize
          </p>
          <a
            href="https://www.youtube.com/@thehypnotist"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
        <div class="card">
          <div class="text-3xl mb-4">🎪</div>
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Anthony Galie
          </h4>
          <p class="text-smoke-300 text-sm mb-4">
            Showhypnose - wunderbare Ideen für kreativen Unfug
          </p>
          <a
            href="https://www.youtube.com/@AnthonyGalie"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline"
          >
            Kanal besuchen
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Community Contribution -->
  <section
    id="community-contribute"
    class="mb-16"
    aria-labelledby="community-contribute-heading"
  >
    <div
      class="text-center bg-gradient-to-r from-charcoal-800 to-charcoal-700 rounded-xl p-8 border border-charcoal-600"
    >
      <div class="text-4xl mb-6">🤝</div>
      <h2
        id="community-contribute-heading"
        class="text-3xl font-display font-semibold text-smoke-50 mb-6"
      >
        Zur Community beitragen
      </h2>
      <p class="text-smoke-300 mb-6 max-w-2xl mx-auto">
        Diese Sammlung lebt von der Community! Hast du eine großartige Ressource
        entdeckt? Kennst du ein Buch, Video oder Tool, das anderen helfen
        könnte? Teile es mit uns!
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a
          href="mailto:support@hypnose-stammtisch.de?subject=Neue%20Lernressource%20vorschlagen"
          class="btn btn-primary"
        >
          📧 Ressource vorschlagen
        </a>
        <a href="/events" use:link class="btn btn-outline">
          🎯 Events besuchen
        </a>
        <a href="/contact" use:link class="btn btn-outline">
          💬 Kontakt aufnehmen
        </a>
      </div>
    </div>
  </section>

  <!-- Navigation Footer -->
  <nav class="text-center" aria-label="Seiten-Navigation">
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/resources" use:link class="btn btn-outline">
        🛡️ Zu den Sicherheitsleitfäden
      </a>
      <a href="/code-of-conduct" use:link class="btn btn-outline">
        📋 Verhaltenskodex lesen
      </a>
      <a href="/about" use:link class="btn btn-outline">
        👥 Über uns erfahren
      </a>
    </div>
  </nav>
</div>

<style>
  .highlight-card {
    position: relative;
    overflow: hidden;
  }

  .highlight-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(var(--accent), 0.1),
      transparent
    );
    transition: left 0.6s;
  }

  .highlight-card:hover::before {
    left: 100%;
  }
</style>

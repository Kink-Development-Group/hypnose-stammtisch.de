<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Impressum - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Rechtliche Informationen und Kontaktdaten des Hypnose Stammtisch."
  />
</svelte:head>

<div class="container mx-auto px-4 py-8 max-w-4xl">
  <!-- Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Impressum
    </h1>
    <p class="text-xl text-smoke-300">
      Angaben gemäß § 5 DDG (Digitale-Dienste-Gesetz)
    </p>
  </header>

  <!-- Responsible Party -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Angaben gemäß § 5 DDG
    </h2>

    <div class="card">
      <div class="space-y-6">
        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">
            Verantwortlich für den Inhalt:
          </h3>
          <div class="text-smoke-300 space-y-1">
            <!--
              ============================================
              PLATZHALTER - BITTE AUSFÜLLEN
              ============================================
              Hier müssen deine echten Daten eingetragen werden:
              - Vor- und Nachname
              - Vollständige Anschrift (Straße, Hausnummer, PLZ, Ort)
              - Land
            -->
            <p class="font-medium">Jonas Pfalzgraf</p>
            <p>Alter Heidberg 22</p>
            <p>22846 Norderstedt</p>
            <p>Deutschland</p>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">Kontakt:</h3>
          <div class="text-smoke-300 space-y-1">
            <!--
              ============================================
              PLATZHALTER - BITTE AUSFÜLLEN
              ============================================
              Mindestens E-Mail ist Pflicht.
              Telefon ist optional, aber empfohlen.
            -->
            <p>
              E-Mail: <a
                href="mailto:info@hypnose-stammtisch.de"
                class="text-accent-400 hover:text-accent-300"
                >info@hypnose-stammtisch.de</a
              >
            </p>
          </div>
        </div>

        <div
          class="p-4 rounded-lg bg-accent-400/10 border border-accent-400/30"
        >
          <p class="text-smoke-300 text-sm">
            <strong class="text-accent-400">Hinweis:</strong>
            Diese Website wird von einer Privatperson betrieben. Es handelt sich nicht
            um ein Unternehmen oder einen eingetragenen Verein.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Content Responsibility according to § 18 MStV -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Verantwortlich für redaktionelle Inhalte
    </h2>

    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Verantwortlich im Sinne des § 18 Abs. 2 Medienstaatsvertrag (MStV):
      </p>
      <div class="text-smoke-300 space-y-1">
        <!--
          ============================================
          PLATZHALTER - BITTE AUSFÜLLEN
          ============================================
          Gleiche Person wie oben, bei journalistisch-redaktionellen Inhalten erforderlich.
        -->
        <p class="font-medium">Jonas Pfalzgraf</p>
        <p>Alter Heidberg 22</p>
        <p>22846 Norderstedt</p>
      </div>
    </div>
  </section>

  <!-- EU Dispute Resolution -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      EU-Streitschlichtung
    </h2>

    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Die Europäische Kommission stellt eine Plattform zur
        Online-Streitbeilegung (OS) bereit:
      </p>
      <p class="text-smoke-300 text-sm mb-4">
        <a
          href="https://ec.europa.eu/consumers/odr/"
          class="text-accent-400 hover:text-accent-300"
          target="_blank"
          rel="noopener noreferrer"
        >
          https://ec.europa.eu/consumers/odr/
        </a>
      </p>
      <p class="text-smoke-300 text-sm">
        Da diese Plattform ausschließlich kostenlose, nicht-kommerzielle Dienste
        anbietet, besteht keine Verpflichtung zur Teilnahme an
        Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle.
      </p>
    </div>
  </section>

  <!-- Liability for Content -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Haftung für Inhalte
    </h2>

    <div class="card">
      <div class="prose prose-invert max-w-none text-sm">
        <p class="text-smoke-300 mb-4">
          Als Diensteanbieter bin ich gemäß § 7 Abs.1 DDG für eigene Inhalte auf
          diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8
          bis 10 DDG bin ich als Diensteanbieter jedoch nicht verpflichtet,
          übermittelte oder gespeicherte fremde Informationen zu überwachen oder
          nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit
          hinweisen.
        </p>

        <p class="text-smoke-300 mb-4">
          Verpflichtungen zur Entfernung oder Sperrung der Nutzung von
          Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt.
          Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der
          Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden
          von entsprechenden Rechtsverletzungen werde ich diese Inhalte umgehend
          entfernen.
        </p>
      </div>
    </div>
  </section>

  <!-- Liability for Links -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Haftung für Links
    </h2>

    <div class="card">
      <div class="prose prose-invert max-w-none text-sm">
        <p class="text-smoke-300 mb-4">
          Diese Website enthält Links zu externen Websites Dritter, auf deren
          Inhalte ich keinen Einfluss habe. Deshalb kann ich für diese fremden
          Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten
          Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten
          verantwortlich.
        </p>

        <p class="text-smoke-300 mb-4">
          Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche
          Rechtsverstöße überprüft. Rechtswidrige Inhalte waren zum Zeitpunkt
          der Verlinkung nicht erkennbar. Eine permanente inhaltliche Kontrolle
          der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer
          Rechtsverletzung nicht zumutbar. Bei Bekanntwerden von
          Rechtsverletzungen werde ich derartige Links umgehend entfernen.
        </p>
      </div>
    </div>
  </section>

  <!-- Copyright -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Urheberrecht
    </h2>

    <div class="card">
      <div class="prose prose-invert max-w-none text-sm">
        <p class="text-smoke-300 mb-4">
          Die durch mich erstellten Inhalte und Werke auf diesen Seiten
          unterliegen dem deutschen Urheberrecht. Die Vervielfältigung,
          Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der
          Grenzen des Urheberrechtes bedürfen meiner schriftlichen Zustimmung.
        </p>

        <p class="text-smoke-300 mb-4">
          Downloads und Kopien dieser Seite sind nur für den privaten, nicht
          kommerziellen Gebrauch gestattet. Soweit die Inhalte auf dieser Seite
          nicht von mir erstellt wurden, werden die Urheberrechte Dritter
          beachtet. Insbesondere werden Inhalte Dritter als solche
          gekennzeichnet.
        </p>

        <p class="text-smoke-300 mb-4">
          Solltest du trotzdem auf eine Urheberrechtsverletzung aufmerksam
          werden, bitte ich um einen entsprechenden Hinweis. Bei Bekanntwerden
          von Rechtsverletzungen werde ich derartige Inhalte umgehend entfernen.
        </p>

        <p class="text-smoke-300">
          <strong class="text-smoke-100">Open Source:</strong> Der Quellcode
          dieser Website ist Open Source und unter der MIT-Lizenz auf
          <a
            href="https://github.com/Kink-Development-Group/hypnose-stammtisch.de"
            target="_blank"
            rel="noopener noreferrer"
            class="text-accent-400 hover:text-accent-300">GitHub</a
          > verfügbar.
        </p>
      </div>
    </div>
  </section>

  <!-- Technical Information -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Technische Umsetzung
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">Hosting</h3>
        <div class="text-smoke-300 text-sm space-y-1">
          <!--
            ============================================
            PLATZHALTER - BITTE AUSFÜLLEN
            ============================================
            Hier die Daten deines Hosting-Anbieters eintragen.
          -->
          <p><strong>Anbieter:</strong> Hetzner Online GmbH</p>
          <p>Industriestr. 25</p>
          <p>91710 Gunzenhausen, Deutschland</p>
          <p>
            <strong>Website:</strong>
            <a
              href="https://www.hetzner.com"
              class="text-accent-400 hover:text-accent-300"
              target="_blank"
              rel="noopener noreferrer">hetzner.com</a
            >
          </p>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">Entwicklung</h3>
        <div class="text-smoke-300 text-sm space-y-1">
          <p><strong>Framework:</strong> Svelte 5 + TypeScript</p>
          <p><strong>Styling:</strong> Tailwind CSS v4</p>
          <p><strong>Backend:</strong> PHP 8.1+ mit MySQL</p>
          <p><strong>SSL:</strong> Let's Encrypt</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Content Policy -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Inhaltliche Verantwortung
    </h2>

    <div class="card">
      <div class="prose prose-invert max-w-none text-sm">
        <p class="text-smoke-300 mb-4">
          Die Inhalte dieser Website dienen ausschließlich der Information über
          die Hypnose-Stammtisch-Community und deren Events. Sie stellen keine
          medizinische Beratung oder therapeutische Anleitung dar.
        </p>

        <div
          class="bg-boundaries/10 border border-boundaries/30 rounded-lg p-4 mb-4"
        >
          <p class="text-smoke-300 text-sm">
            <strong class="text-boundaries">Altersfreigabe:</strong>
            Diese Website enthält Inhalte, die ausschließlich für Erwachsene (18+)
            bestimmt sind. Die Inhalte behandeln Themen der erotischen Hypnose und
            verwandter Bereiche.
          </p>
        </div>

        <div class="bg-caution/10 border border-caution/30 rounded-lg p-4">
          <p class="text-smoke-300 text-sm">
            <strong class="text-caution">Gesundheitshinweis:</strong>
            Hypnose sollte nur von qualifizierten Fachkräften durchgeführt werden.
            Bei gesundheitlichen Problemen wende dich an einen Arzt oder Therapeuten.
            Unsere Community-Events ersetzen keine professionelle medizinische oder
            therapeutische Behandlung.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact for Legal Issues -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Rechtliche Anfragen
    </h2>

    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Für rechtliche Anfragen, Beschwerden oder Hinweise auf
        Rechtsverletzungen kontaktiere mich bitte unter:
      </p>

      <div class="space-y-2 text-smoke-300 text-sm">
        <p>
          <strong>E-Mail:</strong>
          <a
            href="mailto:legal@hypnose-stammtisch.de"
            class="text-accent-400 hover:text-accent-300"
            >legal@hypnose-stammtisch.de</a
          >
        </p>
        <!--
          ============================================
          PLATZHALTER - BITTE AUSFÜLLEN
          ============================================
        -->
        <p>
          <strong>Post:</strong> Jonas Pfalzgraf, Postfach 7222, 22846 Norderstedt
        </p>
      </div>

      <p class="text-smoke-400 text-xs mt-4">
        Bitte gib bei rechtlichen Anfragen immer die betroffene URL und eine
        detaillierte Beschreibung des Problems an.
      </p>
    </div>
  </section>

  <!-- Related Pages -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Verwandte Seiten
    </h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <a
        href="/privacy"
        use:link
        class="card hover:border-accent-400/50 transition-colors group"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-2xl" aria-hidden="true">🔐</span>
          </div>
          <div>
            <h3
              class="font-semibold text-smoke-50 group-hover:text-accent-400 transition-colors"
            >
              Datenschutz
            </h3>
            <p class="text-smoke-400 text-sm">Datenschutzerklärung</p>
          </div>
        </div>
      </a>
      <a
        href="/terms"
        use:link
        class="card hover:border-accent-400/50 transition-colors group"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-2xl" aria-hidden="true">📜</span>
          </div>
          <div>
            <h3
              class="font-semibold text-smoke-50 group-hover:text-accent-400 transition-colors"
            >
              Nutzungsbedingungen
            </h3>
            <p class="text-smoke-400 text-sm">AGB der Plattform</p>
          </div>
        </div>
      </a>
      <a
        href="/cookies"
        use:link
        class="card hover:border-accent-400/50 transition-colors group"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-2xl" aria-hidden="true">🍪</span>
          </div>
          <div>
            <h3
              class="font-semibold text-smoke-50 group-hover:text-accent-400 transition-colors"
            >
              Cookies
            </h3>
            <p class="text-smoke-400 text-sm">Cookie-Richtlinie</p>
          </div>
        </div>
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center">
    <div class="text-xs text-smoke-500 space-y-1">
      <p>Stand: 28. Dezember 2024</p>
      <p>
        Dieses Impressum wurde nach den Anforderungen des § 5 DDG und § 18 MStV
        erstellt.
      </p>
    </div>
  </footer>
</div>

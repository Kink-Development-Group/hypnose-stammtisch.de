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
    <p class="text-xl text-smoke-300">Rechtliche Informationen gemäß § 5 TMG</p>
  </header>

  <!-- Responsible Party -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Angaben gemäß § 5 TMG
    </h2>

    <div class="card">
      <div class="space-y-6">
        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">
            Verantwortlich für den Inhalt:
          </h3>
          <div class="text-smoke-300 space-y-1">
            <p class="font-medium">Hypnose Stammtisch Community</p>
            <p>Max Mustermann (Community-Manager)</p>
            <p>Musterstraße 123</p>
            <p>12345 Musterstadt</p>
            <p>Deutschland</p>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">Kontakt:</h3>
          <div class="text-smoke-300 space-y-1">
            <p>Telefon: +49 (0) 123 456789</p>
            <p>
              E-Mail: <a
                href="mailto:info@hypnose-stammtisch.de"
                class="text-accent-400 hover:text-accent-300"
                >info@hypnose-stammtisch.de</a
              >
            </p>
            <p>
              Website: <a
                href="https://hypnose-stammtisch.de"
                class="text-accent-400 hover:text-accent-300"
                >hypnose-stammtisch.de</a
              >
            </p>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">
            Rechtliche Form:
          </h3>
          <p class="text-smoke-300">
            Eingetragener Verein (e.V.) • Vereinsregister: VR 12345 •
            Amtsgericht Musterstadt
          </p>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-3">
            Vertretungsberechtigter Vorstand:
          </h3>
          <div class="text-smoke-300 space-y-1">
            <p>1. Vorsitzende: Max Mustermann</p>
            <p>2. Vorsitzende: Maria Musterfrau</p>
            <p>Kassenwart: Dr. Hans Beispiel</p>
          </div>
        </div>
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
        Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren
        vor einer Verbraucherschlichtungsstelle teilzunehmen.
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
          Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte
          auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach
          §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht unter der
          Verpflichtung, übermittelte oder gespeicherte fremde Informationen zu
          überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige
          Tätigkeit hinweisen.
        </p>

        <p class="text-smoke-300 mb-4">
          Verpflichtungen zur Entfernung oder Sperrung der Nutzung von
          Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt.
          Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der
          Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden
          von entsprechenden Rechtsverletzungen werden wir diese Inhalte
          umgehend entfernen.
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
          Unser Angebot enthält Links zu externen Websites Dritter, auf deren
          Inhalte wir keinen Einfluss haben. Deshalb können wir für diese
          fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der
          verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der
          Seiten verantwortlich.
        </p>

        <p class="text-smoke-300 mb-4">
          Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche
          Rechtsverstöße überprüft. Rechtswidrige Inhalte waren zum Zeitpunkt
          der Verlinkung nicht erkennbar. Eine permanente inhaltliche Kontrolle
          der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer
          Rechtsverletzung nicht zumutbar. Bei Bekanntwerden von
          Rechtsverletzungen werden wir derartige Links umgehend entfernen.
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
          Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen
          Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung,
          Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der
          Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des
          jeweiligen Autors bzw. Erstellers.
        </p>

        <p class="text-smoke-300 mb-4">
          Downloads und Kopien dieser Seite sind nur für den privaten, nicht
          kommerziellen Gebrauch gestattet. Soweit die Inhalte auf dieser Seite
          nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter
          beachtet. Insbesondere werden Inhalte Dritter als solche
          gekennzeichnet.
        </p>

        <p class="text-smoke-300">
          Sollten Sie trotzdem auf eine Urheberrechtsverletzung aufmerksam
          werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden
          von Rechtsverletzungen werden wir derartige Inhalte umgehend
          entfernen.
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
          <p><strong>Framework:</strong> Svelte + TypeScript</p>
          <p><strong>Styling:</strong> Tailwind CSS</p>
          <p><strong>Fonts:</strong> Google Fonts (Playfair Display, Inter)</p>
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
          unsere Community und Events. Sie stellen keine medizinische Beratung
          oder therapeutische Anleitung dar.
        </p>

        <div class="bg-caution/10 border border-caution/30 rounded-lg p-4">
          <p class="text-smoke-300 text-sm">
            <strong class="text-caution">Wichtiger Hinweis:</strong>
            Hypnose sollte nur von qualifizierten Fachkräften durchgeführt werden.
            Bei gesundheitlichen Problemen wenden Sie sich an einen Arzt oder Therapeuten.
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
        Rechtsverletzungen kontaktieren Sie uns bitte unter:
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
        <p>
          <strong>Post:</strong> Hypnose Stammtisch Community, Rechtsabteilung, Musterstraße
          123, 12345 Musterstadt
        </p>
      </div>

      <p class="text-smoke-400 text-xs mt-4">
        Bitte geben Sie bei rechtlichen Anfragen immer die betroffene URL und
        eine detaillierte Beschreibung des Problems an.
      </p>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/privacy" use:link class="btn btn-outline">
          Datenschutzerklärung
        </a>
        <a href="/code-of-conduct" use:link class="btn btn-outline">
          Verhaltenskodex
        </a>
        <a href="/contact" use:link class="btn btn-outline"> Kontakt </a>
      </div>

      <div class="text-xs text-smoke-500 space-y-1">
        <p>Stand: 15. Januar 2025</p>
        <p>
          Dieses Impressum wurde sorgfältig erstellt und regelmäßig überprüft.
        </p>
      </div>
    </div>
  </footer>
</div>


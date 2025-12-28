<script lang="ts">
  import { link } from "svelte-spa-router";
  import { complianceStore } from "../stores/compliance";
</script>

<svelte:head>
  <title>Cookie-Richtlinie - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Informationen über die Verwendung von Cookies auf unserer Website gemäß DSGVO und ePrivacy-Richtlinie."
  />
</svelte:head>

<main class="container mx-auto px-4 py-8 max-w-4xl">
  <!-- Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Cookie-Richtlinie
    </h1>
    <p class="text-xl text-smoke-300">
      Transparenz über die Verwendung von Cookies auf unserer Website
    </p>
    <div class="mt-6 text-sm text-smoke-400">
      Gültig seit: 28. Dezember 2024 • Letzte Aktualisierung: 28. Dezember 2024
    </div>
  </header>

  <!-- Quick Actions -->
  <section class="mb-12">
    <div class="card bg-accent-400/10 border-accent-400/30">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-smoke-50 mb-1">
            Cookie-Einstellungen verwalten
          </h2>
          <p class="text-smoke-300 text-sm">
            Du kannst deine Cookie-Präferenzen jederzeit anpassen.
          </p>
        </div>
        <button
          type="button"
          on:click={() => complianceStore.openSettings()}
          class="px-6 py-3 rounded-lg bg-accent-500 text-charcoal-900 font-semibold hover:bg-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-800 transition-all duration-200 whitespace-nowrap"
        >
          Einstellungen öffnen
        </button>
      </div>
    </div>
  </section>

  <!-- Introduction -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      1. Was sind Cookies?
    </h2>
    <div class="card">
      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 mb-4">
          Cookies sind kleine Textdateien, die von Websites auf deinem Gerät
          (Computer, Tablet oder Smartphone) gespeichert werden. Sie dienen
          dazu, dein Nutzererlebnis zu verbessern, indem sie beispielsweise
          deine Einstellungen speichern oder anonyme Statistiken über die
          Websitenutzung erfassen.
        </p>
        <p class="text-smoke-300">
          Cookies selbst sind harmlos und enthalten keine Viren oder Malware.
          Sie können jedoch personenbezogene Daten enthalten, weshalb wir deine
          Einwilligung benötigen, bevor wir nicht-essenzielle Cookies setzen.
        </p>
      </div>
    </div>
  </section>

  <!-- Legal Basis -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      2. Rechtsgrundlage
    </h2>
    <div class="card">
      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 mb-4">
          Unsere Verwendung von Cookies basiert auf den folgenden rechtlichen
          Grundlagen:
        </p>
        <ul class="space-y-2 text-smoke-300">
          <li class="flex items-start gap-2">
            <span class="text-accent-400">•</span>
            <span>
              <strong class="text-smoke-100">EU-DSGVO</strong> (Datenschutz-Grundverordnung)
              – Art. 6 Abs. 1 lit. a (Einwilligung) und Art. 6 Abs. 1 lit. f (berechtigtes
              Interesse für essenzielle Cookies)
            </span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-accent-400">•</span>
            <span>
              <strong class="text-smoke-100">ePrivacy-Richtlinie</strong>
              (2002/58/EG, geändert durch 2009/136/EG) – Regelungen zur Einwilligung
              bei Cookies
            </span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-accent-400">•</span>
            <span>
              <strong class="text-smoke-100">TDDDG</strong>
              (Telekommunikation-Digitale-Dienste-Datenschutz-Gesetz) – Deutsche Umsetzung
              der ePrivacy-Richtlinie
            </span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Cookie Categories -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      3. Cookie-Kategorien
    </h2>

    <div class="space-y-6">
      <!-- Essential Cookies -->
      <div class="card border-l-4 border-l-consent">
        <div class="flex items-start gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-consent/20 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-2xl" aria-hidden="true">🔒</span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h3 class="text-lg font-semibold text-smoke-50">
                Essenzielle Cookies
              </h3>
              <span
                class="px-2 py-0.5 text-xs rounded-full bg-consent/20 text-consent"
              >
                Immer aktiv
              </span>
            </div>
            <p class="text-smoke-300 text-sm mb-4">
              Diese Cookies sind für den Betrieb der Website unbedingt
              erforderlich und können nicht deaktiviert werden. Sie werden nur
              als Reaktion auf von dir vorgenommene Aktionen gesetzt.
            </p>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-charcoal-600">
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Cookie</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Zweck</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Dauer</th
                    >
                  </tr>
                </thead>
                <tbody class="text-smoke-400">
                  <tr class="border-b border-charcoal-700">
                    <td class="py-2 font-mono text-xs">cookie_consent</td>
                    <td class="py-2">Speichert deine Cookie-Einstellungen</td>
                    <td class="py-2">1 Jahr</td>
                  </tr>
                  <tr class="border-b border-charcoal-700">
                    <td class="py-2 font-mono text-xs">age_verified</td>
                    <td class="py-2">Speichert die Altersbestätigung</td>
                    <td class="py-2">1 Jahr</td>
                  </tr>
                  <tr class="border-b border-charcoal-700">
                    <td class="py-2 font-mono text-xs">PHPSESSID</td>
                    <td class="py-2">Session-ID für Backend-Anfragen</td>
                    <td class="py-2">Session</td>
                  </tr>
                  <tr>
                    <td class="py-2 font-mono text-xs">csrf_token</td>
                    <td class="py-2">Schutz gegen Cross-Site-Request-Forgery</td
                    >
                    <td class="py-2">Session</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Preference Cookies -->
      <div class="card border-l-4 border-l-accent-400">
        <div class="flex items-start gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-accent-400/20 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-2xl" aria-hidden="true">⚙️</span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h3 class="text-lg font-semibold text-smoke-50">
                Präferenz-Cookies
              </h3>
              <span
                class="px-2 py-0.5 text-xs rounded-full bg-accent-400/20 text-accent-400"
              >
                Optional
              </span>
            </div>
            <p class="text-smoke-300 text-sm mb-4">
              Diese Cookies ermöglichen es der Website, sich an Entscheidungen
              zu erinnern, die du getroffen hast, um dir ein verbessertes,
              personalisiertes Erlebnis zu bieten.
            </p>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-charcoal-600">
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Cookie</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Zweck</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Dauer</th
                    >
                  </tr>
                </thead>
                <tbody class="text-smoke-400">
                  <tr>
                    <td class="py-2 font-mono text-xs">theme_preference</td>
                    <td class="py-2">Speichert bevorzugtes Farbschema</td>
                    <td class="py-2">1 Jahr</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="text-smoke-500 text-xs mt-3">
              Derzeit werden keine Präferenz-Cookies aktiv gesetzt. Diese
              Kategorie ist für zukünftige Funktionen reserviert.
            </p>
          </div>
        </div>
      </div>

      <!-- Statistics Cookies -->
      <div class="card border-l-4 border-l-blue-400">
        <div class="flex items-start gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-blue-400/20 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-2xl" aria-hidden="true">📊</span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h3 class="text-lg font-semibold text-smoke-50">
                Statistik-Cookies
              </h3>
              <span
                class="px-2 py-0.5 text-xs rounded-full bg-blue-400/20 text-blue-400"
              >
                Optional
              </span>
            </div>
            <p class="text-smoke-300 text-sm mb-4">
              Diese Cookies helfen uns zu verstehen, wie Besucher mit der
              Website interagieren, indem sie Informationen anonym sammeln und
              melden. Dies hilft uns, unser Angebot zu verbessern.
            </p>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-charcoal-600">
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Cookie</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Anbieter</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Zweck</th
                    >
                    <th class="text-left py-2 text-smoke-200 font-medium"
                      >Dauer</th
                    >
                  </tr>
                </thead>
                <tbody class="text-smoke-400">
                  <tr class="border-b border-charcoal-700">
                    <td class="py-2 font-mono text-xs">_ga</td>
                    <td class="py-2">Google Analytics</td>
                    <td class="py-2">Unterscheidet Besucher</td>
                    <td class="py-2">2 Jahre</td>
                  </tr>
                  <tr class="border-b border-charcoal-700">
                    <td class="py-2 font-mono text-xs">_ga_*</td>
                    <td class="py-2">Google Analytics</td>
                    <td class="py-2">Session-Status</td>
                    <td class="py-2">2 Jahre</td>
                  </tr>
                  <tr>
                    <td class="py-2 font-mono text-xs">_gid</td>
                    <td class="py-2">Google Analytics</td>
                    <td class="py-2">Unterscheidet Besucher</td>
                    <td class="py-2">24 Stunden</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="text-smoke-500 text-xs mt-3">
              <strong>Hinweis:</strong> Google Analytics ist geplant, aber derzeit
              noch nicht aktiv. Wir werden diese Richtlinie aktualisieren, sobald
              es implementiert wird.
            </p>
          </div>
        </div>
      </div>

      <!-- Marketing Cookies -->
      <div class="card border-l-4 border-l-purple-400">
        <div class="flex items-start gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-purple-400/20 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-2xl" aria-hidden="true">📢</span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h3 class="text-lg font-semibold text-smoke-50">
                Marketing-Cookies
              </h3>
              <span
                class="px-2 py-0.5 text-xs rounded-full bg-purple-400/20 text-purple-400"
              >
                Optional
              </span>
            </div>
            <p class="text-smoke-300 text-sm mb-4">
              Diese Cookies werden verwendet, um Besuchern relevante Werbung
              anzuzeigen. Sie verfolgen Besucher über Websites hinweg und
              sammeln Informationen für personalisierte Werbung.
            </p>
            <div
              class="p-4 rounded-lg bg-charcoal-700/50 border border-charcoal-600"
            >
              <p class="text-smoke-400 text-sm">
                🎉 <strong>Gute Nachrichten:</strong> Wir verwenden derzeit keine
                Marketing-Cookies. Deine Daten werden nicht für Werbezwecke verwendet
                oder an Werbetreibende weitergegeben.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Your Rights -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      4. Deine Rechte
    </h2>
    <div class="card">
      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 mb-4">
          Du hast folgende Rechte in Bezug auf Cookies und deine Daten:
        </p>
        <ul class="space-y-3 text-smoke-300">
          <li class="flex items-start gap-2">
            <span class="text-accent-400 font-bold">✓</span>
            <span>
              <strong class="text-smoke-100">Einwilligung widerrufen:</strong>
              Du kannst deine Cookie-Einstellungen jederzeit über die Schaltfläche
              oben ändern.
            </span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-accent-400 font-bold">✓</span>
            <span>
              <strong class="text-smoke-100">Cookies löschen:</strong>
              Du kannst alle Cookies in deinen Browser-Einstellungen löschen.
            </span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-accent-400 font-bold">✓</span>
            <span>
              <strong class="text-smoke-100">Cookies blockieren:</strong>
              Du kannst deinen Browser so einstellen, dass alle Cookies blockiert
              werden.
            </span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-accent-400 font-bold">✓</span>
            <span>
              <strong class="text-smoke-100">Auskunft verlangen:</strong>
              Du kannst Auskunft über die über dich gespeicherten Daten verlangen.
            </span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Browser Settings -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      5. Browser-Einstellungen
    </h2>
    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Du kannst Cookies auch über deine Browser-Einstellungen verwalten. Hier
        findest du Anleitungen für die gängigsten Browser:
      </p>
      <div class="grid gap-3 sm:grid-cols-2">
        <a
          href="https://support.google.com/chrome/answer/95647"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 p-3 rounded-lg bg-charcoal-700/50 border border-charcoal-600 hover:border-accent-400/50 transition-colors"
        >
          <span class="text-xl">🌐</span>
          <span class="text-smoke-200">Google Chrome</span>
        </a>
        <a
          href="https://support.mozilla.org/de/kb/cookies-erlauben-und-ablehnen"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 p-3 rounded-lg bg-charcoal-700/50 border border-charcoal-600 hover:border-accent-400/50 transition-colors"
        >
          <span class="text-xl">🦊</span>
          <span class="text-smoke-200">Mozilla Firefox</span>
        </a>
        <a
          href="https://support.apple.com/de-de/guide/safari/sfri11471/mac"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 p-3 rounded-lg bg-charcoal-700/50 border border-charcoal-600 hover:border-accent-400/50 transition-colors"
        >
          <span class="text-xl">🧭</span>
          <span class="text-smoke-200">Apple Safari</span>
        </a>
        <a
          href="https://support.microsoft.com/de-de/microsoft-edge/cookies-in-microsoft-edge-löschen-63947406-40ac-c3b8-57b9-2a946a29ae09"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 p-3 rounded-lg bg-charcoal-700/50 border border-charcoal-600 hover:border-accent-400/50 transition-colors"
        >
          <span class="text-xl">🔷</span>
          <span class="text-smoke-200">Microsoft Edge</span>
        </a>
      </div>
      <p class="text-smoke-400 text-xs mt-4">
        <strong>Hinweis:</strong> Das Blockieren aller Cookies kann dazu führen, dass
        einige Funktionen der Website nicht mehr ordnungsgemäß funktionieren.
      </p>
    </div>
  </section>

  <!-- Contact -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      6. Kontakt
    </h2>
    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Bei Fragen zu dieser Cookie-Richtlinie oder zur Datenverarbeitung kannst
        du uns jederzeit kontaktieren:
      </p>
      <div class="space-y-2 text-smoke-300 text-sm">
        <p>
          <strong>E-Mail:</strong>
          <a
            href="mailto:privacy@hypnose-stammtisch.de"
            class="text-accent-400 hover:text-accent-300"
            >privacy@hypnose-stammtisch.de</a
          >
        </p>
      </div>
    </div>
  </section>

  <!-- Related Links -->
  <section>
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Verwandte Seiten
    </h2>
    <div class="grid gap-4 sm:grid-cols-2">
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
              Datenschutzerklärung
            </h3>
            <p class="text-smoke-400 text-sm">
              Vollständige Informationen zum Datenschutz
            </p>
          </div>
        </div>
      </a>
      <a
        href="/imprint"
        use:link
        class="card hover:border-accent-400/50 transition-colors group"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-lg bg-accent-400/20 flex items-center justify-center"
          >
            <span class="text-2xl" aria-hidden="true">📋</span>
          </div>
          <div>
            <h3
              class="font-semibold text-smoke-50 group-hover:text-accent-400 transition-colors"
            >
              Impressum
            </h3>
            <p class="text-smoke-400 text-sm">
              Rechtliche Angaben zum Betreiber
            </p>
          </div>
        </div>
      </a>
    </div>
  </section>
</main>

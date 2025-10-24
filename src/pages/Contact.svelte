<script lang="ts">
  import { link } from "svelte-spa-router";
  import ContactForm from "../components/forms/ContactForm.svelte";
</script>

<svelte:head>
  <title>Kontakt - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Kontaktieren Sie uns für Fragen zu Hypnose-Events, Kooperationen oder allgemeine Anfragen. Wir freuen uns auf Ihre Nachricht!"
  />
</svelte:head>

<section
  class="min-h-screen bg-gradient-to-br from-charcoal-900 via-charcoal-800 to-charcoal-900 relative overflow-hidden"
>
  <!-- Background decoration -->
  <div class="absolute inset-0 opacity-5">
    <div
      class="absolute top-20 left-20 w-40 h-40 bg-accent-400 rounded-full blur-3xl"
    ></div>
    <div
      class="absolute bottom-32 right-16 w-56 h-56 bg-secondary-400 rounded-full blur-3xl"
    ></div>
    <div
      class="absolute top-1/3 right-1/4 w-32 h-32 bg-primary-400 rounded-full blur-2xl"
    ></div>
  </div>

  <div class="container mx-auto px-4 py-20 relative z-10">
    <!-- Header -->
    <header class="text-center mb-16">
      <div
        class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-accent-400 to-secondary-400 rounded-full mb-6"
      >
        <span class="text-2xl">💬</span>
      </div>
      <h1
        class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-6"
      >
        Kontakt aufnehmen
      </h1>
      <p class="text-xl text-smoke-200 max-w-3xl mx-auto leading-relaxed">
        Haben Sie Fragen zu unseren Events, möchten Sie eine Veranstaltung
        organisieren oder haben andere Anliegen? Wir freuen uns auf Ihre
        Nachricht und melden uns schnellstmöglich bei Ihnen.
      </p>
    </header>

    <!-- Main Content Grid -->
    <div class="max-w-6xl mx-auto">
      <div class="grid gap-8 lg:grid-cols-3">
        <!-- Contact Information & Quick Actions -->
        <div class="lg:col-span-1">
          <!-- Response & Communication Info -->
          <div
            class="card bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600 mb-8"
          >
            <div class="flex items-center mb-6">
              <div
                class="w-12 h-12 bg-gradient-to-br from-accent-400 to-accent-500 rounded-lg flex items-center justify-center mr-4"
              >
                <span class="text-xl">⚡</span>
              </div>
              <h2 class="text-2xl font-display font-semibold text-smoke-50">
                Schnelle Antwort
              </h2>
            </div>

            <div class="space-y-6">
              <!-- Response Time -->
              <div class="flex items-start group">
                <div
                  class="w-10 h-10 bg-gradient-to-br from-primary-400/20 to-primary-500/20 rounded-full flex items-center justify-center mr-4 mt-1 group-hover:from-primary-400/40 group-hover:to-primary-500/40 transition-all duration-300"
                >
                  <span class="text-primary-400 text-lg">🕒</span>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-primary-400 mb-2">
                    24-48 Stunden
                  </h3>
                  <p class="text-smoke-300 text-sm leading-relaxed">
                    Wir antworten normalerweise innerhalb von 24-48 Stunden auf
                    Ihre Anfrage.
                  </p>
                </div>
              </div>

              <!-- Direct Contact -->
              <div class="flex items-start group">
                <div
                  class="w-10 h-10 bg-gradient-to-br from-accent-400/20 to-accent-500/20 rounded-full flex items-center justify-center mr-4 mt-1 group-hover:from-accent-400/40 group-hover:to-accent-500/40 transition-all duration-300"
                >
                  <span class="text-accent-400 text-lg">📧</span>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-accent-400 mb-2">
                    Direkter Kontakt
                  </h3>
                  <p class="text-smoke-300 text-sm leading-relaxed mb-2">
                    Für dringende Anfragen:
                  </p>
                  <a
                    href="mailto:info@hypnose-stammtisch.de"
                    class="text-accent-400 hover:text-accent-300 transition-colors text-sm font-medium"
                  >
                    info@hypnose-stammtisch.de
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Common Topics -->
          <div
            class="card bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600 mb-8"
          >
            <div class="flex items-center mb-6">
              <div
                class="w-12 h-12 bg-gradient-to-br from-secondary-400 to-secondary-500 rounded-lg flex items-center justify-center mr-4"
              >
                <span class="text-xl">📋</span>
              </div>
              <h2 class="text-2xl font-display font-semibold text-smoke-50">
                Häufige Anfragen
              </h2>
            </div>

            <div class="space-y-4">
              {#each [{ icon: "🎯", title: "Event-Teilnahme", description: "Anmeldung und Fragen zu kommenden Events" }, { icon: "🎪", title: "Event-Organisation", description: "Eigene Events planen und durchführen" }, { icon: "🤝", title: "Kooperationen", description: "Partnerschaften und Zusammenarbeit" }, { icon: "💡", title: "Feedback", description: "Verbesserungen und Anregungen" }, { icon: "🛠️", title: "Support", description: "Technische Hilfe und Plattform-Fragen" }] as topic (topic.title)}
                <div
                  class="flex items-center p-3 rounded-lg bg-charcoal-700/50 hover:bg-charcoal-600/50 transition-colors duration-300 group"
                >
                  <span class="text-2xl mr-3">{topic.icon}</span>
                  <div>
                    <h4
                      class="font-semibold text-smoke-50 text-sm group-hover:text-accent-400 transition-colors"
                    >
                      {topic.title}
                    </h4>
                    <p class="text-smoke-400 text-xs">{topic.description}</p>
                  </div>
                </div>
              {/each}
            </div>
          </div>

          <!-- Community Guidelines -->
          <div
            class="card bg-gradient-to-br from-consent/20 to-consent-light/20 border-consent/30"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-10 h-10 bg-gradient-to-br from-consent/30 to-consent-light/30 rounded-lg flex items-center justify-center mr-3"
              >
                <span class="text-consent text-lg">🛡️</span>
              </div>
              <h3 class="text-lg font-semibold text-consent">
                Verhaltenskodex
              </h3>
            </div>
            <p class="text-smoke-300 text-sm leading-relaxed mb-4">
              Bei Verstößen gegen unseren Verhaltenskodex können Sie uns
              vertraulich kontaktieren.
            </p>
            <a href="/code-of-conduct" use:link class="btn btn-outline text-sm">
              Kodex lesen
            </a>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-2">
          <div
            class="card bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600"
          >
            <div class="flex items-center mb-8">
              <div
                class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-500 rounded-lg flex items-center justify-center mr-4"
              >
                <span class="text-xl">✉️</span>
              </div>
              <h2 class="text-2xl font-display font-semibold text-smoke-50">
                Nachricht senden
              </h2>
            </div>
            <ContactForm />
          </div>
        </div>
      </div>
    </div>

    <!-- Security & Privacy Section -->
    <section class="mt-16 max-w-5xl mx-auto">
      <div
        class="card bg-gradient-to-r from-charcoal-800 via-charcoal-700 to-charcoal-800 border-charcoal-600 relative overflow-hidden"
      >
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-5">
          <div
            class="absolute top-0 left-0 w-full h-full"
            style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"
          ></div>
        </div>

        <div class="relative z-10">
          <div class="text-center mb-8">
            <div
              class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-consent to-consent-light rounded-full mb-4"
            >
              <span class="text-2xl">🔒</span>
            </div>
            <h2 class="text-2xl font-display font-bold text-smoke-50 mb-4">
              Datenschutz & Sicherheit
            </h2>
            <p class="text-smoke-300 max-w-2xl mx-auto">
              Ihre Privatsphäre und Datensicherheit haben für uns höchste
              Priorität.
            </p>
          </div>

          <div class="grid gap-6 md:grid-cols-3 mb-8">
            <div class="text-center group">
              <div
                class="w-16 h-16 bg-gradient-to-br from-consent/20 to-consent-light/20 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:from-consent/30 group-hover:to-consent-light/30 transition-all duration-300"
              >
                <span class="text-consent text-2xl">🛡️</span>
              </div>
              <h3 class="font-semibold text-smoke-50 mb-3">DSGVO-konform</h3>
              <p class="text-sm text-smoke-400 leading-relaxed">
                Alle übermittelten Daten werden verschlüsselt übertragen und
                entsprechend der DSGVO verarbeitet.
              </p>
            </div>

            <div class="text-center group">
              <div
                class="w-16 h-16 bg-gradient-to-br from-consent/20 to-consent-light/20 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:from-consent/30 group-hover:to-consent-light/30 transition-all duration-300"
              >
                <span class="text-consent text-2xl">🎯</span>
              </div>
              <h3 class="font-semibold text-smoke-50 mb-3">
                Zweckgebundene Nutzung
              </h3>
              <p class="text-sm text-smoke-400 leading-relaxed">
                Ihre Kontaktdaten verwenden wir ausschließlich zur Bearbeitung
                Ihrer Anfrage.
              </p>
            </div>

            <div class="text-center group">
              <div
                class="w-16 h-16 bg-gradient-to-br from-consent/20 to-consent-light/20 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:from-consent/30 group-hover:to-consent-light/30 transition-all duration-300"
              >
                <span class="text-consent text-2xl">🚫</span>
              </div>
              <h3 class="font-semibold text-smoke-50 mb-3">Spam-Schutz</h3>
              <p class="text-sm text-smoke-400 leading-relaxed">
                Automatisierter Spam-Schutz sorgt dafür, dass nur echte
                Nachrichten bearbeitet werden.
              </p>
            </div>
          </div>

          <div
            class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-6 border-t border-charcoal-600"
          >
            <p class="text-sm text-smoke-400 text-center sm:text-left">
              Weitere Informationen in unserer:
            </p>
            <div class="flex flex-col sm:flex-row gap-3">
              <a href="/privacy" use:link class="btn btn-outline-light text-sm">
                Datenschutzerklärung
              </a>
              <a href="/imprint" use:link class="btn btn-outline-light text-sm">
                Impressum
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</section>

<script lang="ts">
  import ContactForm from "../components/forms/ContactForm.svelte";
</script>

<svelte:head>
  <title>Kontakt - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Kontaktieren Sie uns für Fragen zu Hypnose-Events, Kooperationen oder allgemeine Anfragen. Wir freuen uns auf Ihre Nachricht!"
  />
</svelte:head>

<div
  class="min-h-screen bg-gradient-to-br from-charcoal-900 via-charcoal-800 to-charcoal-900"
>
  <div class="container mx-auto px-4 py-16">
    <!-- Header -->
    <header class="text-center mb-16">
      <h1
        class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-6"
      >
        Kontakt aufnehmen
      </h1>
      <p class="text-xl text-smoke-300 max-w-3xl mx-auto leading-relaxed">
        Haben Sie Fragen zu unseren Events, möchten Sie eine Veranstaltung
        organisieren oder haben andere Anliegen? Wir freuen uns auf Ihre
        Nachricht und melden uns schnellstmöglich bei Ihnen.
      </p>
    </header>

    <div class="max-w-4xl mx-auto">
      <div class="grid gap-12 lg:grid-cols-3">
        <!-- Contact Information -->
        <div class="lg:col-span-1">
          <div
            class="bg-charcoal-800/50 backdrop-blur-sm border border-charcoal-600 rounded-xl p-8"
          >
            <h2 class="text-2xl font-display font-bold text-smoke-50 mb-8">
              Weitere Kontaktmöglichkeiten
            </h2>

            <!-- Response Time -->
            <div class="mb-8">
              <h3 class="text-lg font-semibold text-accent-400 mb-3">
                <svg
                  class="w-5 h-5 inline mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Antwortzeit
              </h3>
              <p class="text-smoke-300">
                Wir antworten normalerweise innerhalb von 24-48 Stunden auf Ihre
                Anfrage.
              </p>
            </div>

            <!-- Topics -->
            <div class="mb-8">
              <h3 class="text-lg font-semibold text-accent-400 mb-3">
                <svg
                  class="w-5 h-5 inline mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Häufige Anfragen
              </h3>
              <ul class="space-y-2 text-smoke-300">
                <li>• Teilnahme an Events</li>
                <li>• Event-Organisation</li>
                <li>• Kooperationen</li>
                <li>• Technischer Support</li>
                <li>• Feedback & Verbesserungen</li>
              </ul>
            </div>

            <!-- Community Guidelines -->
            <div>
              <h3 class="text-lg font-semibold text-accent-400 mb-3">
                <svg
                  class="w-5 h-5 inline mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                  />
                </svg>
                Verhaltenskodex
              </h3>
              <p class="text-smoke-300">
                Bei Verstößen gegen unseren <a
                  href="/code-of-conduct"
                  class="text-accent-400 hover:text-accent-300 underline"
                  >Verhaltenskodex</a
                >
                können Sie uns vertraulich kontaktieren.
              </p>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-2">
          <div
            class="bg-charcoal-800/50 backdrop-blur-sm border border-charcoal-600 rounded-xl p-8"
          >
            <ContactForm />
          </div>
        </div>
      </div>
    </div>

    <!-- Additional Information -->
    <section class="mt-16 max-w-4xl mx-auto">
      <div
        class="bg-charcoal-800/30 backdrop-blur-sm border border-charcoal-600 rounded-xl p-8"
      >
        <h2 class="text-2xl font-display font-bold text-smoke-50 mb-6">
          Datenschutz & Sicherheit
        </h2>

        <div class="grid gap-8 md:grid-cols-2">
          <div>
            <h3 class="text-lg font-semibold text-accent-400 mb-3">
              Ihre Daten sind sicher
            </h3>
            <p class="text-smoke-300 leading-relaxed">
              Alle übermittelten Daten werden verschlüsselt übertragen und
              entsprechend der DSGVO verarbeitet. Ihre Kontaktdaten verwenden
              wir ausschließlich zur Bearbeitung Ihrer Anfrage.
            </p>
          </div>

          <div>
            <h3 class="text-lg font-semibold text-accent-400 mb-3">
              Spam-Schutz
            </h3>
            <p class="text-smoke-300 leading-relaxed">
              Unser Kontaktformular ist durch automatisierten Spam-Schutz
              gesichert. Ihre Nachricht wird nur von echten Personen bearbeitet.
            </p>
          </div>
        </div>

        <div class="mt-8 pt-6 border-t border-charcoal-600">
          <p class="text-sm text-smoke-400">
            Weitere Informationen finden Sie in unserer
            <a
              href="/privacy"
              class="text-accent-400 hover:text-accent-300 underline"
              >Datenschutzerklärung</a
            >
            und unserem
            <a
              href="/imprint"
              class="text-accent-400 hover:text-accent-300 underline"
              >Impressum</a
            >.
          </p>
        </div>
      </div>
    </section>
  </div>
</div>

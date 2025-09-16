<script lang="ts">
  import { link } from "svelte-spa-router";

  const teamMembers = [
    {
      name: "JohnaSwitch",
      role: "Leitung 2. Hypnose Stammtisch Hamburg, Teil des Teams der Hypnose Stammtische in Hamburg",
      location: "Hamburg",
      logo: "",
      description:
        "Organisiert den monatlichen 2. Hypnose Stammtisch Hamburg im BaSeMent und führt Einsteiger-Workshops durch. (wenn es passt xD)",
      specialties: [
        "Einsteiger-Betreuung",
        "Event-Organisation",
        "Community-Building",
      ],
      contact: {
        email: "info@hypnose-stammtisch.de",
        social: "@JohnaSwitch",
      },
    },
  ];

  const platformInfo = {
    mission:
      "Eine zentrale, plattformunabhängige Anlaufstelle für geprüfte Hypnose-Events im deutschsprachigen Raum zu schaffen.",
    values: [
      "Konsens als oberstes Gebot",
      "Professionelle Standards",
      "Sicherheit und Vertrauen",
      "Inklusivität und Respekt",
    ],
    coverage: ["Deutschsprachiger Raum"],
  };
</script>

<section
  class="py-20 bg-gradient-to-br from-charcoal-900 via-charcoal-800 to-charcoal-900 relative overflow-hidden"
>
  <!-- Background decoration -->
  <div class="absolute inset-0 opacity-5">
    <div
      class="absolute top-10 left-10 w-32 h-32 bg-accent-400 rounded-full blur-3xl"
    ></div>
    <div
      class="absolute bottom-20 right-20 w-48 h-48 bg-secondary-400 rounded-full blur-3xl"
    ></div>
    <div
      class="absolute top-1/2 left-1/3 w-24 h-24 bg-primary-400 rounded-full blur-2xl"
    ></div>
  </div>

  <div class="container mx-auto px-4 relative z-10">
    <!-- Section Header -->
    <div class="text-center mb-16">
      <div
        class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-accent-400 to-secondary-400 rounded-full mb-6"
      >
        <span class="text-2xl">🏛️</span>
      </div>
      <h2
        class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-6"
      >
        Über die Plattform
      </h2>
      <p class="text-xl text-smoke-200 max-w-4xl mx-auto leading-relaxed">
        {platformInfo.mission}
      </p>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-16">
      <!-- Platform Values -->
      <div class="xl:col-span-1">
        <div
          class="card h-full bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600"
        >
          <div class="flex items-center mb-6">
            <div
              class="w-12 h-12 bg-gradient-to-br from-accent-400 to-accent-500 rounded-lg flex items-center justify-center mr-4"
            >
              <span class="text-xl">🎯</span>
            </div>
            <h3 class="text-2xl font-display font-semibold text-smoke-50">
              Unsere Werte
            </h3>
          </div>
          <div class="space-y-4">
            {#each platformInfo.values as value, index (index)}
              <div class="flex items-start group">
                <div
                  class="w-8 h-8 bg-gradient-to-br from-accent-400/20 to-accent-500/20 rounded-full flex items-center justify-center mr-4 mt-1 group-hover:from-accent-400/40 group-hover:to-accent-500/40 transition-all duration-300"
                >
                  <span class="text-accent-400 text-sm font-bold"
                    >{index + 1}</span
                  >
                </div>
                <p
                  class="text-smoke-300 group-hover:text-smoke-200 transition-colors duration-300 leading-relaxed"
                >
                  {value}
                </p>
              </div>
            {/each}
          </div>
        </div>
      </div>

      <!-- Geographic Coverage -->
      <div class="xl:col-span-1">
        <div
          class="card h-full bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600"
        >
          <div class="flex items-center mb-6">
            <div
              class="w-12 h-12 bg-gradient-to-br from-secondary-400 to-secondary-500 rounded-lg flex items-center justify-center mr-4"
            >
              <span class="text-xl">🌍</span>
            </div>
            <h3 class="text-2xl font-display font-semibold text-smoke-50">
              Unsere Reichweite
            </h3>
          </div>
          <div class="space-y-4">
            {#each platformInfo.coverage as area (area)}
              <div class="flex items-center group">
                <div
                  class="w-8 h-8 bg-gradient-to-br from-secondary-400/20 to-secondary-500/20 rounded-full flex items-center justify-center mr-4 group-hover:from-secondary-400/40 group-hover:to-secondary-500/40 transition-all duration-300"
                >
                  <span class="text-secondary-400 text-lg">📍</span>
                </div>
                <p
                  class="text-smoke-300 group-hover:text-smoke-200 transition-colors duration-300 font-medium"
                >
                  {area}
                </p>
              </div>
            {/each}
            <div
              class="mt-6 p-4 bg-secondary-500/10 rounded-lg border border-secondary-500/20"
            >
              <p class="text-sm text-smoke-400">
                <strong class="text-secondary-400">Bald mehr:</strong> Wir arbeiten
                an der Expansion in weitere Regionen und freuen uns über lokale Partner!
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats / Quick Info -->
      <div class="xl:col-span-1">
        <div
          class="card h-full bg-gradient-to-br from-primary-900/50 to-primary-800/50 border-primary-600/30"
        >
          <div class="flex items-center mb-6">
            <div
              class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-500 rounded-lg flex items-center justify-center mr-4"
            >
              <span class="text-xl">📊</span>
            </div>
            <h3 class="text-2xl font-display font-semibold text-smoke-50">
              Auf einen Blick
            </h3>
          </div>
          <div class="space-y-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-primary-400 mb-1">2025</div>
              <p class="text-sm text-smoke-400">Plattform gestartet</p>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-accent-400 mb-1">100%</div>
              <p class="text-sm text-smoke-400">Kostenlos & Open Source</p>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-secondary-400 mb-1">
                DSGVO
              </div>
              <p class="text-sm text-smoke-400">Konform & Datenschutz</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Team Section -->
    <div class="mb-16">
      <div class="text-center mb-12">
        <div
          class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-primary-400 to-primary-500 rounded-full mb-4"
        >
          <span class="text-2xl">👥</span>
        </div>
        <h3 class="text-3xl font-display font-bold text-smoke-50 mb-4">
          Team & Organisatoren
        </h3>
        <p class="text-lg text-smoke-300 max-w-2xl mx-auto">
          Lerne die Menschen kennen, die diese Plattform möglich machen und die
          Community leiten.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {#each teamMembers as member (member.name)}
          <div
            class="card bg-gradient-to-br from-charcoal-800 to-charcoal-700 border-charcoal-600 hover:border-accent-500/50 transition-all duration-300 hover:shadow-xl hover:shadow-accent-500/10"
          >
            <div class="flex items-start space-x-6">
              <!-- Avatar -->
              <div class="relative">
                <div
                  class="w-20 h-20 bg-gradient-to-br from-accent-400 to-secondary-400 rounded-full flex items-center justify-center text-2xl font-bold text-charcoal-900 shadow-lg"
                >
                  {member.name.charAt(0)}
                </div>
                <div
                  class="absolute -bottom-1 -right-1 w-6 h-6 bg-primary-400 rounded-full flex items-center justify-center"
                >
                  <span class="text-xs">⭐</span>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1">
                <h4
                  class="text-xl font-display font-semibold text-smoke-50 mb-2"
                >
                  {member.name}
                </h4>
                <p class="text-sm text-accent-400 mb-3 font-medium">
                  {member.role}
                </p>
                <div class="flex items-center text-sm text-smoke-400 mb-3">
                  <span class="mr-2">📍</span>
                  {member.location}
                </div>
                <p class="text-sm text-smoke-300 mb-4 leading-relaxed">
                  {member.description}
                </p>

                <!-- Specialties -->
                <div class="flex flex-wrap gap-2 mb-4">
                  {#each member.specialties as specialty (specialty)}
                    <span class="badge badge-outline-accent text-xs px-3 py-1">
                      {specialty}
                    </span>
                  {/each}
                </div>

                <!-- Contact -->
                <div class="text-xs text-smoke-500 space-y-2">
                  <div class="flex items-center">
                    <span class="mr-2">📧</span>
                    <a
                      href="mailto:{member.contact.email}"
                      class="text-accent-400 hover:text-accent-300 transition-colors"
                    >
                      {member.contact.email}
                    </a>
                  </div>
                  <div class="flex items-center">
                    <span class="mr-2">🐦</span>
                    <span class="text-smoke-400">{member.contact.social}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        {/each}

        <!-- Community Call Card -->
        <div
          class="card bg-gradient-to-br from-accent-500/20 to-secondary-500/20 border-accent-500/30 hover:border-accent-400/50 transition-all duration-300"
        >
          <div class="text-center">
            <div
              class="w-16 h-16 bg-gradient-to-br from-accent-400 to-secondary-400 rounded-full flex items-center justify-center mx-auto mb-4"
            >
              <span class="text-2xl">🚀</span>
            </div>
            <h4 class="text-xl font-display font-semibold text-smoke-50 mb-3">
              Werde Teil des Teams!
            </h4>
            <p class="text-sm text-smoke-300 mb-6 leading-relaxed">
              Du organisierst regelmäßige Hypnose-Events oder möchtest uns bei
              der Plattform-Entwicklung helfen? Wir freuen uns über jeden
              Beitrag zur Community!
            </p>
            <div class="space-y-3">
              <a href="/contact" use:link class="btn btn-primary w-full">
                Kontakt aufnehmen
              </a>
              <p class="text-xs text-smoke-500">
                Alle Hintergründe willkommen • Remote & lokal
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transparency & Trust Section -->
    <div class="text-center">
      <div class="max-w-4xl mx-auto">
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
            <div class="flex items-center justify-center mb-6">
              <div
                class="w-16 h-16 bg-gradient-to-br from-consent to-consent-light rounded-full flex items-center justify-center mr-4"
              >
                <span class="text-2xl">🛡️</span>
              </div>
              <h4 class="text-2xl font-display font-bold text-smoke-50">
                Transparenz & Vertrauen
              </h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <div class="text-center">
                <div
                  class="w-12 h-12 bg-consent/20 rounded-lg flex items-center justify-center mx-auto mb-3"
                >
                  <span class="text-consent text-xl">👁️</span>
                </div>
                <h5 class="font-semibold text-smoke-50 mb-2">
                  Offene Kommunikation
                </h5>
                <p class="text-sm text-smoke-400">
                  Alle Entscheidungen werden transparent kommuniziert
                </p>
              </div>
              <div class="text-center">
                <div
                  class="w-12 h-12 bg-consent/20 rounded-lg flex items-center justify-center mx-auto mb-3"
                >
                  <span class="text-consent text-xl">✅</span>
                </div>
                <h5 class="font-semibold text-smoke-50 mb-2">
                  Geprüfte Events
                </h5>
                <p class="text-sm text-smoke-400">
                  Nur vertrauensvolle Veranstalter werden gelistet
                </p>
              </div>
              <div class="text-center">
                <div
                  class="w-12 h-12 bg-consent/20 rounded-lg flex items-center justify-center mx-auto mb-3"
                >
                  <span class="text-consent text-xl">💬</span>
                </div>
                <h5 class="font-semibold text-smoke-50 mb-2">
                  Direkter Kontakt
                </h5>
                <p class="text-sm text-smoke-400">
                  Bei Fragen stehen wir jederzeit zur Verfügung
                </p>
              </div>
            </div>

            <p class="text-smoke-300 leading-relaxed mb-6">
              Wir schaffen Transparenz, indem wir die Personen hinter dem
              Projekt vorstellen. Vertrauen ist die Basis unserer Community -
              deshalb arbeiten wir offen und ehrlich.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <a href="/code-of-conduct" use:link class="btn btn-outline">
                Verhaltenskodex lesen
              </a>
              <a href="/contact" use:link class="btn btn-primary">
                Fragen stellen
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

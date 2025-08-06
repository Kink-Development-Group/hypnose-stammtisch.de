<script lang="ts">
  import { link } from "svelte-spa-router";

  const teamMembers = [
    {
      name: "JohnaSwitch",
      role: "Leitung Hamburger Hypnose Munch",
      location: "Hamburg",
      description:
        "Organisiert den monatlichen Hypnose Munch im Club Catonium und führt Einsteiger-Workshops durch.",
      specialties: [
        "Einsteiger-Betreuung",
        "Event-Organisation",
        "Community-Building",
      ],
      contact: {
        email: "johna@hypnose-stammtisch.de",
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
    coverage: [
      "Hamburg und Umgebung",
      "Rhein-Main-Gebiet (Mainz, Frankfurt)",
      "Bremen",
      "Weitere deutsche Städte",
    ],
  };
</script>

<section class="py-16 bg-charcoal-900">
  <div class="container mx-auto px-4">
    <!-- Section Header -->
    <div class="text-center mb-12">
      <h2
        class="text-3xl md:text-4xl font-display font-bold text-smoke-50 mb-4"
      >
        Über die Plattform
      </h2>
      <p class="text-lg text-smoke-300 max-w-3xl mx-auto">
        {platformInfo.mission}
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      <!-- Platform Values -->
      <div class="space-y-8">
        <div>
          <h3
            class="text-2xl font-semibold text-smoke-50 mb-4 flex items-center"
          >
            <span class="text-consent mr-3">🎯</span>
            Unsere Werte
          </h3>
          <ul class="space-y-3">
            {#each platformInfo.values as value}
              <li class="flex items-start text-smoke-300">
                <span class="text-accent-400 mr-3 flex-shrink-0 text-lg">•</span
                >
                {value}
              </li>
            {/each}
          </ul>
        </div>

        <div>
          <h3
            class="text-2xl font-semibold text-smoke-50 mb-4 flex items-center"
          >
            <span class="text-secondary-400 mr-3">📍</span>
            Geografische Abdeckung
          </h3>
          <ul class="space-y-3">
            {#each platformInfo.coverage as area}
              <li class="flex items-start text-smoke-300">
                <span class="text-accent-400 mr-3 flex-shrink-0 text-lg">•</span
                >
                {area}
              </li>
            {/each}
          </ul>
        </div>
      </div>

      <!-- Team Section -->
      <div>
        <h3 class="text-2xl font-semibold text-smoke-50 mb-6 flex items-center">
          <span class="text-primary-400 mr-3">👥</span>
          Team & Organisatoren
        </h3>

        {#each teamMembers as member}
          <div class="card mb-6">
            <div class="flex items-start space-x-4">
              <div
                class="w-16 h-16 bg-gradient-to-br from-accent-400 to-secondary-400 rounded-full flex items-center justify-center text-2xl font-bold text-charcoal-900"
              >
                {member.name.charAt(0)}
              </div>
              <div class="flex-1">
                <h4 class="text-lg font-semibold text-smoke-50 mb-1">
                  {member.name}
                </h4>
                <p class="text-sm text-accent-400 mb-2">
                  {member.role}
                </p>
                <p class="text-sm text-smoke-400 mb-1">
                  📍 {member.location}
                </p>
                <p class="text-sm text-smoke-300 mb-3">
                  {member.description}
                </p>

                <!-- Specialties -->
                <div class="flex flex-wrap gap-2 mb-3">
                  {#each member.specialties as specialty}
                    <span class="badge badge-outline text-xs">
                      {specialty}
                    </span>
                  {/each}
                </div>

                <!-- Contact -->
                <div class="text-xs text-smoke-500 space-y-1">
                  <div>📧 {member.contact.email}</div>
                  <div>🐦 {member.contact.social}</div>
                </div>
              </div>
            </div>
          </div>
        {/each}

        <!-- Community Call -->
        <div
          class="card bg-gradient-to-r from-accent-500/10 to-secondary-500/10 border-accent-500/20"
        >
          <h4 class="text-lg font-semibold text-smoke-50 mb-3">
            Werde Teil des Teams!
          </h4>
          <p class="text-sm text-smoke-300 mb-4">
            Du organisierst regelmäßige Hypnose-Events oder möchtest uns bei der
            Plattform-Entwicklung helfen?
          </p>
          <a href="/contact" use:link class="btn btn-sm btn-primary">
            Kontakt aufnehmen
          </a>
        </div>
      </div>
    </div>

    <!-- Transparency Note -->
    <div class="mt-12 text-center">
      <div class="inline-block bg-charcoal-800 rounded-lg p-6 max-w-3xl">
        <h4 class="text-lg font-semibold text-smoke-50 mb-3">
          Transparenz & Vertrauen
        </h4>
        <p class="text-sm text-smoke-300">
          Wir schaffen Transparenz, indem wir die Personen hinter dem Projekt
          vorstellen. Alle Events werden sorgfältig geprüft und nur
          vertrauensvolle Veranstalter werden gelistet. Bei Fragen oder
          Problemen stehen wir jederzeit zur Verfügung.
        </p>
      </div>
    </div>
  </div>
</section>

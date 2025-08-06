<script lang="ts">
  const safetyPrinciples = [
    {
      icon: "🛡️",
      title: "Informiertes Einverständnis",
      description:
        "Alle Hypnose-Aktivitäten erfordern klare, informierte Zustimmung aller Beteiligten.",
      details: [
        "Ausführliche Absprachen vor jeder Session",
        "Jederzeit widerrufbares Einverständnis",
        "Keine Hypnose ohne explizite Zustimmung",
      ],
    },
    {
      icon: "🤝",
      title: "Respektvolle Kommunikation",
      description:
        "Ein respektvoller Umgang miteinander ist die Grundlage unserer Community.",
      details: [
        "Verwendung korrekter Pronomen und Namen",
        "Respekt vor persönlichen Grenzen und Limits",
        "Konstruktive und unterstützende Kommunikation",
      ],
    },
    {
      icon: "🔒",
      title: "Sichere Umgebung",
      description:
        "Wir schaffen geschützte Räume für Experimente und Lernen in der erotischen Hypnose.",
      details: [
        "Erfahrene Moderatoren und Sicherheitspersonen",
        "Klare Safeword-Systeme und Notfallprotokolle",
        "Diskrete Beratung und Beschwerdestelle",
      ],
    },
    {
      icon: "📋",
      title: "Community-Standards",
      description:
        "Professionelle Standards gewährleisten sichere erotische Hypnose-Praktiken.",
      details: [
        "Null-Toleranz für Belästigung und Grenzüberschreitungen",
        "Strikte Vertraulichkeit und Diskretion",
        "Geprüfte Events und verantwortungsvolle Veranstalter",
      ],
    },
  ];
</script>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
  {#each safetyPrinciples as principle}
    <div class="card">
      <div class="flex items-start space-x-4">
        <div class="text-4xl flex-shrink-0" aria-hidden="true">
          {principle.icon}
        </div>
        <div class="flex-1">
          <h3 class="text-xl font-semibold text-smoke-50 mb-3">
            {principle.title}
          </h3>
          <p class="text-smoke-300 mb-4 leading-relaxed">
            {principle.description}
          </p>
          <ul class="space-y-2">
            {#each principle.details as detail}
              <li class="flex items-start text-sm text-smoke-400">
                <svg
                  class="w-4 h-4 text-consent mr-2 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
                {detail}
              </li>
            {/each}
          </ul>
        </div>
      </div>
    </div>
  {/each}
</div>

<!-- Additional safety resources -->
<div class="mt-12 bg-charcoal-700 border border-charcoal-600 rounded-lg p-6">
  <h3 class="text-xl font-semibold text-smoke-50 mb-4 text-center">
    Deine Sicherheit ist unsere Priorität
  </h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
    <div>
      <div class="text-2xl mb-2" aria-hidden="true">📞</div>
      <h4 class="font-semibold text-smoke-50 mb-2">24/7 Hotline</h4>
      <p class="text-sm text-smoke-300">
        Anonyme Beratung und Unterstützung bei Problemen
      </p>
    </div>
    <div>
      <div class="text-2xl mb-2" aria-hidden="true">📧</div>
      <h4 class="font-semibold text-smoke-50 mb-2">Vertrauensstellung</h4>
      <p class="text-sm text-smoke-300">
        Sichere Meldestelle für Feedback und Beschwerden
      </p>
    </div>
    <div>
      <div class="text-2xl mb-2" aria-hidden="true">📚</div>
      <h4 class="font-semibold text-smoke-50 mb-2">Ressourcen</h4>
      <p class="text-sm text-smoke-300">
        Leitfäden und Materialien für sichere Praxis
      </p>
    </div>
  </div>

  <div class="text-center mt-6">
    <a href="/code-of-conduct" class="btn btn-outline">
      Vollständigen Verhaltenskodex lesen
    </a>
  </div>
</div>

<script lang="ts">
  import { link } from "svelte-spa-router";

  const guideSteps = [
    {
      number: 1,
      title: "Willkommen & Einführung",
      description: "Lerne unsere Community und Grundprinzipien kennen",
      icon: "👋",
      content: [
        "Lies unseren Verhaltenskodex aufmerksam durch",
        "Verstehe die Grundlagen der Hypnose-Sicherheit",
        "Lerne die Community-Richtlinien kennen",
      ],
      action: {
        text: "Code of Conduct lesen",
        href: "/code-of-conduct",
      },
    },
    {
      number: 2,
      title: "Erste Teilnahme",
      description: "So bereitest du dich auf dein erstes Event vor",
      icon: "🎯",
      content: [
        'Wähle ein Event mit "Anfängerfreundlich" Label',
        "Kontaktiere uns vorab bei Fragen",
        "Komme etwas früher für eine persönliche Einführung",
      ],
      action: {
        text: "Anfänger-Events finden",
        href: "/events?filter=beginner",
      },
    },
    {
      number: 3,
      title: "Grenzen & Sicherheit",
      description: "Wie du deine Grenzen kommunizierst und respektierst",
      icon: "🛡️",
      content: [
        "Definiere deine persönlichen Grenzen vorab",
        "Kommuniziere Limits klar und deutlich",
        "Lerne das Safeword-System kennen",
      ],
      action: {
        text: "Sicherheitsleitfaden",
        href: "/resources/safety-guide",
      },
    },
    {
      number: 4,
      title: "Community Integration",
      description: "Werde Teil unserer unterstützenden Gemeinschaft",
      icon: "🤝",
      content: [
        "Stelle dich in der Community vor",
        "Finde Mentoren und Lernpartner",
        "Teile deine Erfahrungen respektvoll",
      ],
      action: {
        text: "Community beitreten",
        href: "/contact",
      },
    },
  ];
</script>

<div class="space-y-12">
  <!-- Introduction -->
  <div class="text-center max-w-3xl mx-auto">
    <h3 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
      Neu in der Welt der Hypnose?
    </h3>
    <p class="text-smoke-300 leading-relaxed">
      Unser Leitfaden hilft dir dabei, sicher und respektvoll in unsere
      Community einzusteigen. Folge diesen Schritten, um das Beste aus deiner
      Erfahrung zu machen.
    </p>
  </div>

  <!-- Guide steps -->
  <div class="space-y-8">
    {#each guideSteps as step, index (step.title)}
      <div class="flex flex-col md:flex-row gap-6 items-start">
        <!-- Step number and icon -->
        <div class="flex-shrink-0 flex flex-col items-center">
          <div
            class="w-16 h-16 bg-primary-900 text-primary-100 rounded-full flex items-center justify-center text-2xl font-bold mb-2"
          >
            {step.number}
          </div>
          <div class="text-3xl" aria-hidden="true">
            {step.icon}
          </div>
        </div>

        <!-- Step content -->
        <div class="flex-1 card">
          <h4 class="text-xl font-semibold text-smoke-50 mb-2">
            {step.title}
          </h4>
          <p class="text-smoke-300 mb-4">
            {step.description}
          </p>

          <!-- Step checklist -->
          <ul class="space-y-2 mb-6">
            {#each step.content as item (item)}
              <li class="flex items-start text-sm text-smoke-400">
                <svg
                  class="w-4 h-4 text-accent-400 mr-3 mt-0.5 flex-shrink-0"
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
                {item}
              </li>
            {/each}
          </ul>

          <!-- Action button -->
          <a href={step.action.href} use:link class="btn btn-primary">
            {step.action.text}
          </a>
        </div>

        <!-- Connector line (except for last step) -->
        {#if index < guideSteps.length - 1}
          <div
            class="hidden md:block absolute left-8 mt-20 w-0.5 h-12 bg-charcoal-600"
            style="margin-left: 1.75rem;"
          ></div>
        {/if}
      </div>
    {/each}
  </div>

  <!-- FAQ Teaser -->
  <div class="bg-charcoal-700 border border-charcoal-600 rounded-lg p-6">
    <h4 class="text-lg font-semibold text-smoke-50 mb-4 text-center">
      Häufig gestellte Fragen
    </h4>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h5 class="font-medium text-smoke-50 mb-2">
          Muss ich Erfahrung mit Hypnose haben?
        </h5>
        <p class="text-sm text-smoke-300">
          Nein! Wir begrüßen Menschen aller Erfahrungsstufen. Viele unserer
          Events sind speziell für Anfänger konzipiert.
        </p>
      </div>

      <div>
        <h5 class="font-medium text-smoke-50 mb-2">Ist Hypnose sicher?</h5>
        <p class="text-sm text-smoke-300">
          Bei korrekter Anwendung und in unserer kontrollierten Umgebung ist
          Hypnose sehr sicher.
        </p>
      </div>

      <div>
        <h5 class="font-medium text-smoke-50 mb-2">
          Was soll ich zu meinem ersten Event mitbringen?
        </h5>
        <p class="text-sm text-smoke-300">
          Nur eine offene Einstellung! Alles Weitere wird vor Ort besprochen
          oder ist bereits vorhanden.
        </p>
      </div>

      <div>
        <h5 class="font-medium text-smoke-50 mb-2">
          Kann ich jederzeit aufhören?
        </h5>
        <p class="text-sm text-smoke-300">
          Absolut! Du hast jederzeit die volle Kontrolle und kannst jede
          Aktivität beenden, wann immer du möchtest.
        </p>
      </div>
    </div>

    <div class="text-center mt-6">
      <a href="/resources/faq" use:link class="btn btn-outline">
        Alle FAQs anzeigen
      </a>
    </div>
  </div>
</div>

<script lang="ts">
  import { link } from "svelte-spa-router";

  const eventSeries = [
    {
      title: "Hamburger Hypnose Munch",
      location: "Hamburg • Club Catonium",
      frequency: "Monatlich • Jeden 1. Freitag",
      description:
        "Monatliche Treffen im Club Catonium mit verschiedenen Formaten:",
      formats: [
        "Hypnose 101/102 - Einsteiger-Abende",
        "Koalabox - Praxis-Abende für Geübte",
        "Sonderthemen und Workshops",
      ],
      price: "10€ Eintritt",
      tags: ["Alle Levels", "Erotisch", "Community"],
      nextEvent: "Nächster Termin: Fr, 6. Sep 2025",
    },
    {
      title: "Hypnose-Stammtisch Rhein-Main",
      location: "Mainz • Rhein-Main-Gebiet",
      frequency: "Monatlich • Jeden 2. Samstag",
      description:
        "Treffen für Begeisterte der erotischen, BDSM- und Freizeithypnose:",
      formats: [
        "Austausch und Networking",
        "Gemeinsames Ausprobieren",
        "Entspannte Atmosphäre",
      ],
      price: "Kostenfrei",
      tags: ["Erotisch", "BDSM", "Vernetzung"],
      nextEvent: "Nächster Termin: Sa, 14. Sep 2025",
    },
    {
      title: "Hypno Study Frankfurt",
      location: "Frankfurt • Eventspace",
      frequency: "Monatlich • Jeden 3. Mittwoch",
      description:
        "Deeptalk-Stammtisch mit Fokus auf Techniken und Diskussionen:",
      formats: [
        "Technischer Austausch",
        "English speakers welcome",
        "Fortgeschrittene Methoden",
      ],
      price: "12€ Unkostenbeitrag",
      tags: ["Fortgeschritten", "Techniken", "International"],
      nextEvent: "Nächster Termin: Mi, 18. Sep 2025",
    },
  ];
</script>

<section class="py-16 bg-charcoal-900/50">
  <div class="container mx-auto px-4">
    <!-- Section Header -->
    <div class="text-center mb-12">
      <h2
        class="text-3xl md:text-4xl font-display font-bold text-smoke-50 mb-4"
      >
        Bekannte Event-Reihen
      </h2>
      <p class="text-lg text-smoke-300 max-w-2xl mx-auto">
        Regelmäßige Stammtische und Events in Hamburg, Rhein-Main und Frankfurt
      </p>
    </div>

    <!-- Event Series Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      {#each eventSeries as series}
        <div class="card hover:shadow-glow transition-all duration-300 group">
          <!-- Header -->
          <div class="mb-4">
            <h3
              class="text-xl font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
            >
              {series.title}
            </h3>
            <div class="text-sm text-smoke-400 space-y-1">
              <div class="flex items-center">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
                {series.location}
              </div>
              <div class="flex items-center">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                {series.frequency}
              </div>
            </div>
          </div>

          <!-- Description -->
          <p class="text-smoke-300 mb-4 text-sm">
            {series.description}
          </p>

          <!-- Formats -->
          <div class="mb-4">
            <ul class="space-y-1">
              {#each series.formats as format}
                <li class="text-sm text-smoke-400 flex items-start">
                  <span class="text-accent-400 mr-2 flex-shrink-0">•</span>
                  {format}
                </li>
              {/each}
            </ul>
          </div>

          <!-- Tags -->
          <div class="flex flex-wrap gap-2 mb-4">
            {#each series.tags as tag}
              <span class="badge badge-outline text-xs">
                {tag}
              </span>
            {/each}
          </div>

          <!-- Footer -->
          <div class="mt-auto pt-4 border-t border-charcoal-700">
            <div class="flex justify-between items-end">
              <div>
                <div class="text-lg font-semibold text-consent mb-1">
                  {series.price}
                </div>
                <div class="text-xs text-smoke-500">
                  {series.nextEvent}
                </div>
              </div>
              <a
                href="/events"
                use:link
                class="btn btn-sm btn-outline hover:btn-primary transition-all duration-200"
              >
                Details
              </a>
            </div>
          </div>
        </div>
      {/each}
    </div>

    <!-- CTA -->
    <div class="text-center mt-12">
      <a href="/events" use:link class="btn btn-primary mr-4">
        Alle Events anzeigen
      </a>
      <a href="/submit-event" use:link class="btn btn-outline">
        Event vorschlagen
      </a>
    </div>
  </div>
</section>

<script lang="ts">
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";
  import BrandLogo from "../ui/BrandLogo.svelte";

  let heroRef: HTMLElement;
  let isVisible = false;

  onMount(() => {
    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isVisible = true;
          }
        });
      },
      { threshold: 0.1 },
    );

    if (heroRef) {
      observer.observe(heroRef);
    }

    return () => {
      if (heroRef) {
        observer.unobserve(heroRef);
      }
    };
  });
</script>

<section
  bind:this={heroRef}
  class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-charcoal-900 via-primary-950 to-charcoal-900"
  aria-labelledby="hero-heading"
>
  <!-- Background spiral gradient -->
  <div
    class="absolute inset-0 opacity-30"
    style="background: radial-gradient(circle at 60% 40%, rgba(65, 242, 192, 0.1) 0%, transparent 60%)"
    aria-hidden="true"
  ></div>

  <!-- Content -->
  <div class="relative z-10 text-center px-4 max-w-5xl mx-auto mt-20">
    <!-- Logo -->
    <div class="mb-8 flex justify-center">
      <BrandLogo
        size="xl"
        className="w-24 h-24 md:w-32 md:h-32"
        showAnimation={isVisible}
      />
    </div>

    <!-- Main heading -->
    <h1
      id="hero-heading"
      class="text-5xl md:text-7xl lg:text-8xl font-display font-bold mb-6 {isVisible
        ? 'animate-fade-in'
        : 'opacity-0'}"
    >
      <span
        class="text-gradient bg-gradient-to-r from-accent-400 to-secondary-400 bg-clip-text text-transparent"
      >
        Dive Deep.
      </span>
      <br />
      <span class="text-smoke-50"> Play Safe. </span>
    </h1>

    <!-- Subtitle -->
    <p
      class="text-xl md:text-2xl text-smoke-200 mb-8 max-w-3xl mx-auto leading-relaxed {isVisible
        ? 'animate-slide-up'
        : 'opacity-0 translate-y-4'}"
      style="animation-delay: 0.3s;"
    >
      Zentrale Plattform für <strong>geprüfte Hypnose-Events</strong> im
      deutschsprachigen Raum. Spezialisiert auf
      <strong>Freizeit- und erotische Hypnose</strong>
      mit Fokus auf
      <strong>Konsens, Professionalität und Sicherheit</strong>.
    </p>

    <!-- CTA Buttons -->
    <div
      class="flex flex-col sm:flex-row gap-4 justify-center mb-12 {isVisible
        ? 'animate-slide-up'
        : 'opacity-0 translate-y-4'}"
      style="animation-delay: 0.6s;"
    >
      <a
        href="/events"
        use:link
        class="btn btn-primary text-lg px-8 py-4 shadow-glow hover:shadow-glow transform hover:scale-105 transition-all duration-300"
      >
        Stammtische & Events
      </a>
      <a
        href="/about"
        use:link
        class="btn btn-outline text-lg px-8 py-4 transform hover:scale-105 transition-all duration-300"
      >
        Über uns erfahren
      </a>
    </div>

    <!-- Key features -->
    <div
      class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center {isVisible
        ? 'animate-fade-in'
        : 'opacity-0'}"
      style="animation-delay: 0.9s;"
    >
      <div class="space-y-2">
        <div class="text-consent text-2xl mb-2" aria-hidden="true">✓</div>
        <p class="text-lg font-semibold text-smoke-50">
          Einverständnis-zentriert
        </p>
        <p class="text-smoke-300 text-sm">
          Alle Aktivitäten basieren auf ausdrücklichem und informiertem
          Einverständnis
        </p>
      </div>

      <div class="space-y-2">
        <div class="text-accent-400 text-2xl mb-2" aria-hidden="true">🤝</div>
        <p class="text-lg font-semibold text-smoke-50">Community-fokussiert</p>
        <p class="text-smoke-300 text-sm">
          Eine unterstützende Gemeinschaft für Lernende aller Erfahrungsstufen
        </p>
      </div>

      <div class="space-y-2">
        <div class="text-secondary-400 text-2xl mb-2" aria-hidden="true">
          🛡️
        </div>
        <p class="text-lg font-semibold text-smoke-50">Sicher & Respektvoll</p>
        <p class="text-smoke-300 text-sm">
          Klare Grenzen und professionelle Standards für alle Veranstaltungen
        </p>
      </div>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div
    class="absolute bottom-8 left-1/2 transform -translate-x-1/2 {isVisible
      ? 'animate-bounce'
      : 'opacity-0'}"
    style="animation-delay: 1.2s;"
  >
    <a
      href="#content"
      class="text-smoke-400 hover:text-accent-400 transition-colors duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 focus-visible:ring-offset-charcoal-900 rounded-full p-2"
      aria-label="Zum Inhalt scrollen"
    >
      <svg
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 14l-7 7m0 0l-7-7m7 7V3"
        />
      </svg>
    </a>
  </div>
</section>

<!-- Content anchor for scroll -->
<div id="content" aria-hidden="true"></div>

<style>
  @media (prefers-reduced-motion: reduce) {
    .animate-fade-in,
    .animate-slide-up,
    .animate-bounce {
      animation: none !important;
      opacity: 1 !important;
      transform: none !important;
    }
  }

  .animate-fade-in {
    animation: fadeIn 1s ease-out forwards;
  }

  .animate-slide-up {
    animation: slideUp 1s ease-out forwards;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

<script lang="ts">
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";
  import LocationDetails from "../components/map/LocationDetails.svelte";
  import MapFilter from "../components/map/MapFilter.svelte";
  import MapView from "../components/map/MapView.svelte";
  import {
    closeLocationDetails,
    filteredLocations,
    isMapLoading,
    selectedLocation,
  } from "../stores/api-map-locations";
  import { t } from "../utils/i18n";

  const isBrowser = typeof window !== "undefined";
  let showMobileDetails = false;
  let isMobileViewport = false;

  const howToSteps = [
    "map.info.howTo.step1",
    "map.info.howTo.step2",
    "map.info.howTo.step3",
    "map.info.howTo.step4",
  ];

  const relatedLinks = [
    { href: "/events", labelKey: "map.related.events", icon: "📅" },
    {
      href: "/ressourcen/safety-guide",
      labelKey: "map.related.safety",
      icon: "🛡️",
    },
    {
      href: "/code-of-conduct",
      labelKey: "map.related.codeOfConduct",
      icon: "📋",
    },
    { href: "/about", labelKey: "map.related.about", icon: "👥" },
  ] as const;

  function updateViewportState(): void {
    if (!isBrowser) return;
    isMobileViewport = window.innerWidth < 768;
  }

  $: showMobileDetails = Boolean($selectedLocation && isMobileViewport);

  function closeMobileDetails() {
    showMobileDetails = false;
    closeLocationDetails();
  }

  onMount(() => {
    if (!isBrowser) {
      return;
    }

    updateViewportState();
    window.addEventListener("resize", updateViewportState);

    let stylesheet = document.querySelector<HTMLLinkElement>(
      'link[data-leaflet="true"]',
    );
    let appended = false;

    if (!stylesheet) {
      stylesheet = document.createElement("link");
      stylesheet.rel = "stylesheet";
      stylesheet.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
      stylesheet.integrity =
        "sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=";
      stylesheet.crossOrigin = "";
      stylesheet.dataset.leaflet = "true";
      document.head.appendChild(stylesheet);
      appended = true;
    }

    return () => {
      window.removeEventListener("resize", updateViewportState);
      if (appended && stylesheet?.parentNode) {
        stylesheet.parentNode.removeChild(stylesheet);
      }
    };
  });

  $: locationCount = $filteredLocations.length;
  $: locationCountText =
    locationCount === 1
      ? t("map.footer.single", { values: { count: locationCount } })
      : t("map.footer.plural", { values: { count: locationCount } });

  $: pageTitle = t("map.meta.title");
  $: pageDescription = t("map.meta.description");
  $: pageKeywords = t("map.meta.keywords");
</script>

<svelte:head>
  <title>{pageTitle}</title>
  <meta name="description" content={pageDescription} />
  <meta name="keywords" content={pageKeywords} />
</svelte:head>

<div class="container mx-auto px-4 py-8">
  <!-- Page Header -->
  <header class="text-center mb-8">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      {t("map.hero.headline")}
    </h1>
    <p class="text-xl text-smoke-300 max-w-3xl mx-auto mb-6">
      {t("map.hero.lead")}
    </p>

    <!-- Quick Stats -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-number">{locationCount}</span>
        <span class="stat-label">{t("map.stats.locationsLabel")}</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">🇩🇪 🇦🇹 🇨🇭</span>
        <span class="stat-label">{t("map.stats.regionLabel")}</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">🔄</span>
        <span class="stat-label">{t("map.stats.liveUpdatesLabel")}</span>
      </div>
    </div>
  </header>

  <!-- Map Section -->
  <section class="map-section" aria-label="Interaktive Stammtisch-Karte">
    <!-- Filters -->
    <div class="mb-6">
      <MapFilter />
    </div>

    <!-- Map Container -->
    <div class="map-container-wrapper">
      <!-- Map -->
      <div class="map-area">
        {#if $isMapLoading}
          <div class="map-loading-state">
            <div class="loading-spinner"></div>
            <p>Karte wird geladen...</p>
          </div>
        {:else}
          <MapView height="600px" />
        {/if}
      </div>

      <!-- Desktop Location Details Sidebar -->
      {#if $selectedLocation && !showMobileDetails}
        <aside class="details-sidebar" aria-label="Stammtisch-Details">
          <LocationDetails />
        </aside>
      {/if}
    </div>

    <!-- Location Count -->
    <div class="map-footer">
      <p class="text-sm text-smoke-400">{locationCountText}</p>
    </div>
  </section>

  <!-- Mobile Location Details Modal -->
  {#if showMobileDetails && $selectedLocation}
    <div
      class="mobile-details-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="mobile-details-title"
    >
      <div
        class="modal-overlay"
        role="button"
        tabindex="0"
        on:click={closeMobileDetails}
        on:keydown={(e) => e.key === "Escape" && closeMobileDetails()}
        aria-label="Overlay schließen"
      ></div>
      <div class="modal-content">
        <LocationDetails />
        <button
          class="mobile-close-btn"
          on:click={closeMobileDetails}
          aria-label={t("map.details.close")}
        >
          {t("map.details.close")}
        </button>
      </div>
    </div>
  {/if}

  <!-- Additional Information -->
  <section class="info-section mt-12">
    <div class="grid md:grid-cols-2 gap-8">
      <!-- How to Use -->
      <div class="info-card">
        <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
          {t("map.info.howTo.title")}
        </h2>
        <ul class="space-y-2 text-smoke-300">
          {#each howToSteps as step}
            <li>• {t(step)}</li>
          {/each}
        </ul>
      </div>

      <!-- Add Location -->
      <div class="info-card">
        <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
          {t("map.info.add.title")}
        </h2>
        <p class="text-smoke-300 mb-4">{t("map.info.add.description")}</p>
        <div class="flex flex-col sm:flex-row gap-3">
          <a href="/contact" use:link class="btn btn-primary">
            {t("map.info.add.contactButton")}
          </a>
          <a href="/submit-event" use:link class="btn btn-outline">
            {t("map.info.add.submitButton")}
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Related Links -->
  <section class="related-links mt-12">
    <h2
      class="text-2xl font-display font-semibold text-smoke-50 mb-6 text-center"
    >
      {t("map.related.title")}
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      {#each relatedLinks as item}
        <a href={item.href} use:link class="link-card">
          {item.icon}
          {t(item.labelKey)}
        </a>
      {/each}
    </div>
  </section>
</div>

<style>
  .stats-bar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
  }

  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: rgb(99 102 241);
  }

  .stat-label {
    font-size: 0.875rem;
    color: #9ca3af;
    font-weight: 500;
  }

  .map-container-wrapper {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    position: relative;
  }

  @media (min-width: 1024px) {
    .map-container-wrapper {
      grid-template-columns: 1fr 400px;
    }
  }

  .map-area {
    position: relative;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }

  .map-loading-state {
    height: 600px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #1f2937;
    color: #e5e7eb;
    border-radius: 0.75rem;
  }

  .loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #374151;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  .details-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
  }

  .map-footer {
    margin-top: 1rem;
    text-align: center;
  }

  .mobile-details-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }

  .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
  }

  .modal-content {
    position: relative;
    z-index: 201;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .mobile-close-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    margin-top: 1rem;
  }

  .info-section {
    background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
    border-radius: 1rem;
    padding: 2rem;
    margin-top: 3rem;
  }

  .info-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .related-links {
    text-align: center;
  }

  .link-card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 0.5rem;
    color: rgb(99 102 241);
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
  }

  .link-card:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.3);
    transform: translateY(-2px);
  }

  /* Hide desktop sidebar on mobile */
  @media (max-width: 1023px) {
    .details-sidebar {
      display: none;
    }
  }

  @media (max-width: 640px) {
    .stats-bar {
      gap: 1rem;
    }

    .stat-number {
      font-size: 1.25rem;
    }

    .info-section {
      padding: 1.5rem;
    }

    .info-card {
      padding: 1rem;
    }
  }
</style>

<script lang="ts">
  import type { GeoJsonObject } from "geojson";
  import { Map as LeafletMap, Marker } from "leaflet";
  import { onDestroy, onMount } from "svelte";
  import { CountryMetadata } from "../../classes/CountryMetadata";
  import { CountryCode } from "../../enums/countryCode";
  import {
    filteredStammtischLocations,
    isLoadingLocations,
    locationsError,
    mapViewport,
    openLocationDetails,
    updateMapViewport,
  } from "../../stores/api-map-locations";
  import type { StammtischLocation } from "../../types/stammtisch";
  import { t } from "../../utils/i18n";

  type LeafletNamespace = typeof import("leaflet");
  type DivIcon = import("leaflet").DivIcon;

  let mapContainer: HTMLDivElement;
  let map: LeafletMap | null = null;
  let markers: Marker[] = [];
  let L: LeafletNamespace;
  let stammtischIcon: DivIcon | null = null;

  // eslint-disable-next-line svelte/prefer-svelte-reactivity -- Cache doesn't need reactivity
  const geoJsonCache = new Map<string, GeoJsonObject>();
  const COUNTRY_BOUNDARY_FILES: Record<CountryCode, string> = {
    [CountryCode.GERMANY]: "germany",
    [CountryCode.AUSTRIA]: "austria",
    [CountryCode.SWITZERLAND]: "switzerland",
  };

  // Props
  export let height: string = "500px";
  export let className: string = "";

  onMount(async () => {
    // Dynamically import Leaflet to avoid SSR issues
    L = await import("leaflet");

    configureLeafletIcons();
    initializeMap();

    // Listen for custom events from popup buttons
    mapContainer?.addEventListener(
      "show-details",
      handleShowDetails as EventListener,
    );
  });

  onDestroy(() => {
    if (map) {
      map.remove();
      map = null;
    }
    markers = [];
    // Remove event listener
    mapContainer?.removeEventListener(
      "show-details",
      handleShowDetails as EventListener,
    );
  });

  function configureLeafletIcons(): void {
    if (!L) return;

    const iconProto = L.Icon.Default.prototype as unknown as Record<
      string,
      unknown
    >;
    if (iconProto && "_getIconUrl" in iconProto) {
      delete (iconProto as { _getIconUrl?: unknown })._getIconUrl;
    }
    L.Icon.Default.mergeOptions({
      iconRetinaUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png",
      iconUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png",
      shadowUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png",
    });
  }

  function initializeMap(): void {
    if (!mapContainer || !L || map) return;

    const viewport = $mapViewport;

    // DACH region bounds
    const dachBounds = L.latLngBounds(
      [45.8, 5.9], // Southwest corner (southern Switzerland/Austria)
      [55.1, 17.2], // Northeast corner (northern Germany/eastern Austria)
    );

    // Create map with DACH region constraints
    map = L.map(mapContainer, {
      center: [viewport.center.lat, viewport.center.lng],
      zoom: viewport.zoom,
      zoomControl: true,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      dragging: true,
      maxBounds: dachBounds, // Restrict panning to DACH region
      maxBoundsViscosity: 1.0, // How much to stop panning outside bounds
      minZoom: 4, // Weitere Zoom-Stufe nach draußen ermöglichen
      maxZoom: 15, // Reasonable max zoom for city level
    });

    // Add tile layer (OpenStreetMap)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 15,
      minZoom: 4,
    }).addTo(map);

    // Add DACH region highlight overlay
    void addDachRegionHighlight();

    // Update viewport store when map moves
    map.on("moveend", () => {
      const currentMap = map;
      if (!currentMap) return;

      const center = currentMap.getCenter();
      const zoom = currentMap.getZoom();
      updateMapViewport({
        center: { lat: center.lat, lng: center.lng },
        zoom,
      });
    });

    // Update markers when locations change
    updateMarkers($filteredStammtischLocations);
  }

  async function addDachRegionHighlight(): Promise<void> {
    if (!map || !L) return;

    const countryStyles: Record<
      CountryCode,
      { color: string; fillColor: string; fillOpacity: number }
    > = {
      [CountryCode.GERMANY]: {
        color: "#1f77b4",
        fillColor: "#1f77b4",
        fillOpacity: 0.15,
      },
      [CountryCode.AUSTRIA]: {
        color: "#d62728",
        fillColor: "#d62728",
        fillOpacity: 0.15,
      },
      [CountryCode.SWITZERLAND]: {
        color: "#2ca02c",
        fillColor: "#2ca02c",
        fillOpacity: 0.15,
      },
    };

    const baseStyle = {
      weight: 2,
      opacity: 0.8,
      interactive: false,
    };

    try {
      const layers = await Promise.all(
        Object.entries(COUNTRY_BOUNDARY_FILES).map(async ([code, filename]) => {
          const geoJson = await loadCountryBoundary(filename);
          if (!geoJson) {
            return null;
          }

          return L.geoJSON(geoJson, {
            style: () => ({
              ...baseStyle,
              ...countryStyles[code as CountryCode],
            }),
          }).addTo(map!);
        }),
      );

      if (!layers.some((layer) => layer)) {
        addFallbackRegions();
      }
    } catch (error) {
      console.error("Error loading country boundaries:", error);
      addFallbackRegions();
    }
  }

  async function loadCountryBoundary(
    filename: string,
  ): Promise<GeoJsonObject | null> {
    if (geoJsonCache.has(filename)) {
      return geoJsonCache.get(filename) ?? null;
    }

    try {
      const response = await fetch(`/data/${filename}.geojson`, {
        cache: "force-cache",
      });

      if (!response.ok) {
        console.warn(`Could not load ${filename}.geojson`);
        return null;
      }

      const data = (await response.json()) as GeoJsonObject;
      geoJsonCache.set(filename, data);
      return data;
    } catch (error) {
      console.error(`Error loading geojson for ${filename}:`, error);
      return null;
    }
  }

  // Fallback-Funktion mit vereinfachten Polygonen
  function addFallbackRegions() {
    if (!map || !L) return;

    // Sehr vereinfachte aber erkennbare Ländergrenzen als Fallback
    const fallbackRegions = [
      {
        color: "#1f77b4",
        coords: [
          [54.983, 5.866] as [number, number],
          [54.983, 15.042] as [number, number],
          [47.271, 15.042] as [number, number],
          [47.271, 5.866] as [number, number],
          [54.983, 5.866] as [number, number],
        ],
      },
      {
        color: "#d62728",
        coords: [
          [49.021, 9.531] as [number, number],
          [49.021, 17.161] as [number, number],
          [46.372, 17.161] as [number, number],
          [46.372, 9.531] as [number, number],
          [49.021, 9.531] as [number, number],
        ],
      },
      {
        color: "#2ca02c",
        coords: [
          [47.808, 5.956] as [number, number],
          [47.808, 10.492] as [number, number],
          [45.818, 10.492] as [number, number],
          [45.818, 5.956] as [number, number],
          [47.808, 5.956] as [number, number],
        ],
      },
    ];

    fallbackRegions.forEach((region) => {
      L.polygon(region.coords, {
        color: region.color,
        weight: 2,
        opacity: 0.8,
        fillColor: region.color,
        fillOpacity: 0.15,
        interactive: false,
      }).addTo(map!);
    });
  }
  function updateMarkers(locations: StammtischLocation[]): void {
    if (!map || !L) return;

    // Bestehende Marker entfernen
    markers.forEach((marker) => {
      try {
        marker.remove();
      } catch {
        // Marker war bereits entfernt
      }
    });
    markers = [];

    const icon = ensureStammtischIcon();

    locations.forEach((location) => {
      const marker = L.marker(
        [location.coordinates.lat, location.coordinates.lng],
        {
          icon,
          alt: t("map.marker.ariaLabel", {
            values: { name: location.name, city: location.city },
          }),
          keyboard: true,
          riseOnHover: true,
        },
      ).addTo(map!);

      // Tooltip beim Hover anzeigen
      const tooltipContent = createTooltipContent(location);
      marker.bindTooltip(tooltipContent, {
        permanent: false,
        direction: "top",
        offset: [0, -35],
        className: "stammtisch-tooltip",
        opacity: 1,
      });

      // Popup mit verbessertem Styling
      const popupContent = createPopupContent(location);
      marker.bindPopup(popupContent, {
        maxWidth: 320,
        minWidth: 240,
        className: "stammtisch-popup",
        closeButton: true,
        autoClose: true,
        closeOnEscapeKey: true,
        autoPan: true,
        autoPanPadding: [40, 60],
        // Kein keepInView - verursacht Freeze mit maxBounds
      });

      // Click öffnet Details-Panel (mit Fehlerbehandlung)
      marker.on("click", () => {
        try {
          openLocationDetails(location);
        } catch (error) {
          console.error("Error opening location details:", error);
        }
      });

      markers.push(marker);
    });
  }

  function createTooltipContent(location: StammtischLocation): string {
    const countryInfo = CountryMetadata.getCountryInfo(location.country);
    const name = escapeHtml(location.name);
    const city = escapeHtml(location.city);
    const hint = escapeHtml(t("map.tooltip.clickForDetails"));

    return `
      <div class="tooltip-content">
        <strong>${countryInfo.flag} ${name}</strong>
        <span class="tooltip-city">${city}</span>
        <span class="tooltip-hint">${hint}</span>
      </div>
    `;
  }

  function ensureStammtischIcon(): DivIcon {
    if (!stammtischIcon && L) {
      stammtischIcon = L.divIcon({
        html: `
          <div class="stammtisch-marker" role="img">
            <div class="marker-pin">
              <div class="marker-icon">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
              </div>
            </div>
            <div class="marker-pulse"></div>
            <div class="marker-shadow"></div>
          </div>
        `,
        className: "custom-stammtisch-marker",
        iconSize: [36, 48],
        iconAnchor: [18, 48],
        popupAnchor: [0, -48],
        tooltipAnchor: [0, -40],
      });
    }

    return stammtischIcon!;
  }

  function createPopupContent(location: StammtischLocation): string {
    const countryInfo = CountryMetadata.getCountryInfo(location.country);
    const tagsHtml =
      location.tags.length > 0
        ? location.tags
            .slice(0, 3) // Maximal 3 Tags im Popup zeigen
            .map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`)
            .join("") +
          (location.tags.length > 3
            ? `<span class="tag tag-more">+${location.tags.length - 3}</span>`
            : "")
        : "";

    const locationText = escapeHtml(
      t("map.popup.location", {
        values: {
          city: location.city,
          region: location.region,
        },
      }),
    );

    const meetingFrequency =
      location.meetingInfo.frequency &&
      location.meetingInfo.frequency.length > 0
        ? location.meetingInfo.frequency
        : t("map.details.frequencyUnknown");

    const frequencyText = escapeHtml(
      t("map.popup.frequency", {
        values: { frequency: meetingFrequency },
      }),
    );

    const buttonLabel = escapeHtml(t("map.popup.more"));
    const locationName = escapeHtml(location.name);
    const locationId = escapeForJsString(location.id);
    const isActive = location.isActive;
    const statusClass = isActive ? "active" : "inactive";
    const statusIcon = isActive ? "🟢" : "🔴";
    const statusLabel = isActive ? "Aktiv" : "Inaktiv";

    return `
      <div class="popup-content" role="article" aria-label="${locationName}">
        <header class="popup-header">
          <h3 class="popup-title">
            <span class="popup-flag">${countryInfo.flag}</span>
            ${locationName}
          </h3>
          <span class="popup-status ${statusClass}" aria-label="${statusLabel}">
            ${statusIcon}
          </span>
        </header>
        <div class="popup-body">
          <p class="popup-location">
            ${locationText}
          </p>
          <p class="popup-frequency">
            ${frequencyText}
          </p>
          ${tagsHtml ? `<div class="popup-tags">${tagsHtml}</div>` : ""}
        </div>
        <footer class="popup-footer">
          <button
            class="popup-button"
            onclick="this.dispatchEvent(new CustomEvent('show-details', { bubbles: true, detail: '${locationId}' }))"
            aria-label="${buttonLabel} zu ${locationName}"
          >
            <span class="popup-button-text">${buttonLabel}</span>
            <svg class="popup-button-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </footer>
      </div>
    `;
  }
  let escapeDiv: HTMLDivElement | null = null;

  function escapeHtml(value: string): string {
    if (typeof document === "undefined") {
      return value
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
    }

    if (!escapeDiv) {
      escapeDiv = document.createElement("div");
    }

    escapeDiv.textContent = value;
    return escapeDiv.innerHTML;
  }

  function escapeForJsString(value: string): string {
    return value
      .replace(/\\/g, "\\\\")
      .replace(/"/g, '\\"')
      .replace(/'/g, "\\'");
  }

  // Track last rendered locations to avoid unnecessary re-renders
  let lastRenderedLocationsHash = "";

  function getLocationsHash(locations: StammtischLocation[]): string {
    return locations.map((l) => l.id).join(",");
  }

  // React to store changes - mit Deduplication
  $: if (map && L && $filteredStammtischLocations) {
    const currentHash = getLocationsHash($filteredStammtischLocations);
    if (currentHash !== lastRenderedLocationsHash) {
      lastRenderedLocationsHash = currentHash;
      updateMarkers($filteredStammtischLocations);
    }
  }

  // Update map view when viewport changes (from external sources)
  $: if (map && $mapViewport) {
    const currentCenter = map.getCenter();
    const currentZoom = map.getZoom();

    if (
      Math.abs(currentCenter.lat - $mapViewport.center.lat) > 0.001 ||
      Math.abs(currentCenter.lng - $mapViewport.center.lng) > 0.001 ||
      currentZoom !== $mapViewport.zoom
    ) {
      map.setView(
        [$mapViewport.center.lat, $mapViewport.center.lng],
        $mapViewport.zoom,
      );
    }
  }

  // Handle popup custom events
  function handleShowDetails(event: Event) {
    const customEvent = event as CustomEvent;
    const locationId = customEvent.detail;
    const location = $filteredStammtischLocations.find(
      (l) => l.id === locationId,
    );
    if (location) {
      openLocationDetails(location);
    }
  }
</script>

<div
  bind:this={mapContainer}
  class="map-container {className}"
  style="height: {height}; min-height: 400px;"
  role="application"
  aria-label={t("map.view.aria")}
  tabindex="-1"
>
  <!-- Loading indicators -->
  {#if !map}
    <div class="map-loading" role="status" aria-live="polite">
      <div class="loading-spinner"></div>
      <p>{t("map.loading.map")}</p>
    </div>
  {:else if $isLoadingLocations}
    <div class="locations-loading" role="status" aria-live="polite">
      <div class="loading-spinner small"></div>
      <span>{t("map.loading.locations")}</span>
    </div>
  {/if}

  <!-- Error indicator -->
  {#if $locationsError}
    <div class="locations-error" role="alert" aria-live="assertive">
      <span class="error-icon">⚠️</span>
      <span>
        {t("map.loading.error", { values: { message: $locationsError } })}
      </span>
    </div>
  {/if}
</div>

<style>
  .map-container {
    position: relative;
    border-radius: 0.5rem;
    overflow: visible; /* Popups dürfen über den Rand hinausragen */
    box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.1),
      0 2px 4px -1px rgba(0, 0, 0, 0.06);
    background-color: #f3f4f6;
    z-index: 1;
  }

  /* Leaflet-Container behält sein Clipping für Tiles */
  .map-container :global(.leaflet-container) {
    border-radius: 0.5rem;
    overflow: hidden;
  }

  /* Popup-Pane darf über den Container hinausragen */
  .map-container :global(.leaflet-pane.leaflet-popup-pane) {
    overflow: visible !important;
  }

  .map-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    background-color: #1f2937;
    color: #e5e7eb;
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

  .loading-spinner.small {
    width: 24px;
    height: 24px;
    border-width: 3px;
    margin-bottom: 0;
    margin-right: 0.5rem;
  }

  .locations-loading {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(31, 41, 55, 0.9);
    color: #e5e7eb;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    z-index: 1000;
  }

  .locations-error {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(220, 38, 38, 0.9);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    z-index: 1000;
    max-width: 90%;
  }

  .error-icon {
    margin-right: 0.5rem;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  /* Custom Stammtisch Marker Styles */
  :global(.custom-stammtisch-marker) {
    background: transparent !important;
    border: none !important;
  }

  :global(.stammtisch-marker) {
    position: relative;
    width: 36px;
    height: 48px;
    cursor: pointer;
    transition: transform 0.2s ease-out;
  }

  :global(.stammtisch-marker:hover) {
    transform: scale(1.15);
  }

  :global(.marker-pin) {
    position: absolute;
    top: 0;
    left: 50%;
    width: 32px;
    height: 32px;
    background: linear-gradient(145deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
    border: 3px solid #ffffff;
    border-radius: 50% 50% 50% 0;
    transform: translateX(-50%) rotate(-45deg);
    box-shadow:
      0 4px 12px rgba(79, 70, 229, 0.4),
      0 2px 4px rgba(0, 0, 0, 0.2),
      inset 0 1px 2px rgba(255, 255, 255, 0.3);
    z-index: 2;
    transition: all 0.2s ease-out;
  }

  :global(.custom-stammtisch-marker:hover .marker-pin) {
    background: linear-gradient(145deg, #818cf8 0%, #6366f1 50%, #4f46e5 100%);
    box-shadow:
      0 6px 16px rgba(79, 70, 229, 0.5),
      0 3px 6px rgba(0, 0, 0, 0.25),
      inset 0 1px 3px rgba(255, 255, 255, 0.4);
    transform: translateX(-50%) rotate(-45deg) scale(1.05);
  }

  :global(.marker-icon) {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
    width: 16px;
    height: 16px;
    color: white;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
  }

  :global(.marker-icon svg) {
    width: 100%;
    height: 100%;
  }

  :global(.marker-pulse) {
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    background: rgba(99, 102, 241, 0.3);
    border-radius: 50%;
    animation: markerPulse 2s ease-in-out infinite;
    z-index: 0;
  }

  @keyframes markerPulse {
    0%,
    100% {
      transform: translateX(-50%) scale(0.8);
      opacity: 0.6;
    }
    50% {
      transform: translateX(-50%) scale(1.2);
      opacity: 0;
    }
  }

  :global(.marker-shadow) {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 10px;
    background: radial-gradient(
      ellipse at center,
      rgba(0, 0, 0, 0.25) 0%,
      transparent 70%
    );
    z-index: 1;
  }

  /* Tooltip Styles */
  :global(.stammtisch-tooltip) {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%) !important;
    border: 1px solid rgba(99, 102, 241, 0.3) !important;
    border-radius: 8px !important;
    box-shadow:
      0 8px 24px rgba(0, 0, 0, 0.3),
      0 2px 8px rgba(0, 0, 0, 0.2) !important;
    padding: 0 !important;
  }

  :global(.stammtisch-tooltip::before) {
    border-top-color: #1f2937 !important;
  }

  :global(.leaflet-tooltip-top.stammtisch-tooltip::before) {
    border-top-color: #1f2937 !important;
    margin-left: -8px;
  }

  :global(.tooltip-content) {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 14px;
    text-align: center;
  }

  :global(.tooltip-content strong) {
    color: #f3f4f6;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
  }

  :global(.tooltip-city) {
    color: #9ca3af;
    font-size: 0.8rem;
  }

  :global(.tooltip-hint) {
    color: #6366f1;
    font-size: 0.7rem;
    margin-top: 2px;
    font-style: italic;
  }

  /* Updated Popup Styles */
  :global(.leaflet-popup-content-wrapper) {
    background: linear-gradient(145deg, #1f2937 0%, #111827 100%) !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 12px !important;
    box-shadow:
      0 20px 40px rgba(0, 0, 0, 0.35),
      0 8px 16px rgba(0, 0, 0, 0.25) !important;
    overflow: hidden;
  }

  :global(.leaflet-popup-content) {
    margin: 0 !important;
    padding: 0 !important;
    color: #e5e7eb !important;
    min-width: 220px;
  }

  :global(.leaflet-popup-tip) {
    background: #1f2937 !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-top: none !important;
    border-left: none !important;
  }

  :global(.leaflet-popup-close-button) {
    color: #9ca3af !important;
    font-size: 20px !important;
    padding: 8px 12px !important;
    transition: color 0.2s ease !important;
  }

  :global(.leaflet-popup-close-button:hover) {
    color: #f3f4f6 !important;
    background: transparent !important;
  }

  /* Popup Animation */
  :global(.leaflet-popup) {
    animation: popupAppear 0.25s ease-out;
  }

  /* Sicherstellen, dass Popups nicht über den Kartenrand hinausragen */
  :global(.leaflet-popup-pane) {
    overflow: visible;
  }

  :global(.map-container .leaflet-popup) {
    /* Verhindert Clipping am Rand */
    margin-top: 0;
  }

  @keyframes popupAppear {
    from {
      opacity: 0;
      transform: translate3d(-50%, -10px, 0) scale(0.95);
    }
    to {
      opacity: 1;
      transform: translate3d(-50%, 0, 0) scale(1);
    }
  }

  /* Popup Content Styles */
  :global(.stammtisch-popup .popup-content) {
    font-family:
      system-ui,
      -apple-system,
      sans-serif;
    padding: 0;
  }

  :global(.stammtisch-popup .popup-header) {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 16px 16px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(99, 102, 241, 0.05);
  }

  :global(.stammtisch-popup .popup-title) {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: #f3f4f6 !important;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.3;
    flex: 1;
    padding-right: 8px;
  }

  :global(.stammtisch-popup .popup-flag) {
    font-size: 1.2em;
    flex-shrink: 0;
  }

  :global(.stammtisch-popup .popup-status) {
    font-size: 0.85rem;
    flex-shrink: 0;
  }

  :global(.stammtisch-popup .popup-body) {
    padding: 12px 16px;
  }

  :global(.stammtisch-popup .popup-location) {
    margin: 0 0 6px;
    color: #d1d5db !important;
    font-size: 0.875rem;
    line-height: 1.4;
  }

  :global(.stammtisch-popup .popup-frequency) {
    margin: 0 0 10px;
    color: #9ca3af !important;
    font-size: 0.8rem;
    line-height: 1.4;
  }

  :global(.stammtisch-popup .popup-tags) {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
  }

  :global(.stammtisch-popup .tag) {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.02em;
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
  }

  :global(.stammtisch-popup .tag-more) {
    background: rgba(255, 255, 255, 0.1);
    color: #9ca3af;
    box-shadow: none;
  }

  :global(.stammtisch-popup .popup-footer) {
    padding: 12px 16px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
  }

  :global(.stammtisch-popup .popup-button) {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
  }

  :global(.stammtisch-popup .popup-button:hover) {
    background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
  }

  :global(.stammtisch-popup .popup-button:active) {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
  }

  :global(.stammtisch-popup .popup-button:focus-visible) {
    outline: 2px solid #818cf8;
    outline-offset: 2px;
  }

  :global(.stammtisch-popup .popup-button-text) {
    flex: 1;
  }

  :global(.stammtisch-popup .popup-button-icon) {
    flex-shrink: 0;
    transition: transform 0.2s ease;
  }

  :global(.stammtisch-popup .popup-button:hover .popup-button-icon) {
    transform: translateX(3px);
  }

  :global(.leaflet-container) {
    font-family:
      system-ui,
      -apple-system,
      sans-serif;
  }

  /* Ensure popups and tooltips appear above all map layers */
  :global(.leaflet-pane) {
    z-index: 400;
  }

  :global(.leaflet-tile-pane) {
    z-index: 200;
  }

  :global(.leaflet-overlay-pane) {
    z-index: 400;
  }

  :global(.leaflet-shadow-pane) {
    z-index: 500;
  }

  :global(.leaflet-marker-pane) {
    z-index: 600;
  }

  :global(.leaflet-tooltip-pane) {
    z-index: 650;
  }

  :global(.leaflet-popup-pane) {
    z-index: 700;
  }

  :global(.leaflet-control-container) {
    z-index: 800;
  }
</style>

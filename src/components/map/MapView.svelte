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

    markers.forEach((marker) => marker.remove());
    markers = [];

    const icon = ensureStammtischIcon();

    locations.forEach((location) => {
      const marker = L.marker(
        [location.coordinates.lat, location.coordinates.lng],
        {
          icon,
        },
      ).addTo(map!);

      const popupContent = createPopupContent(location);
      marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: "stammtisch-popup",
      });

      marker.on("click", () => {
        openLocationDetails(location);
      });

      markers.push(marker);
    });
  }

  function ensureStammtischIcon(): DivIcon {
    if (!stammtischIcon && L) {
      stammtischIcon = L.divIcon({
        html: `
          <div class="stammtisch-marker">
            <div class="marker-pin">
              <div class="marker-icon">📍</div>
            </div>
            <div class="marker-shadow"></div>
          </div>
        `,
        className: "custom-stammtisch-marker",
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -40],
      });
    }

    return stammtischIcon!;
  }

  function createPopupContent(location: StammtischLocation): string {
    const countryInfo = CountryMetadata.getCountryInfo(location.country);
    const tagsHtml = location.tags
      .map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`)
      .join("");

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

    return `
      <div class="popup-content">
        <h3 class="popup-title">
          ${countryInfo.flag} ${locationName}
        </h3>
        <p class="popup-location">
          ${locationText}
        </p>
        <p class="popup-frequency">
          ${frequencyText}
        </p>
        <div class="popup-tags">
          ${tagsHtml}
        </div>
        <button class="popup-button" onclick="this.dispatchEvent(new CustomEvent('show-details', { bubbles: true, detail: '${locationId}' }))">
          ${buttonLabel}
        </button>
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

  // React to store changes
  $: if (map && L) {
    updateMarkers($filteredStammtischLocations);
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
    overflow: hidden;
    box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.1),
      0 2px 4px -1px rgba(0, 0, 0, 0.06);
    background-color: #f3f4f6;
    z-index: 1;
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
    width: 30px;
    height: 40px;
  }

  :global(.marker-pin) {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: 3px solid #ffffff;
    border-radius: 50% 50% 50% 0;
    transform: translateX(-50%) rotate(-45deg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    z-index: 2;
  }

  :global(.marker-icon) {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
    font-size: 14px;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  }

  :global(.marker-shadow) {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 8px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 50%;
    filter: blur(2px);
    z-index: 1;
  }

  /* Hover effect for markers */
  :global(.custom-stammtisch-marker:hover .marker-pin) {
    transform: translateX(-50%) rotate(-45deg) scale(1.1);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.4);
    transition: all 0.2s ease-in-out;
  }

  /* Updated Popup Styles */
  :global(.leaflet-popup-content-wrapper) {
    background: #1f2937 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
  }

  :global(.leaflet-popup-content) {
    margin: 0 !important;
    padding: 1rem !important;
    color: #e5e7eb !important;
  }

  :global(.leaflet-popup-tip) {
    background: #1f2937 !important;
  }

  /* Popup Styles */
  :global(.stammtisch-popup .popup-content) {
    font-family:
      system-ui,
      -apple-system,
      sans-serif;
    padding: 0.5rem;
  }

  :global(.stammtisch-popup .popup-title) {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #f3f4f6 !important;
  }

  :global(.stammtisch-popup .popup-location) {
    margin: 0.25rem 0;
    color: #d1d5db !important;
    font-size: 0.875rem;
  }

  :global(.stammtisch-popup .popup-frequency) {
    margin: 0.25rem 0 0.5rem 0;
    color: #d1d5db !important;
    font-size: 0.875rem;
  }

  :global(.stammtisch-popup .popup-tags) {
    margin: 0.5rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }

  :global(.stammtisch-popup .tag) {
    background-color: #4f46e5;
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  :global(.stammtisch-popup .popup-button) {
    background-color: #4f46e5;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    margin-top: 0.5rem;
    transition: background-color 0.2s;
  }

  :global(.stammtisch-popup .popup-button:hover) {
    background-color: #3730a3;
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

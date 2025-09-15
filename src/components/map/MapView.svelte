<script lang="ts">
  import type { Map } from "leaflet";
  import { onDestroy, onMount } from "svelte";
  import {
    filteredLocations,
    mapViewport,
    openLocationDetails,
    updateMapViewport,
  } from "../../stores/map";
  import type { StammtischLocation } from "../../types/stammtisch";

  let mapContainer: HTMLDivElement;
  let map: Map;
  let markers: any[] = [];
  let L: typeof import("leaflet");

  // Props
  export let height: string = "500px";
  export let className: string = "";

  onMount(async () => {
    // Dynamically import Leaflet to avoid SSR issues
    L = await import("leaflet");

    // Fix for default marker icons in Leaflet with bundlers
    delete (L.Icon.Default.prototype as any)._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png",
      iconUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png",
      shadowUrl:
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png",
    });

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
    }
    // Remove event listener
    mapContainer?.removeEventListener(
      "show-details",
      handleShowDetails as EventListener,
    );
  });

  function initializeMap() {
    if (!mapContainer || !L) return;

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
      minZoom: 5, // Prevent zooming out too far
      maxZoom: 15, // Reasonable max zoom for city level
    });

    // Add tile layer (OpenStreetMap)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 15,
      minZoom: 5,
    }).addTo(map);

    // Add DACH region highlight overlay
    addDachRegionHighlight();

    // Add DACH region highlight overlay
    addDachRegionHighlight();

    // Update viewport store when map moves
    map.on("moveend", () => {
      const center = map.getCenter();
      const zoom = map.getZoom();
      updateMapViewport({
        center: { lat: center.lat, lng: center.lng },
        zoom,
      });
    });

    // Update markers when locations change
    updateMarkers($filteredLocations);
  }

  function addDachRegionHighlight() {
    if (!map || !L) return;

    // Approximate DACH region boundaries as LatLng tuples
    const dachRegions: L.LatLngExpression[][] = [
      // Germany
      [
        [54.983, 5.866], // Northwest
        [54.983, 15.042], // Northeast
        [47.271, 15.042], // Southeast
        [47.271, 5.866], // Southwest
        [54.983, 5.866], // Close polygon
      ],
      // Austria
      [
        [49.021, 9.531],
        [49.021, 17.161],
        [46.372, 17.161],
        [46.372, 9.531],
        [49.021, 9.531],
      ],
      // Switzerland
      [
        [47.808, 5.956],
        [47.808, 10.492],
        [45.818, 10.492],
        [45.818, 5.956],
        [47.808, 5.956],
      ],
    ];

    // Add subtle highlighting for DACH regions
    dachRegions.forEach((region) => {
      L.polygon(region, {
        color: "#4F46E5", // Indigo border
        weight: 2,
        opacity: 0.3,
        fillColor: "#4F46E5",
        fillOpacity: 0.05,
        interactive: false, // Don't interfere with map interactions
      }).addTo(map);
    });
  }

  function updateMarkers(locations: StammtischLocation[]) {
    if (!map || !L) return;

    // Clear existing markers
    markers.forEach((marker: any) => map.removeLayer(marker));
    markers.length = 0;

    // Custom pin icon for stammtische
    const stammtischIcon = L.divIcon({
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

    // Add markers for current locations
    locations.forEach((location) => {
      const marker = L.marker(
        [location.coordinates.lat, location.coordinates.lng],
        {
          icon: stammtischIcon,
        },
      ).addTo(map);

      // Create popup content
      const popupContent = createPopupContent(location);
      marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: "stammtisch-popup",
      });

      // Handle marker click
      marker.on("click", () => {
        openLocationDetails(location);
      });

      markers.push(marker);
    });
  }

  function createPopupContent(location: StammtischLocation): string {
    const countryFlag = getCountryFlag(location.country);
    const tags = location.tags
      .map((tag) => `<span class="tag">${tag}</span>`)
      .join(" ");

    return `
      <div class="popup-content">
        <h3 class="popup-title">
          ${countryFlag} ${location.name}
        </h3>
        <p class="popup-location">
          📍 ${location.city}, ${location.region}
        </p>
        <p class="popup-frequency">
          🗓️ ${location.meetingInfo.frequency}
        </p>
        <div class="popup-tags">
          ${tags}
        </div>
        <button class="popup-button" onclick="this.dispatchEvent(new CustomEvent('show-details', { bubbles: true, detail: '${location.id}' }))">
          Mehr Details →
        </button>
      </div>
    `;
  }

  function getCountryFlag(country: string): string {
    const flags: Record<string, string> = {
      DE: "🇩🇪",
      AT: "🇦🇹",
      CH: "🇨🇭",
    };
    return flags[country] || "🏁";
  }

  // React to store changes
  $: if (map && L) {
    updateMarkers($filteredLocations);
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
    const location = $filteredLocations.find((l) => l.id === locationId);
    if (location) {
      openLocationDetails(location);
    }
  }
</script>

<!-- svelte-ignore a11y-click-events-have-key-events -->
<!-- svelte-ignore a11y-no-static-element-interactions -->
<div
  bind:this={mapContainer}
  class="map-container {className}"
  style="height: {height}; min-height: 400px;"
  role="application"
  aria-label="Interaktive Karte mit Stammtisch-Standorten"
  tabindex="-1"
>
  <!-- Loading indicator -->
  {#if !map}
    <div class="map-loading">
      <div class="loading-spinner"></div>
      <p>Karte wird geladen...</p>
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
</style>

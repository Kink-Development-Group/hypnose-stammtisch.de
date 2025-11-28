<script lang="ts">
  import { onMount } from "svelte";
  import { CountryCode } from "../../enums/countryCode";
  import { LocationStatus } from "../../enums/locationStatus";
  import type { StammtischLocation } from "../../types/stammtisch";
  import { t } from "../../utils/i18n";
  import Portal from "../ui/Portal.svelte";

  /**
   * Statistics interface for location data aggregation.
   */
  interface LocationStats {
    total: number;
    published: number;
    draft: number;
    archived: number;
    active: number;
    inactive: number;
    by_country: Array<{ country: string; count: number }>;
    by_region: Array<{ region: string; country: string; count: number }>;
  }

  /**
   * Form data interface matching the API contract.
   */
  interface LocationFormData {
    name: string;
    city: string;
    region: string;
    country: CountryCode;
    latitude: number;
    longitude: number;
    description: string;
    contact_email: string;
    contact_phone: string;
    contact_fetlife: string;
    contact_website: string;
    meeting_frequency: string;
    meeting_location: string;
    meeting_address: string;
    next_meeting: string;
    tags: string[];
    is_active: boolean;
    status: LocationStatus;
  }

  let locations: StammtischLocation[] = [];
  let stats: LocationStats | null = null;
  let loading = true;
  let error = "";
  let showCreateForm = false;
  let editingLocation: StammtischLocation | null = null;
  let selectedLocations: string[] = [];

  // Form state
  let formData: LocationFormData = {
    name: "",
    city: "",
    region: "",
    country: CountryCode.GERMANY,
    latitude: 0,
    longitude: 0,
    description: "",
    contact_email: "",
    contact_phone: "",
    contact_fetlife: "",
    contact_website: "",
    meeting_frequency: "",
    meeting_location: "",
    meeting_address: "",
    next_meeting: "",
    tags: [],
    is_active: true,
    status: LocationStatus.DRAFT,
  };

  let tagInput = "";
  let availableTags = [
    "anfängerfreundlich",
    "erfahren",
    "praxis",
    "theorie",
    "wissenschaftlich",
    "forschung",
    "akademisch",
    "sicherheit",
  ];

  const countryOptions = [
    { code: CountryCode.GERMANY, name: "Deutschland", flag: "🇩🇪" },
    { code: CountryCode.AUSTRIA, name: "Österreich", flag: "🇦🇹" },
    { code: CountryCode.SWITZERLAND, name: "Schweiz", flag: "🇨🇭" },
  ];

  onMount(async () => {
    await loadLocations();
    await loadStats();
  });

  /**
   * Fetch all stammtisch locations from the API.
   * @returns Promise that resolves when locations are loaded
   */
  /**
   * Fetch all stammtisch locations from the API.
   * @returns Promise that resolves when locations are loaded
   */
  async function loadLocations(): Promise<void> {
    try {
      loading = true;
      error = "";

      const response = await fetch("/api/admin/stammtisch-locations", {
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to load locations: ${response.status}`);
      }

      const result = await response.json();
      if (result.success) {
        locations = result.data || [];
      } else {
        throw new Error(result.message || "Failed to load locations");
      }
    } catch (err) {
      error = err instanceof Error ? err.message : "Unknown error occurred";
      console.error("Error loading locations:", err);
    } finally {
      loading = false;
    }
  }

  /**
   * Fetch location statistics for the dashboard.
   * @returns Promise that resolves when stats are loaded
   */
  async function loadStats(): Promise<void> {
    try {
      const response = await fetch("/api/admin/stammtisch-locations/stats", {
        credentials: "same-origin",
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          stats = result.data;
        }
      }
    } catch (err) {
      console.error("Error loading stats:", err);
    }
  }

  /**
   * Reset form to initial empty state.
   */
  function resetForm(): void {
    formData = {
      name: "",
      city: "",
      region: "",
      country: CountryCode.GERMANY,
      latitude: 0,
      longitude: 0,
      description: "",
      contact_email: "",
      contact_phone: "",
      contact_fetlife: "",
      contact_website: "",
      meeting_frequency: "",
      meeting_location: "",
      meeting_address: "",
      next_meeting: "",
      tags: [],
      is_active: true,
      status: LocationStatus.DRAFT,
    };
    tagInput = "";
    editingLocation = null;
  }

  /**
   * Open modal for creating a new location.
   */
  function startCreate(): void {
    resetForm();
    showCreateForm = true;
  }

  /**
   * Open modal for editing an existing location.
   * @param location - The location to edit
   */
  function startEdit(location: StammtischLocation): void {
    // Handle both coordinate structures: coordinates.lat/lng or direct latitude/longitude
    const lat = location.coordinates?.lat ?? (location as any).latitude ?? 0;
    const lng = location.coordinates?.lng ?? (location as any).longitude ?? 0;

    formData = {
      name: location.name,
      city: location.city,
      region: location.region,
      country: location.country as CountryCode,
      latitude: lat,
      longitude: lng,
      description: location.description,
      contact_email: location.contact?.email || "",
      contact_phone: location.contact?.phone || "",
      contact_fetlife: location.contact?.fetlife || "",
      contact_website: location.contact?.website || "",
      meeting_frequency: location.meetingInfo?.frequency || "",
      meeting_location: location.meetingInfo?.location || "",
      meeting_address: location.meetingInfo?.address || "",
      next_meeting: location.meetingInfo?.nextMeeting || "",
      tags: [...(location.tags || [])],
      is_active: location.isActive,
      status: location.status as LocationStatus,
    };
    editingLocation = location;
    showCreateForm = true;
  }

  /**
   * Close form modal and reset state.
   */
  function cancelForm(): void {
    showCreateForm = false;
    resetForm();
  }

  /**
   * Add custom tag to the location.
   */
  function addTag(): void {
    const tag = tagInput.trim();
    if (tag && !formData.tags.includes(tag)) {
      formData.tags = [...formData.tags, tag];
      tagInput = "";
    }
  }

  /**
   * Remove a tag from the location.
   * @param tag - Tag to remove
   */
  function removeTag(tag: string): void {
    formData.tags = formData.tags.filter((t) => t !== tag);
  }

  /**
   * Add a predefined tag to the location.
   * @param tag - Tag to add
   */
  function addAvailableTag(tag: string): void {
    if (!formData.tags.includes(tag)) {
      formData.tags = [...formData.tags, tag];
    }
  }

  /**
   * Save location (create or update) via API.
   * @returns Promise that resolves when save is complete
   */
  async function saveLocation(): Promise<void> {
    try {
      const url = editingLocation
        ? `/api/admin/stammtisch-locations/${editingLocation.id}`
        : "/api/admin/stammtisch-locations";

      const method = editingLocation ? "PUT" : "POST";

      const response = await fetch(url, {
        method,
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const result = await response.json();
      if (result.success) {
        await loadLocations();
        await loadStats();
        cancelForm();
      } else {
        throw new Error(result.message || "Failed to save location");
      }
    } catch (err) {
      error = err instanceof Error ? err.message : "Unknown error occurred";
      console.error("Error saving location:", err);
    }
  }

  /**
   * Delete a location with confirmation.
   * @param location - Location to delete
   * @returns Promise that resolves when deletion is complete
   */
  async function deleteLocation(location: StammtischLocation): Promise<void> {
    if (
      !confirm(
        `Möchten Sie den Stammtisch "${location.name}" wirklich löschen?`,
      )
    ) {
      return;
    }

    try {
      const response = await fetch(
        `/api/admin/stammtisch-locations/${location.id}`,
        {
          method: "DELETE",
          credentials: "same-origin",
        },
      );

      if (!response.ok) {
        throw new Error(`Failed to delete location: ${response.status}`);
      }

      const result = await response.json();
      if (result.success) {
        await loadLocations();
        await loadStats();
      } else {
        throw new Error(result.message || "Failed to delete location");
      }
    } catch (err) {
      error = err instanceof Error ? err.message : "Unknown error occurred";
      console.error("Error deleting location:", err);
    }
  }

  /**
   * Toggle selection state of a location.
   * @param locationId - ID of location to toggle
   */
  function toggleLocationSelection(locationId: string): void {
    if (selectedLocations.includes(locationId)) {
      selectedLocations = selectedLocations.filter((id) => id !== locationId);
    } else {
      selectedLocations = [...selectedLocations, locationId];
    }
  }

  /**
   * Select all locations in the current view.
   */
  function selectAllLocations(): void {
    selectedLocations = locations.map((l) => l.id);
  }

  /**
   * Clear all location selections.
   */
  function clearSelection(): void {
    selectedLocations = [];
  }

  /**
   * Update status for multiple selected locations.
   * @param status - New status to apply
   * @returns Promise that resolves when bulk update is complete
   */
  async function bulkUpdateStatus(status: LocationStatus): Promise<void> {
    if (selectedLocations.length === 0) {
      alert("Bitte wählen Sie mindestens einen Stammtisch aus.");
      return;
    }

    if (
      !confirm(
        `Möchten Sie ${selectedLocations.length} Stammtische auf "${status}" setzen?`,
      )
    ) {
      return;
    }

    try {
      const response = await fetch(
        "/api/admin/stammtisch-locations/bulk-status",
        {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            ids: selectedLocations,
            status,
          }),
        },
      );

      if (!response.ok) {
        throw new Error(`Failed to update status: ${response.status}`);
      }

      const result = await response.json();
      if (result.success) {
        await loadLocations();
        await loadStats();
        clearSelection();
      } else {
        throw new Error(result.message || "Failed to update status");
      }
    } catch (err) {
      error = err instanceof Error ? err.message : "Unknown error occurred";
      console.error("Error updating status:", err);
    }
  }

  /**
   * Get country flag emoji for a country code.
   * @param country - Country code (DE, AT, CH)
   * @returns Flag emoji string
   */
  function getCountryFlag(country: string): string {
    const countryData = countryOptions.find((c) => c.code === country);
    return countryData ? countryData.flag : "🏁";
  }

  /**
   * Get CSS class for status badge.
   * @param status - Location status
   * @returns Tailwind CSS classes
   */
  function getStatusBadgeClass(status: string): string {
    switch (status) {
      case LocationStatus.PUBLISHED:
        return "bg-green-100 text-green-800";
      case LocationStatus.DRAFT:
        return "bg-yellow-100 text-yellow-800";
      case LocationStatus.ARCHIVED:
        return "bg-gray-100 text-gray-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  /**
   * Get human-readable status text.
   * @param status - Location status
   * @returns Localized status text
   */
  function getStatusText(status: string): string {
    switch (status) {
      case LocationStatus.PUBLISHED:
        return "Veröffentlicht";
      case LocationStatus.DRAFT:
        return "Entwurf";
      case LocationStatus.ARCHIVED:
        return "Archiviert";
      default:
        return status;
    }
  }
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Stammtisch-Standorte</h1>
      <p class="text-gray-600">
        Verwalten Sie die Stammtisch-Standorte auf der Karte
      </p>
    </div>
    <button
      on:click={startCreate}
      class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
    >
      Neuen Standort erstellen
    </button>
  </div>

  <!-- Error Message -->
  {#if error}
    <div
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      {error}
    </div>
  {/if}

  <!-- Statistics -->
  {#if stats}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-lg shadow border">
        <div class="text-2xl font-bold text-blue-600">{stats.total}</div>
        <div class="text-sm text-gray-600">Standorte gesamt</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border">
        <div class="text-2xl font-bold text-green-600">{stats.published}</div>
        <div class="text-sm text-gray-600">Veröffentlicht</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border">
        <div class="text-2xl font-bold text-yellow-600">{stats.draft}</div>
        <div class="text-sm text-gray-600">Entwürfe</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border">
        <div class="text-2xl font-bold text-gray-600">{stats.archived}</div>
        <div class="text-sm text-gray-600">Archiviert</div>
      </div>
    </div>
  {/if}

  <!-- Bulk Actions -->
  {#if selectedLocations.length > 0}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <span class="text-blue-700">
          {selectedLocations.length} Standort(e) ausgewählt
        </span>
        <div class="flex space-x-2">
          <button
            on:click={() => bulkUpdateStatus(LocationStatus.PUBLISHED)}
            class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700"
          >
            Veröffentlichen
          </button>
          <button
            on:click={() => bulkUpdateStatus(LocationStatus.DRAFT)}
            class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700"
          >
            Als Entwurf
          </button>
          <button
            on:click={() => bulkUpdateStatus(LocationStatus.ARCHIVED)}
            class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700"
          >
            Archivieren
          </button>
          <button
            on:click={clearSelection}
            class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700"
          >
            Auswahl aufheben
          </button>
        </div>
      </div>
    </div>
  {/if}

  <!-- Locations Table -->
  <div class="bg-white shadow border rounded-lg overflow-hidden">
    {#if loading}
      <div class="p-8 text-center">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
        ></div>
        <p class="mt-2 text-gray-600">Lade Standorte...</p>
      </div>
    {:else if locations.length === 0}
      <div class="p-8 text-center text-gray-500">
        <p>Noch keine Stammtisch-Standorte vorhanden.</p>
        <button
          on:click={startCreate}
          class="mt-2 text-blue-600 hover:text-blue-800"
        >
          Erstellen Sie den ersten Standort
        </button>
      </div>
    {:else}
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              <input
                type="checkbox"
                checked={selectedLocations.length === locations.length}
                on:change={(e) => {
                  const target = e.target as HTMLInputElement;
                  if (target.checked) {
                    selectAllLocations();
                  } else {
                    clearSelection();
                  }
                }}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Name & Ort
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Land/Region
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Tags
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Aktionen
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          {#each locations as location (location.id)}
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  checked={selectedLocations.includes(location.id)}
                  on:change={() => toggleLocationSelection(location.id)}
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {location.name}
                </div>
                <div class="text-sm text-gray-500">{location.city}</div>
                {#if location.coordinates}
                  <div class="text-xs text-gray-400">
                    {location.coordinates.lat.toFixed(4)}, {location.coordinates.lng.toFixed(
                      4,
                    )}
                  </div>
                {/if}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-1">
                  <span class="text-lg">{getCountryFlag(location.country)}</span
                  >
                  <div>
                    <div class="text-sm text-gray-900">{location.region}</div>
                    <div class="text-xs text-gray-500">{location.country}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {getStatusBadgeClass(
                    location.status,
                  )}"
                >
                  {getStatusText(location.status)}
                </span>
                {#if !location.isActive}
                  <span
                    class="ml-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"
                  >
                    Inaktiv
                  </span>
                {/if}
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  {#each location.tags.slice(0, 3) as tag (tag)}
                    <span
                      class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded"
                    >
                      {tag}
                    </span>
                  {/each}
                  {#if location.tags.length > 3}
                    <span
                      class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded"
                    >
                      +{location.tags.length - 3}
                    </span>
                  {/if}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    on:click={() => startEdit(location)}
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Bearbeiten
                  </button>
                  <button
                    on:click={() => deleteLocation(location)}
                    class="text-red-600 hover:text-red-900"
                  >
                    Löschen
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    {/if}
  </div>
</div>

<!-- Create/Edit Form Modal -->
{#if showCreateForm}
  <Portal>
    <div
      class="fixed inset-0 bg-gray-700/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
    >
      <div
        class="relative mx-auto mt-8 md:mt-12 border w-11/12 max-w-5xl shadow-2xl rounded-lg bg-white flex flex-col max-h-[92vh]"
      >
        <!-- Header (sticky) -->
        <div
          class="px-6 py-5 border-b bg-gradient-to-r from-gray-50 to-white sticky top-0 z-10 rounded-t-lg"
        >
          <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
              <h3 class="text-xl font-semibold text-gray-900 leading-tight">
                {editingLocation
                  ? t("adminLocations.modal.titleEdit")
                  : t("adminLocations.modal.titleCreate")}
              </h3>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="text-xs px-2 py-1 rounded border shadow-sm hover:bg-gray-100 focus:outline-none focus:ring"
                on:click={cancelForm}
              >
                {t("adminLocations.modal.close")}
              </button>
              <button
                type="button"
                class="text-xs px-2 py-1 rounded border shadow-sm hover:bg-gray-100 focus:outline-none focus:ring"
                on:click={resetForm}
              >
                {t("adminLocations.modal.reset")}
              </button>
            </div>
          </div>
        </div>

        <!-- Scrollable Form Content -->
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <form on:submit|preventDefault={saveLocation} class="space-y-6">
            <!-- Basic Information -->
            <fieldset class="space-y-4">
              <legend class="text-lg font-medium text-gray-900 mb-4">
                Grundinformationen
              </legend>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name -->
                <div>
                  <label
                    for="name-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.nameLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <input
                    id="name-input"
                    type="text"
                    bind:value={formData.name}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.namePlaceholder")}
                  />
                </div>

                <!-- City -->
                <div>
                  <label
                    for="city-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.cityLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <input
                    id="city-input"
                    type="text"
                    bind:value={formData.city}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.cityPlaceholder")}
                  />
                </div>

                <!-- Region -->
                <div>
                  <label
                    for="region-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.regionLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <input
                    id="region-input"
                    type="text"
                    bind:value={formData.region}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.regionPlaceholder")}
                  />
                </div>

                <!-- Country -->
                <div>
                  <label
                    for="country-select"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.countryLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <select
                    id="country-select"
                    bind:value={formData.country}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    {#each countryOptions as country (country.code)}
                      <option value={country.code}>
                        {country.flag}
                        {country.name}
                      </option>
                    {/each}
                  </select>
                </div>

                <!-- Latitude -->
                <div>
                  <label
                    for="latitude-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.latitudeLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <input
                    id="latitude-input"
                    type="number"
                    step="any"
                    bind:value={formData.latitude}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.latitudePlaceholder")}
                  />
                </div>

                <!-- Longitude -->
                <div>
                  <label
                    for="longitude-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.longitudeLabel")}
                    <span class="text-red-600">*</span>
                  </label>
                  <input
                    id="longitude-input"
                    type="number"
                    step="any"
                    bind:value={formData.longitude}
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.longitudePlaceholder")}
                  />
                </div>
              </div>

              <!-- Description -->
              <div>
                <label
                  for="description-textarea"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  {t("adminLocations.form.descriptionLabel")}
                </label>
                <textarea
                  id="description-textarea"
                  bind:value={formData.description}
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder={t("adminLocations.form.descriptionPlaceholder")}
                ></textarea>
              </div>
            </fieldset>

            <!-- Contact Information -->
            <fieldset class="space-y-4">
              <legend class="text-lg font-medium text-gray-900 mb-4">
                {t("adminLocations.form.contactSectionTitle")}
              </legend>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="email-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.emailLabel")}
                  </label>
                  <input
                    id="email-input"
                    type="email"
                    bind:value={formData.contact_email}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.emailPlaceholder")}
                  />
                </div>

                <div>
                  <label
                    for="phone-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.phoneLabel")}
                  </label>
                  <input
                    id="phone-input"
                    type="tel"
                    bind:value={formData.contact_phone}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.phonePlaceholder")}
                  />
                </div>

                <div>
                  <label
                    for="fetlife-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.fetlifeLabel")}
                  </label>
                  <input
                    id="fetlife-input"
                    type="text"
                    bind:value={formData.contact_fetlife}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.fetlifePlaceholder")}
                  />
                </div>

                <div>
                  <label
                    for="website-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.websiteLabel")}
                  </label>
                  <input
                    id="website-input"
                    type="url"
                    bind:value={formData.contact_website}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.websitePlaceholder")}
                  />
                </div>
              </div>
            </fieldset>

            <!-- Meeting Information -->
            <fieldset class="space-y-4">
              <legend class="text-lg font-medium text-gray-900 mb-4">
                {t("adminLocations.form.meetingSectionTitle")}
              </legend>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="frequency-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.frequencyLabel")}
                  </label>
                  <input
                    id="frequency-input"
                    type="text"
                    bind:value={formData.meeting_frequency}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.frequencyPlaceholder")}
                  />
                </div>

                <div>
                  <label
                    for="location-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.locationLabel")}
                  </label>
                  <input
                    id="location-input"
                    type="text"
                    bind:value={formData.meeting_location}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.locationPlaceholder")}
                  />
                </div>

                <div class="md:col-span-2">
                  <label
                    for="address-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.addressLabel")}
                  </label>
                  <input
                    id="address-input"
                    type="text"
                    bind:value={formData.meeting_address}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder={t("adminLocations.form.addressPlaceholder")}
                  />
                </div>

                <div>
                  <label
                    for="next-meeting-input"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.nextMeetingLabel")}
                  </label>
                  <input
                    id="next-meeting-input"
                    type="datetime-local"
                    bind:value={formData.next_meeting}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </fieldset>

            <!-- Tags -->
            <fieldset class="space-y-4">
              <legend class="text-lg font-medium text-gray-900 mb-4">
                {t("adminLocations.form.tagsSectionTitle")}
              </legend>

              <!-- Available Tags -->
              <div class="mb-3">
                <p class="text-sm text-gray-600 mb-2">
                  {t("adminLocations.form.tagsAvailable")}
                </p>
                <div class="flex flex-wrap gap-2">
                  {#each availableTags as tag (tag)}
                    <button
                      type="button"
                      on:click={() => addAvailableTag(tag)}
                      disabled={formData.tags.includes(tag)}
                      class="px-3 py-1.5 text-sm bg-gray-50 border-2 border-gray-300 text-gray-700 rounded-md hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-gray-50 disabled:hover:border-gray-300 disabled:hover:text-gray-700"
                    >
                      {tag}
                    </button>
                  {/each}
                </div>
              </div>

              <!-- Custom Tag Input -->
              <div class="flex mb-3">
                <input
                  type="text"
                  bind:value={tagInput}
                  on:keydown={(e) =>
                    e.key === "Enter" && (e.preventDefault(), addTag())}
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder={t("adminLocations.form.tagInputPlaceholder")}
                />
                <button
                  type="button"
                  on:click={addTag}
                  class="px-4 py-2 bg-blue-600 text-white border border-blue-600 rounded-r-md hover:bg-blue-700 hover:border-blue-700 transition-colors font-medium"
                >
                  {t("adminLocations.form.tagAdd")}
                </button>
              </div>

              <!-- Selected Tags -->
              <div class="flex flex-wrap gap-2">
                {#each formData.tags as tag (tag)}
                  <span
                    class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded"
                  >
                    {tag}
                    <button
                      type="button"
                      on:click={() => removeTag(tag)}
                      class="ml-1 text-blue-600 hover:text-blue-800"
                    >
                      ×
                    </button>
                  </span>
                {/each}
              </div>
            </fieldset>

            <!-- Status and Active -->
            <fieldset class="space-y-4">
              <legend class="text-lg font-medium text-gray-900 mb-4">
                Status & Sichtbarkeit
              </legend>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="status-select"
                    class="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t("adminLocations.form.statusLabel")}
                  </label>
                  <select
                    id="status-select"
                    bind:value={formData.status}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value={LocationStatus.DRAFT}>
                      {t("adminLocations.form.statusDraft")}
                    </option>
                    <option value={LocationStatus.PUBLISHED}>
                      {t("adminLocations.form.statusPublished")}
                    </option>
                    <option value={LocationStatus.ARCHIVED}>
                      {t("adminLocations.form.statusArchived")}
                    </option>
                  </select>
                </div>

                <div class="flex items-center">
                  <input
                    type="checkbox"
                    id="is_active"
                    bind:checked={formData.is_active}
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <label
                    for="is_active"
                    class="ml-2 text-sm font-medium text-gray-700"
                  >
                    {t("adminLocations.form.isActiveLabel")}
                  </label>
                </div>
              </div>
            </fieldset>
          </form>
        </div>

        <!-- Footer (sticky) -->
        <div
          class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-between items-center rounded-b-lg gap-4"
        >
          <div class="text-xs text-gray-400">
            <span class="text-red-600">*</span> = {t(
              "adminLocations.form.required",
            )}
          </div>
          <div class="flex gap-3">
            <button
              type="button"
              on:click={cancelForm}
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
            >
              {t("adminLocations.form.cancel")}
            </button>
            <button
              type="submit"
              on:click={saveLocation}
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              {editingLocation
                ? t("adminLocations.form.update")
                : t("adminLocations.form.create")}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Portal>
{/if}

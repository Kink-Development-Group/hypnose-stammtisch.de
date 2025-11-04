<script lang="ts">
  import { CountryMetadata } from "../../classes/CountryMetadata";
  import { CountryCode } from "../../enums/countryCode";
  import {
    closeLocationDetails,
    selectedLocation,
  } from "../../stores/api-map-locations";
  import type { StammtischLocation } from "../../types/stammtisch";
  import { formatDateTime, t } from "../../utils/i18n";

  export let location: StammtischLocation | null = null;

  $: currentLocation = location || $selectedLocation;

  type ContactChannel = keyof StammtischLocation["contact"];

  function handleClose(): void {
    closeLocationDetails();
  }

  function getCountryFlag(country: CountryCode): string {
    return CountryMetadata.getCountryInfo(country).flag;
  }

  function getCountryName(country: CountryCode): string {
    return CountryMetadata.getDisplayName(country);
  }

  function formatNextMeeting(dateString: string | undefined): string {
    if (!dateString) {
      return t("map.details.nextMeetingUnknown");
    }

    const date = new Date(dateString);
    if (Number.isNaN(date.valueOf())) {
      return t("map.details.nextMeetingInvalid");
    }

    return formatDateTime(date, {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function getLastUpdatedLabel(target: StammtischLocation): string {
    if (!target.lastUpdated) {
      return t("map.details.lastUpdatedUnknown");
    }

    const date = new Date(target.lastUpdated);
    if (Number.isNaN(date.valueOf())) {
      return t("map.details.lastUpdatedUnknown");
    }

    return formatDateTime(date, {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  }

  function handleContactClick(
    type: ContactChannel,
    value: string | undefined,
  ): void {
    if (!value) return;

    switch (type) {
      case "email":
        window.open(`mailto:${value}`);
        break;
      case "website": {
        const href = value.startsWith("http") ? value : `https://${value}`;
        window.open(href, "_blank", "noopener");
        break;
      }
      case "phone":
        window.open(`tel:${value}`);
        break;
      case "telegram": {
        const handle = value.replace(/^@/, "");
        window.open(`https://t.me/${handle}`, "_blank", "noopener");
        break;
      }
      case "discord":
        if (navigator.clipboard?.writeText) {
          navigator.clipboard
            .writeText(value)
            .then(() => {
              window.alert?.(
                t("map.details.contact.discordCopied", {
                  values: { handle: value },
                }),
              );
            })
            .catch(() => {
              window.alert?.(
                t("map.details.contact.copyFallback", {
                  values: { handle: value },
                }),
              );
            });
        } else {
          window.prompt?.(
            t("map.details.contact.copyFallback", {
              values: { handle: value },
            }),
            value,
          );
        }
        break;
      default:
        break;
    }
  }
</script>

{#if currentLocation}
  <div
    class="location-details"
    role="dialog"
    aria-labelledby="location-title"
    aria-describedby="location-description"
  >
    <!-- Header -->
    <header class="details-header">
      <div class="title-section">
        <h2 id="location-title" class="location-title">
          {getCountryFlag(currentLocation.country)}
          {currentLocation.name}
        </h2>
        <p class="location-subtitle">
          {t("map.details.locationSummary", {
            values: {
              city: currentLocation.city,
              region: currentLocation.region,
              country: getCountryName(currentLocation.country),
            },
          })}
        </p>
      </div>

      {#if !location}
        <button
          class="close-button"
          on:click={handleClose}
          aria-label={t("map.details.close")}
        >
          ✕
        </button>
      {/if}
    </header>

    <!-- Content -->
    <div class="details-content">
      <!-- Description -->
      <section class="content-section">
        <h3 class="section-title">{t("map.details.aboutTitle")}</h3>
        <p id="location-description" class="description">
          {currentLocation.description}
        </p>
      </section>

      <!-- Meeting Info -->
      <section class="content-section">
        <h3 class="section-title">{t("map.details.meetingsTitle")}</h3>
        <div class="meeting-info">
          <div class="info-item">
            <span class="info-label">{t("map.details.labels.frequency")}:</span>
            <span class="info-value">
              {currentLocation.meetingInfo.frequency ??
                t("map.details.frequencyUnknown")}
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">{t("map.details.labels.location")}:</span>
            <span class="info-value">
              {currentLocation.meetingInfo.location ??
                t("map.details.locationUnknown")}
            </span>
          </div>
          {#if currentLocation.meetingInfo.nextMeeting}
            <div class="info-item">
              <span class="info-label">
                {t("map.details.labels.nextMeeting")}:
              </span>
              <span class="info-value next-meeting">
                {formatNextMeeting(currentLocation.meetingInfo.nextMeeting)}
              </span>
            </div>
          {/if}
        </div>
      </section>

      <!-- Tags -->
      {#if currentLocation.tags.length > 0}
        <section class="content-section">
          <h3 class="section-title">{t("map.details.tagsTitle")}</h3>
          <div class="tags">
            {#each currentLocation.tags as tag (tag)}
              <span class="tag">{tag}</span>
            {/each}
          </div>
        </section>
      {/if}

      <!-- Contact Info -->
      {#if Object.values(currentLocation.contact).some((v) => v)}
        <section class="content-section">
          <h3 class="section-title">{t("map.details.contactTitle")}</h3>
          <div class="contact-info">
            {#if currentLocation.contact.email}
              <button
                class="contact-button email"
                on:click={() =>
                  handleContactClick("email", currentLocation.contact.email)}
              >
                📧 {t("map.details.contact.email")}
              </button>
            {/if}

            {#if currentLocation.contact.website}
              <button
                class="contact-button website"
                on:click={() =>
                  handleContactClick(
                    "website",
                    currentLocation.contact.website,
                  )}
              >
                🌐 {t("map.details.contact.website")}
              </button>
            {/if}

            {#if currentLocation.contact.phone}
              <button
                class="contact-button phone"
                on:click={() =>
                  handleContactClick("phone", currentLocation.contact.phone)}
              >
                📞 {t("map.details.contact.phone")}
              </button>
            {/if}

            {#if currentLocation.contact.telegram}
              <button
                class="contact-button telegram"
                on:click={() =>
                  handleContactClick(
                    "telegram",
                    currentLocation.contact.telegram,
                  )}
              >
                📱
                {t("map.details.contact.telegram", {
                  values: { handle: currentLocation.contact.telegram },
                })}
              </button>
            {/if}

            {#if currentLocation.contact.discord}
              <button
                class="contact-button discord"
                on:click={() =>
                  handleContactClick(
                    "discord",
                    currentLocation.contact.discord,
                  )}
              >
                🎮
                {t("map.details.contact.discord", {
                  values: { handle: currentLocation.contact.discord },
                })}
              </button>
            {/if}
          </div>
        </section>
      {/if}

      <!-- Status -->
      <section class="content-section">
        <div class="status-info">
          <span
            class="status-indicator"
            class:active={currentLocation.isActive}
          >
            {currentLocation.isActive
              ? t("map.details.status.active")
              : t("map.details.status.inactive")}
          </span>
          <span class="last-updated">
            {t("map.details.lastUpdated", {
              values: { date: getLastUpdatedLabel(currentLocation) },
            })}
          </span>
        </div>
      </section>
    </div>
  </div>
{/if}

<style>
  .location-details {
    background: white;
    border-radius: 0.75rem;
    box-shadow:
      0 10px 25px -5px rgba(0, 0, 0, 0.1),
      0 4px 6px -2px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
    max-height: 70vh;
    overflow-y: auto;
  }

  .details-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .title-section {
    flex: 1;
  }

  .location-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
  }

  .location-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 0.875rem;
  }

  .close-button {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.25rem;
    transition: background-color 0.2s;
    margin-left: 1rem;
  }

  .close-button:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .details-content {
    padding: 1.5rem;
  }

  .content-section {
    margin-bottom: 1.5rem;
  }

  .content-section:last-child {
    margin-bottom: 0;
  }

  .section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .description {
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
  }

  .meeting-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .info-item {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
  }

  .info-label {
    font-weight: 500;
    color: #374151;
    min-width: 80px;
    flex-shrink: 0;
  }

  .info-value {
    color: #6b7280;
    flex: 1;
  }

  .next-meeting {
    font-weight: 500;
    color: #059669;
  }

  .tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .tag {
    background: #eff6ff;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid #dbeafe;
  }

  .contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .contact-button {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    color: #374151;
  }

  .contact-button:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
  }

  .contact-button.email:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
  }

  .contact-button.website:hover {
    background: #f0f9ff;
    border-color: #bae6fd;
    color: #0284c7;
  }

  .contact-button.telegram:hover {
    background: #f0f9ff;
    border-color: #bae6fd;
    color: #0284c7;
  }

  .contact-button.discord:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #4b5563;
  }

  .status-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
  }

  .status-indicator {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .status-indicator.active {
    color: #059669;
  }

  .last-updated {
    font-size: 0.75rem;
    color: #9ca3af;
  }

  /* Responsive */
  @media (max-width: 640px) {
    .details-header {
      padding: 1rem;
    }

    .location-title {
      font-size: 1.25rem;
    }

    .details-content {
      padding: 1rem;
    }

    .status-info {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
  }
</style>

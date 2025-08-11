<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<section class="container mx-auto px-4 py-12 max-w-4xl prose prose-invert">
  <h1>Safety Guide</h1>
  <p class="lead">
    Grundlegende Hinweise für sichere, respektvolle und inklusive
    Hypnose-Community-Events.
  </p>
  <h2>Grundprinzipien</h2>
  <ul>
    <li>
      <strong>Einverständnis & Autonomie:</strong> Niemals Hypnose oder Trance ohne
      ausdrückliche Zustimmung durchführen.
    </li>
    <li>
      <strong>Transparenz:</strong> Erkläre Ablauf, Ziel und mögliche Empfindungen
      vor jeder Interaktion.
    </li>
    <li>
      <strong>Abbruchrecht:</strong> Teilnehmende können jederzeit ohne Begründung
      abbrechen.
    </li>
    <li>
      <strong>Nachsorge:</strong> Sorge für ein kurzes Debriefing und Raum für Fragen.
    </li>
  </ul>
  <h2>Verantwortungsvolle Durchführung</h2>
  <ul>
    <li>
      Vermeide medizinische oder therapeutische Aussagen, wenn Du nicht
      entsprechend qualifiziert bist.
    </li>
    <li>Keine Diagnose, Heilversprechen oder Behandlungssimulation.</li>
    <li>
      Neutralität bei sensiblen Themen (Trauma, psychische Gesundheit, etc.).
    </li>
  </ul>
  <h2>Barrierefreiheit & Inklusion</h2>
  <ul>
    <li>Sprache klar, respektvoll und genderbewusst.</li>
    <li>Optionalität bieten: Beobachten statt Mitmachen erlauben.</li>
    <li>Triggerhinweise bei potenziell belastenden Inhalten.</li>
  </ul>
  <h2>Vorbereitung für Einreichende</h2>
  <ol>
    <li>
      Klare Eventbeschreibung (Thema, Zielgruppe, Format, Voraussetzungen).
    </li>
    <li>
      Angabe von Dauer, Online-/Offline-Format, Sprache und Interaktivitätsgrad.
    </li>
    <li>Kontaktdaten für Rückfragen.</li>
  </ol>
  <h2>Verweise</h2>
  <p>
    Siehe auch unseren <a href="/code-of-conduct" use:link>Verhaltenskodex</a>
    und die <a href="/resources/faq" use:link>FAQ</a>.
  </p>
</section>

<section class="container mx-auto px-4 mt-12 mb-24 flex flex-wrap gap-4">
  <a href="/submit-event" use:link class="btn btn-outline">Event einreichen</a>
  <a href="/resources/faq" use:link class="btn btn-outline">FAQ</a>
  <a href="/events" use:link class="btn btn-outline">Events</a>
  <a href="/contact" use:link class="btn btn-outline">Kontakt</a>
</section>

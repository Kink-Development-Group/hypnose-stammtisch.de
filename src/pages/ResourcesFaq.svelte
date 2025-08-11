<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<section class="container mx-auto px-4 py-12 max-w-4xl prose prose-invert">
  <h1>FAQ – Häufige Fragen</h1>
  <h2>Wie reiche ich ein Event ein?</h2>
  <p>
    Nutze die Seite <a href="/submit-event" use:link>Event einreichen</a>. Alle
    Pflichtfelder ausfüllen und absenden – wir prüfen und veröffentlichen.
  </p>
  <h2>Welche Arten von Events sind erlaubt?</h2>
  <p>
    Community-orientierte transparente Bildungs- und Austauschformate rund um
    Hypnose & Trance oder Partyformate im Bereich der Kinky Hypnose.
  </p>
  <h2>Kostet die Nutzung etwas?</h2>
  <p>
    Nein. Betrieb und Weiterentwicklung erfolgen freiwillig. Unterstützung durch
    konstruktives Feedback ist willkommen.
  </p>
  <h2>Wie werden Daten verarbeitet?</h2>
  <p>
    Siehe unsere <a href="/privacy" use:link>Datenschutzerklärung</a>. Wir
    speichern nur notwendige Informationen.
  </p>
  <h2>Wie gehe ich mit Anliegen oder Verstößen um?</h2>
  <p>
    Melde Dich über das <a href="/contact" use:link>Kontaktformular</a>.
    Verstöße gegen den <a href="/code-of-conduct" use:link>Verhaltenskodex</a> werden
    geprüft.
  </p>
  <h2>Kann ich Inhalte nachträglich ändern?</h2>
  <p>Ja, schreibe uns – wir können Eventdaten aktualisieren.</p>
  <h2>Gibt es Richtlinien für Sicherheit?</h2>
  <p>
    Ja, siehe den <a href="/resources/safety-guide" use:link>Safety Guide</a>.
  </p>
</section>

<section class="container mx-auto px-4 mt-12 mb-24 flex flex-wrap gap-4">
  <a href="/submit-event" use:link class="btn btn-outline">Event einreichen</a>
  <a href="/resources/safety-guide" use:link class="btn btn-outline"
    >Safety Guide</a
  >
  <a href="/events" use:link class="btn btn-outline">Events</a>
  <a href="/contact" use:link class="btn btn-outline">Kontakt</a>
</section>

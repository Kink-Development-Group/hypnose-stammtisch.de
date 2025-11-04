<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Verhaltenskodex - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Unser Community-Verhaltenskodex für sichere, respektvolle und einverständnis-zentrierte Hypnose-Praktiken."
  />
</svelte:head>

<div class="container mx-auto px-4 py-8 max-w-4xl">
  <!-- Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Verhaltenskodex
    </h1>
    <p class="text-xl text-smoke-300">
      Unser Leitfaden für eine sichere, respektvolle und
      einverständnis-zentrierte Community
    </p>
    <div class="mt-6 text-sm text-smoke-400">
      Version 2.1 • Letzte Aktualisierung: Januar 2025
    </div>
  </header>

  <!-- Preamble -->
  <section class="mb-12 card">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      Unsere Grundprinzipien
    </h2>
    <div class="prose prose-invert max-w-none">
      <p class="text-smoke-300 leading-relaxed mb-4">
        Der Hypnose Stammtisch ist eine Gemeinschaft, die auf Respekt,
        Sicherheit und informiertem Einverständnis basiert. Dieser
        Verhaltenskodex definiert die Standards, die wir von allen
        Community-Mitgliedern erwarten.
      </p>
      <p class="text-smoke-300 leading-relaxed">
        Durch deine Teilnahme an unseren Events oder der Nutzung unserer
        Plattformen erklärst du dich mit diesen Richtlinien einverstanden und
        verpflichtest dich, diese einzuhalten.
      </p>
    </div>
  </section>

  <!-- Core Values -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      🌟 Kernwerte
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3 flex items-center">
          <span class="text-consent mr-2">🤝</span>
          Einverständnis
        </h3>
        <p class="text-smoke-300 text-sm">
          Alle Aktivitäten basieren auf ausdrücklichem, informiertem und
          jederzeit widerrufbarem Einverständnis aller Beteiligten.
        </p>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3 flex items-center">
          <span class="text-accent-400 mr-2">🛡️</span>
          Sicherheit
        </h3>
        <p class="text-smoke-300 text-sm">
          Physische und emotionale Sicherheit aller Teilnehmer hat absolute
          Priorität in unserer Community.
        </p>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3 flex items-center">
          <span class="text-secondary-400 mr-2">💝</span>
          Respekt
        </h3>
        <p class="text-smoke-300 text-sm">
          Wir behandeln alle Menschen mit Würde und Respekt, unabhängig von
          Hintergrund, Erfahrung oder Identität.
        </p>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3 flex items-center">
          <span class="text-caution mr-2">🔒</span>
          Vertraulichkeit
        </h3>
        <p class="text-smoke-300 text-sm">
          Was in unserer Community besprochen wird, bleibt in unserer Community.
          Privatsphäre ist ein Grundrecht.
        </p>
      </div>
    </div>
  </section>

  <!-- Consent Framework -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      ✅ Einverständnis-Rahmen
    </h2>

    <div class="space-y-6">
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Informiertes Einverständnis
        </h3>
        <ul class="space-y-2 text-smoke-300">
          <li class="flex items-start">
            <span class="text-consent mr-2">•</span>
            Alle Beteiligten müssen vollständig über geplante Aktivitäten informiert
            werden
          </li>
          <li class="flex items-start">
            <span class="text-consent mr-2">•</span>
            Risiken, Grenzen und Erwartungen müssen klar kommuniziert werden
          </li>
          <li class="flex items-start">
            <span class="text-consent mr-2">•</span>
            Fragen müssen ehrlich und vollständig beantwortet werden
          </li>
          <li class="flex items-start">
            <span class="text-consent mr-2">•</span>
            Genügend Zeit für Entscheidungen muss gewährt werden
          </li>
        </ul>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Kontinuierliches Einverständnis
        </h3>
        <ul class="space-y-2 text-smoke-300">
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            Einverständnis kann jederzeit widerrufen werden
          </li>
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            Regelmäßige Check-ins während Aktivitäten sind erforderlich
          </li>
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            Safewords müssen respektiert und sofort befolgt werden
          </li>
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            Nonverbale Signale müssen beachtet werden
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Expected Behavior -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      ✨ Erwartetes Verhalten
    </h2>

    <div class="space-y-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Respektvolle Kommunikation
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Verwendung korrekter Namen und Pronomen</li>
          <li>• Höfliche und konstruktive Sprache</li>
          <li>• Aktives Zuhören und Empathie</li>
          <li>• Respekt vor unterschiedlichen Meinungen</li>
        </ul>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Grenzen respektieren
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Persönliche Grenzen ohne Diskussion akzeptieren</li>
          <li>• Kein Druck oder Manipulation</li>
          <li>• Körperliche Grenzen strikt einhalten</li>
          <li>• Emotionale Sicherheit gewährleisten</li>
        </ul>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Inklusive Umgebung
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Neue Mitglieder willkommen heißen</li>
          <li>• Unterstützung bei Fragen bieten</li>
          <li>• Diskriminierung in jeder Form vermeiden</li>
          <li>• Diversität feiern und fördern</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Prohibited Behavior -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      🚫 Verbotenes Verhalten
    </h2>

    <div class="card bg-boundaries/10 border-boundaries/30">
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2 text-boundaries">
            Null-Toleranz-Vergehen
          </h3>
          <ul class="space-y-1 text-smoke-300 text-sm">
            <li>• Sexuelle Belästigung oder Übergriffe</li>
            <li>• Physische Gewalt oder Bedrohungen</li>
            <li>• Aktivitäten ohne Einverständnis</li>
            <li>• Diskriminierung aufgrund von Identität</li>
            <li>• Verletzung der Vertraulichkeit</li>
            <li>• Substanzmissbrauch während Events</li>
          </ul>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2 text-caution">
            Schwerwiegende Verstöße
          </h3>
          <ul class="space-y-1 text-smoke-300 text-sm">
            <li>• Wiederholtes Ignorieren von Grenzen</li>
            <li>• Manipulation oder emotionaler Missbrauch</li>
            <li>• Störung von Events oder Aktivitäten</li>
            <li>• Verbreitung falscher Informationen</li>
            <li>• Kommerzialisierung ohne Genehmigung</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Safety Protocols -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      🛡️ Sicherheitsprotokolle
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Safewords & Signale
        </h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="font-medium text-boundaries">"Stopp"/"Rot"</span>
            <span class="text-smoke-300">Sofortiger Abbruch</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-caution">"Gelb"/"Pause"</span>
            <span class="text-smoke-300">Vorsicht/Unterbrechung</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-consent">"Grün"</span>
            <span class="text-smoke-300">Alles in Ordnung</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Moderatoren-System
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Klare Ansprechpartner für Probleme</li>
          <li>• Nachbetreuung bei Vorfällen</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Reporting and Consequences -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      📢 Meldung und Konsequenzen
    </h2>

    <div class="space-y-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Wie melde ich Verstöße?
        </h3>
        <div class="space-y-3 text-smoke-300 text-sm">
          <p>
            • <strong>Während Events:</strong> Wende dich sofort an einen Moderator
          </p>
          <p>
            • <strong>Nach Events:</strong> E-Mail an conduct@hypnose-stammtisch.de
          </p>
          <p>• <strong>Anonym:</strong> Nutze unser Online-Meldeformular</p>
          <p>
            • <strong>Notfall:</strong> Kontaktiere sofort die Notdienste (112)
          </p>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Mögliche Konsequenzen
        </h3>
        <div class="space-y-2 text-smoke-300 text-sm">
          <p>
            <strong class="text-caution">Verwarnung:</strong> Erstes Gespräch und
            Aufklärung
          </p>
          <p>
            <strong class="text-caution">Bewährung:</strong> Zeitlich begrenzte Beobachtung
          </p>
          <p>
            <strong class="text-boundaries">Suspendierung:</strong> Temporärer Ausschluss
          </p>
          <p>
            <strong class="text-boundaries">Ausschluss:</strong> Permanenter Bann
            von der Community
          </p>
          <p>
            <strong class="text-boundaries">Rechtliche Schritte:</strong> Bei strafrechtlich
            relevanten Vorfällen
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Information -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      📞 Kontakt & Unterstützung
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">Community-Team</h3>
        <div class="space-y-2 text-smoke-300 text-sm">
          <p><strong>Allgemeine Fragen:</strong> info@hypnose-stammtisch.de</p>
          <p><strong>Verhaltenskodex:</strong> conduct@hypnose-stammtisch.de</p>
          <p><strong>Support:</strong> support@hypnose-stammtisch.de</p>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">Externe Hilfe</h3>
        <div class="space-y-2 text-smoke-300 text-sm">
          <p><strong>Telefonseelsorge:</strong> 0800 111 0 111</p>
          <p><strong>Nummer gegen Kummer:</strong> 116 111</p>
          <p>
            <strong>Hilfetelefon Gewalt gegen Frauen:</strong> 08000 116 016
          </p>
          <p>
            <strong>Kassenärztlicher Notdienst:</strong> 116 117
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Agreement -->
  <section class="mb-12">
    <div class="card bg-accent-400/10 border-accent-400/30">
      <h2 class="text-xl font-semibold text-smoke-50 mb-4">
        Einverständniserklärung
      </h2>
      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 mb-4">
          Durch deine Teilnahme an Events oder Aktivitäten des Hypnose
          Stammtisch bestätigst du, dass du:
        </p>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Diesen Verhaltenskodex gelesen und verstanden hast</li>
          <li>• Dich verpflichtest, diese Richtlinien einzuhalten</li>
          <li>• Verstehst, dass Verstöße Konsequenzen haben können</li>
          <li>• Bei Fragen oder Problemen das Community-Team kontaktierst</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center">
    <div class="space-y-6">
      <p class="text-smoke-400 text-sm">
        Dieser Verhaltenskodex ist ein lebendiges Dokument und wird regelmäßig
        überprüft und bei Bedarf aktualisiert.
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/ressourcen" use:link class="btn btn-outline">
          Weitere Ressourcen
        </a>
        <a href="/contact" use:link class="btn btn-primary">
          Fragen zum Kodex
        </a>
      </div>

      <div class="text-xs text-smoke-500">
        Letzte Überprüfung: Januar 2025 • Nächste Überprüfung: Juli 2025
      </div>
    </div>
  </footer>
</div>


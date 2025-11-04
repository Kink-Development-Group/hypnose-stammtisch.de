<script lang="ts">
  import { link } from "svelte-spa-router";
</script>

<svelte:head>
  <title>Datenschutzerklärung - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Informationen über die Erhebung und Verwendung personenbezogener Daten auf unserer Website und bei unseren Events."
  />
</svelte:head>

<div class="container mx-auto px-4 py-8 max-w-4xl">
  <!-- Header -->
  <header class="text-center mb-12">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Datenschutzerklärung
    </h1>
    <p class="text-xl text-smoke-300">
      Transparenz über die Erhebung und Verwendung deiner Daten
    </p>
    <div class="mt-6 text-sm text-smoke-400">
      Gültig seit: 15. Januar 2025 • Letzte Aktualisierung: 15. Januar 2025
    </div>
  </header>

  <!-- Overview -->
  <section class="mb-12">
    <div class="card bg-accent-400/10 border-accent-400/30">
      <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-4">
        Das Wichtigste in Kürze
      </h2>
      <div class="prose prose-invert max-w-none">
        <p class="text-smoke-300 mb-4">
          Wir nehmen den Schutz deiner persönlichen Daten sehr ernst. Diese
          Datenschutzerklärung informiert dich über Art, Umfang und Zweck der
          Verarbeitung personenbezogener Daten auf unserer Website und bei
          unseren Events.
        </p>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Wir sammeln nur die notwendigen Daten für unsere Services</li>
          <li>
            • Deine Daten werden nicht an Dritte verkauft oder weitergegeben
          </li>
          <li>• Du hast jederzeit Kontrolle über deine Daten</li>
          <li>
            • Wir halten uns an die EU-DSGVO und deutsche Datenschutzgesetze
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Responsible Party -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      1. Verantwortliche Stelle
    </h2>

    <div class="card">
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">
            Verantwortlich für die Datenverarbeitung:
          </h3>
          <div class="text-smoke-300 space-y-1">
            <p>Hypnose Stammtisch Community</p>
            <p>Max Mustermann (Community-Manager)</p>
            <p>Musterstraße 123</p>
            <p>12345 Musterstadt</p>
            <p>Deutschland</p>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">Kontakt:</h3>
          <div class="text-smoke-300 space-y-1">
            <p>
              E-Mail: <a
                href="mailto:privacy@hypnose-stammtisch.de"
                class="text-accent-400 hover:text-accent-300"
                >privacy@hypnose-stammtisch.de</a
              >
            </p>
            <p>Telefon: +49 (0) 123 456789</p>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-smoke-50 mb-2">
            Datenschutzbeauftragte*r:
          </h3>
          <p class="text-smoke-300">
            E-Mail: <a
              href="mailto:datenschutz@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
              >datenschutz@hypnose-stammtisch.de</a
            >
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Data Collection -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      2. Erhebung und Speicherung personenbezogener Daten
    </h2>

    <div class="space-y-6">
      <!-- Website Usage -->
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          2.1 Beim Besuch unserer Website
        </h3>
        <div class="space-y-3 text-smoke-300 text-sm">
          <p><strong>Automatisch erhobene Daten:</strong></p>
          <ul class="space-y-1 ml-4">
            <li>• IP-Adresse (anonymisiert nach 24 Stunden)</li>
            <li>• Datum und Uhrzeit des Zugriffs</li>
            <li>• Aufgerufene Seiten und Dateien</li>
            <li>• Browser-Type und Version</li>
            <li>• Betriebssystem</li>
            <li>• Verweis-URL (Referrer)</li>
          </ul>
          <p class="mt-3">
            <strong>Rechtsgrundlage:</strong> Berechtigtes Interesse (Art. 6 Abs.
            1 lit. f DSGVO) zur technischen Bereitstellung und Sicherheit der Website.
          </p>
        </div>
      </div>

      <!-- Contact Forms -->
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          2.2 Kontaktformulare
        </h3>
        <div class="space-y-3 text-smoke-300 text-sm">
          <p><strong>Erhobene Daten:</strong></p>
          <ul class="space-y-1 ml-4">
            <li>• Name</li>
            <li>• E-Mail-Adresse</li>
            <li>• Betreff der Anfrage</li>
            <li>• Nachrichteninhalt</li>
            <li>• Zeitpunkt der Übermittlung</li>
          </ul>
          <p class="mt-3">
            <strong>Zweck:</strong> Bearbeitung deiner Anfrage und Kommunikation<br
            />
            <strong>Rechtsgrundlage:</strong> Einwilligung (Art. 6 Abs. 1 lit. a
            DSGVO)<br />
            <strong>Speicherdauer:</strong> 24 Monate nach Abschluss der Kommunikation
          </p>
        </div>
      </div>

      <!-- Event Registration -->
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          2.3 Event-Anmeldungen
        </h3>
        <div class="space-y-3 text-smoke-300 text-sm">
          <p><strong>Erhobene Daten:</strong></p>
          <ul class="space-y-1 ml-4">
            <li>• Name und Vorname</li>
            <li>• E-Mail-Adresse</li>
            <li>• Erfahrungslevel mit Hypnose</li>
            <li>• Besondere Bedürfnisse oder Einschränkungen</li>
            <li>• Notfallkontakt (bei Präsenz-Events)</li>
          </ul>
          <p class="mt-3">
            <strong>Zweck:</strong> Event-Organisation, Teilnehmermanagement,
            Sicherheit<br />
            <strong>Rechtsgrundlage:</strong> Vertragsdurchführung (Art. 6 Abs.
            1 lit. b DSGVO)<br />
            <strong>Speicherdauer:</strong> 12 Monate nach Event-Ende
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Cookies and Tracking -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      3. Cookies und Tracking
    </h2>

    <div class="space-y-6">
      <!-- Essential Cookies -->
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          3.1 Technisch notwendige Cookies
        </h3>
        <div class="space-y-3 text-smoke-300 text-sm">
          <p>Wir verwenden nur technisch notwendige Cookies für:</p>
          <ul class="space-y-1 ml-4">
            <li>• Session-Management</li>
            <li>• Sicherheitsfeatures</li>
            <li>• Cookie-Einstellungen speichern</li>
          </ul>
          <p class="mt-3">
            <strong>Rechtsgrundlage:</strong> Berechtigtes Interesse (Art. 6 Abs.
            1 lit. f DSGVO)
          </p>
        </div>
      </div>

      <!-- No Analytics -->
      <div class="card bg-consent/10 border-consent/30">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          3.2 Keine Tracking-Cookies
        </h3>
        <p class="text-smoke-300 text-sm">
          Wir verwenden bewusst <strong>keine</strong> Tracking-Cookies, Analytics-Tools
          oder Social Media Plugins, die dein Verhalten verfolgen oder Profile erstellen.
        </p>
      </div>
    </div>
  </section>

  <!-- Data Sharing -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      4. Weitergabe von Daten
    </h2>

    <div class="space-y-6">
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Grundsatz: Keine Weitergabe
        </h3>
        <p class="text-smoke-300 text-sm mb-4">
          Deine personenbezogenen Daten werden grundsätzlich nicht an Dritte
          weitergegeben.
        </p>

        <h4 class="text-lg font-semibold text-smoke-50 mb-3">Ausnahmen:</h4>
        <ul class="space-y-2 text-smoke-300 text-sm">
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            <div>
              <strong>Technische Dienstleister:</strong> Hosting-Provider (Hetzner
              Online GmbH) in Deutschland nach Auftragsverarbeitungsvertrag
            </div>
          </li>
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            <div>
              <strong>Rechtliche Verpflichtung:</strong> Bei behördlichen Anfragen
              nur im gesetzlich erforderlichen Umfang
            </div>
          </li>
          <li class="flex items-start">
            <span class="text-caution mr-2">•</span>
            <div>
              <strong>Notfälle:</strong> Weitergabe von Notfallkontaktdaten nur bei
              tatsächlichen medizinischen Notfällen
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Data Security -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      5. Datensicherheit
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Technische Maßnahmen
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• SSL/TLS-Verschlüsselung (HTTPS)</li>
          <li>• Sichere Passwort-Hashing</li>
          <li>• Regelmäßige Sicherheitsupdates</li>
          <li>• Firewall und Intrusion Detection</li>
          <li>• Verschlüsselte Datenbank-Backups</li>
        </ul>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">
          Organisatorische Maßnahmen
        </h3>
        <ul class="space-y-1 text-smoke-300 text-sm">
          <li>• Zugriffskontrolle auf Need-to-know-Basis</li>
          <li>• Regelmäßige Mitarbeiterschulungen</li>
          <li>• Datenschutz-Folgenabschätzung</li>
          <li>• Incident Response Plan</li>
          <li>• Regelmäßige Sicherheitsaudits</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Your Rights -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      6. Deine Rechte
    </h2>

    <div class="space-y-6">
      <div class="card">
        <h3 class="text-xl font-semibold text-smoke-50 mb-4">
          Du hast folgende Rechte
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-smoke-50 mb-2">Informationsrechte</h4>
            <ul class="space-y-1 text-smoke-300 text-sm">
              <li>• Auskunft über verarbeitete Daten (Art. 15 DSGVO)</li>
              <li>• Datenportabilität (Art. 20 DSGVO)</li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold text-smoke-50 mb-2">Kontrollrechte</h4>
            <ul class="space-y-1 text-smoke-300 text-sm">
              <li>• Berichtigung von Daten (Art. 16 DSGVO)</li>
              <li>• Löschung von Daten (Art. 17 DSGVO)</li>
              <li>• Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold text-smoke-50 mb-2">Widerspruchsrechte</h4>
            <ul class="space-y-1 text-smoke-300 text-sm">
              <li>• Widerspruch gegen Verarbeitung (Art. 21 DSGVO)</li>
              <li>• Widerruf von Einwilligungen (Art. 7 DSGVO)</li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold text-smoke-50 mb-2">Beschwerderecht</h4>
            <ul class="space-y-1 text-smoke-300 text-sm">
              <li>• Beschwerde bei Aufsichtsbehörde (Art. 77 DSGVO)</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Exercise Rights -->
      <div class="card bg-accent-400/10 border-accent-400/30">
        <h3 class="text-lg font-semibold text-smoke-50 mb-3">Rechte ausüben</h3>
        <p class="text-smoke-300 text-sm mb-4">
          Um deine Rechte auszuüben, kontaktiere uns unter:
        </p>
        <div class="space-y-2 text-sm">
          <p>
            <strong>E-Mail:</strong>
            <a
              href="mailto:privacy@hypnose-stammtisch.de"
              class="text-accent-400 hover:text-accent-300"
            >
              privacy@hypnose-stammtisch.de
            </a>
          </p>
          <p>
            <strong>Betreff:</strong> "Datenschutzanfrage - [Dein Anliegen]"
          </p>
        </div>
        <p class="text-smoke-400 text-xs mt-4">
          Wir bearbeiten deine Anfrage innerhalb von 30 Tagen nach Erhalt.
        </p>
      </div>
    </div>
  </section>

  <!-- Data Retention -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      7. Speicherdauer
    </h2>

    <div class="card">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="font-semibold text-smoke-50 mb-2">Website-Logs</h3>
            <p class="text-smoke-300 text-sm">
              7 Tage, IP-Adressen anonymisiert nach 24 Stunden
            </p>
          </div>

          <div>
            <h3 class="font-semibold text-smoke-50 mb-2">Kontaktanfragen</h3>
            <p class="text-smoke-300 text-sm">
              24 Monate nach Abschluss der Kommunikation
            </p>
          </div>

          <div>
            <h3 class="font-semibold text-smoke-50 mb-2">Event-Anmeldungen</h3>
            <p class="text-smoke-300 text-sm">12 Monate nach Event-Ende</p>
          </div>

          <div>
            <h3 class="font-semibold text-smoke-50 mb-2">Newsletter</h3>
            <p class="text-smoke-300 text-sm">
              Bis zur Abmeldung, dann sofortige Löschung
            </p>
          </div>
        </div>

        <div class="mt-6 p-4 bg-smoke-800 rounded-lg">
          <p class="text-smoke-300 text-sm">
            <strong>Automatische Löschung:</strong> Alle Daten werden automatisch
            nach Ablauf der Speicherfrist gelöscht, es sei denn, gesetzliche Aufbewahrungspflichten
            erfordern eine längere Speicherung.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Supervisory Authority -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      8. Aufsichtsbehörde
    </h2>

    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Bei Beschwerden bezüglich der Verarbeitung deiner personenbezogenen
        Daten kannst du dich an die zuständige Aufsichtsbehörde wenden:
      </p>

      <div class="space-y-2 text-smoke-300 text-sm">
        <p>
          <strong
            >Landesbeauftragte für den Datenschutz und die Informationsfreiheit</strong
          >
        </p>
        <p>Königstraße 10a</p>
        <p>70173 Stuttgart</p>
        <p>Deutschland</p>
        <p>E-Mail: poststelle@lfdi.bwl.de</p>
        <p>Telefon: +49 (0) 711 61 55 41-0</p>
      </div>
    </div>
  </section>

  <!-- Changes -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-semibold text-smoke-50 mb-6">
      9. Änderungen dieser Datenschutzerklärung
    </h2>

    <div class="card">
      <p class="text-smoke-300 text-sm mb-4">
        Wir behalten uns vor, diese Datenschutzerklärung bei Bedarf zu
        aktualisieren, um sie an geänderte Rechtslagen oder
        Verarbeitungspraktiken anzupassen.
      </p>

      <div class="space-y-2 text-smoke-300 text-sm">
        <p>
          • <strong>Benachrichtigung:</strong> Bei wesentlichen Änderungen informieren
          wir dich per E-Mail
        </p>
        <p>
          • <strong>Verlauf:</strong> Frühere Versionen sind auf Anfrage verfügbar
        </p>
        <p>
          • <strong>Inkrafttreten:</strong> Änderungen treten 30 Tage nach Veröffentlichung
          in Kraft
        </p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/contact" use:link class="btn btn-outline">
          Datenschutz-Anfrage
        </a>
        <a href="/code-of-conduct" use:link class="btn btn-outline">
          Verhaltenskodex
        </a>
        <a href="/imprint" use:link class="btn btn-outline"> Impressum </a>
      </div>

      <div class="text-xs text-smoke-500 space-y-1">
        <p>Stand: 15. Januar 2025</p>
        <p>Diese Datenschutzerklärung wurde mit größter Sorgfalt erstellt.</p>
      </div>
    </div>
  </footer>
</div>


# Passkeys (WebAuthn/FIDO2)

Passkeys sind ein **zusätzlicher, alternativer** Login-Pfad für den Admin-Bereich.
Passwort + TOTP bleibt unverändert bestehen – als Fallback für die erste
Passkey-Registrierung, bei Geräteverlust und für Browser ohne WebAuthn-Support.

## Warum Passkeys

- **Phishing-resistent**: Ein Passkey ist an das Origin gebunden. Eine
  nachgebaute Login-Seite bekommt keine verwertbare Signatur – anders als bei
  Passwort und TOTP-Code, die sich per Echtzeit-Relay abgreifen lassen.
- **Kein Geheimnis auf dem Server**: Gespeichert wird nur der öffentliche
  Schlüssel. Ein Datenbank-Leak gibt keinen Zugang.
- **Weniger Friktion**: Kein App-Wechsel für den TOTP-Code.

## Ablauf

### Registrierung (im Profil)

1. Admin meldet sich regulär mit Passwort + TOTP an.
2. Unter **Mein Profil → Passkeys** einen Namen vergeben und
   „Passkey hinzufügen" wählen.
3. Der Browser erzeugt das Schlüsselpaar; der Server prüft die Attestation und
   speichert den öffentlichen Schlüssel.

Die Registrierung ist nur mit vollständig authentifizierter Session möglich –
ein Passkey kann also nie von jemandem angelegt werden, der nicht bereits
Passwort und zweiten Faktor besitzt.

### Anmeldung

1. Auf der Login-Seite „Mit Passkey anmelden" wählen.
2. Der Authenticator bietet den passenden Passkey an (discoverable credential,
   „usernameless") und verlangt PIN oder Biometrie.
3. Nach erfolgreicher Prüfung ist die Session vollständig authentifiziert – es
   folgt **kein** TOTP-Schritt mehr.

Ein Passkey deckt beide Faktoren zugleich ab: Besitz des Authenticators plus
dessen User Verification. `userVerification` ist deshalb auf `required` gesetzt.

## API-Endpunkte

Alle Endpunkte liegen unter `/api/admin/auth/webauthn`.

| Methode | Endpunkt            | Auth | CSRF | Beschreibung                             |
| ------- | ------------------- | ---- | ---- | ---------------------------------------- |
| POST    | `/register/options` | ja   | ja   | Challenge für `credentials.create()`     |
| POST    | `/register/verify`  | ja   | ja   | Attestation prüfen und Passkey speichern |
| POST    | `/login/options`    | nein | nein | Challenge für `credentials.get()`        |
| POST    | `/login/verify`     | nein | nein | Assertion prüfen und Session öffnen      |
| GET     | `/credentials`      | ja   | –    | Eigene Passkeys auflisten                |
| DELETE  | `/credentials/{id}` | ja   | ja   | Eigenen Passkey löschen                  |

Die Login-Endpunkte sind bewusst anonym: Die Challenge liegt in der Session und
verbindet beide Requests. Sie kennen weder E-Mail-Adresse noch Konto, geben also
auch keine Auskunft darüber, welche Konten oder Passkeys existieren.

## Sicherheitsmaßnahmen

- **Rate Limiting**: 20 Options-Anfragen bzw. 10 Login-Versuche pro IP und
  5 Minuten (`RateLimiter`).
- **IP-Bans und Account-Lockout**: Vor jedem Login-Schritt wird
  `IpBanManager` geprüft; fehlgeschlagene Assertions laufen über
  `FailedLoginTracker` in dieselbe Lockout-Logik wie Passwort-Logins.
- **Challenges sind einmalig** und laufen nach 5 Minuten ab
  (`WebAuthnService::CHALLENGE_TTL`).
- **Signaturzähler**: Ein nicht steigender `sign_count` deutet auf ein
  geklontes Credential hin und führt zur Ablehnung.
- **Origin- und RP-ID-Bindung**: Nur die konfigurierten Origins werden
  akzeptiert (siehe unten).
- **Session-Regeneration** direkt vor dem Öffnen der Admin-Session.
- **Audit-Log**: `webauthn.register_success`, `webauthn.register_failed`,
  `webauthn.login_success`, `webauthn.login_failed`,
  `webauthn.credential_deleted`.
- Pro Konto sind maximal 10 Passkeys erlaubt
  (`WebAuthnCredentialRepository::MAX_CREDENTIALS_PER_USER`).

## Konfiguration

Auf der Produktionsdomain sind alle Werte optional; ohne Angabe werden sie aus
`FRONTEND_URL` bzw. `APP_URL` abgeleitet. **Auf jedem anderen Host sind sie
Pflicht** – siehe den folgenden Kasten.

```env
# Relying Party ID – die Domain, an die der Passkey gebunden wird.
# Default: Host von FRONTEND_URL ohne führendes "www."
WEBAUTHN_RP_ID=hypnose-stammtisch.de

# Anzeigename im Authenticator-Dialog. Default: APP_NAME
WEBAUTHN_RP_NAME=Hypnose Stammtisch

# Erlaubte Origins (kommagetrennt). Default: FRONTEND_URL und APP_URL,
# jeweils zusätzlich in der www-/apex-Variante.
WEBAUTHN_ORIGINS=https://hypnose-stammtisch.de,https://www.hypnose-stammtisch.de
```

::: warning Wechsel der RP-ID
Die Relying Party ID ist Teil des Credentials. Ändert sie sich, sind **alle**
bestehenden Passkeys ungültig und müssen neu registriert werden. Passwort + TOTP
bleibt in diesem Fall der Weg zurück ins Konto.
:::

::: danger Subdomains (Beta, Staging) brauchen eigene Werte
Erlaubt sind ausschließlich die abgeleiteten Origins **und deren www-/apex-
Gegenstück** — sonst nichts. Läuft eine Umgebung unter einer anderen Subdomain,
während `FRONTEND_URL`/`APP_URL` auf die Produktionsdomain zeigen (oder gar nicht
gesetzt sind — der Default ist ebenfalls die Produktionsdomain), scheitert **jede
Registrierung**:

```
Passkey konnte nicht verifiziert werden: Invalid origin. Subdomains are not allowed.
```

Der Fehler ist besonders tückisch, weil der Browser die Zeremonie vorher
anstandslos durchlaufen lässt: `rp.id = hypnose-stammtisch.de` ist ein zulässiges
Domain-Suffix von `beta.hypnose-stammtisch.de`, der Authenticator fragt also nach
Biometrie — erst der Server lehnt ab.

Für eine Beta-Umgebung gehört deshalb in die `.env`:

```env
WEBAUTHN_RP_ID=beta.hypnose-stammtisch.de
WEBAUTHN_ORIGINS=https://beta.hypnose-stammtisch.de
```

Damit sind Beta-Passkeys sauber von der Produktion getrennt. Wer stattdessen nur
`WEBAUTHN_ORIGINS` um die Beta-Adresse ergänzt, behält die Produktions-RP-ID —
die Credentials liegen dann zwar in der Beta-Datenbank, sind aber an die
Produktionsdomain gebunden.

Auf Shared Hosting mit Deploy über das `ENV`-Secret gilt: Der Wert gehört in das
Secret, nicht auf den Server — der Deploy überschreibt die `.env` bei jedem Lauf.
:::

In der lokalen Entwicklung funktioniert `http://localhost:5173` ohne weitere
Konfiguration – WebAuthn erlaubt `localhost` als sicheren Kontext.

## Datenbank

Migration `014_add_webauthn_credentials.sql` legt `user_webauthn_credentials` an:

| Spalte                               | Zweck                                           |
| ------------------------------------ | ----------------------------------------------- |
| `credential_id`                      | Base64url-Credential-ID, eindeutig              |
| `public_key`                         | Base64url-kodierter öffentlicher COSE-Schlüssel |
| `sign_count`                         | Signaturzähler des Authenticators               |
| `transports`, `aaguid`               | Metadaten des Authenticators                    |
| `nickname`                           | Vom Nutzer vergebener Anzeigename               |
| `user_handle`                        | User-Handle im Authenticator (die Konto-ID)     |
| `is_backup_eligible`, `is_backed_up` | Backup-Status (synchronisierte Passkeys)        |
| `created_at`, `last_used_at`         | Zeitstempel                                     |

Rollback: `DROP TABLE IF EXISTS user_webauthn_credentials;` und den Eintrag
`014` aus der Tabelle `migrations` entfernen. Es werden keine bestehenden
Spalten verändert.

## Implementierung

| Datei                                                | Rolle                                    |
| ---------------------------------------------------- | ---------------------------------------- |
| `backend/src/Utils/WebAuthnService.php`              | RP-Konfiguration, Optionen, Verifikation |
| `backend/src/Utils/WebAuthnCredentialRepository.php` | Persistenz der Credentials               |
| `backend/src/Controllers/WebAuthnController.php`     | HTTP-Endpunkte                           |
| `src/utils/webauthn.ts`                              | Frontend-Client                          |
| `src/components/admin/PasskeyManager.svelte`         | Verwaltung im Profil                     |
| `src/pages/admin/AdminLogin.svelte`                  | „Mit Passkey anmelden"                   |

Backend: [`web-auth/webauthn-lib`](https://github.com/web-auth/webauthn-framework),
Frontend: [`@simplewebauthn/browser`](https://simplewebauthn.dev/).

## Tests

```bash
bun run backend:test    # PHPUnit – beide Ceremonies gegen einen Software-Authenticator
bun run test:unit:run   # Vitest – Frontend-Client
```

`backend/tests/Support/FakeAuthenticator.php` erzeugt echte ES256-Signaturen,
sodass Registrierung, Assertion, Origin-/RP-ID-Prüfung, Signaturzähler und
User-Handle-Abgleich mit echter Kryptografie getestet werden.

## Referenzen

- [W3C WebAuthn Level 3](https://www.w3.org/TR/webauthn-3/)
- [FIDO Alliance Passkeys](https://fidoalliance.org/passkeys/)

-- Migration 014: Passkeys (WebAuthn/FIDO2) für den Admin-Login
--
-- Legt die Tabelle `user_webauthn_credentials` an, in der die öffentlichen
-- Schlüssel registrierter Passkeys gespeichert werden. Passkeys sind ein
-- *zusätzlicher*, alternativer Login-Pfad – Passwort + TOTP bleibt unverändert
-- als Fallback bestehen (siehe GitHub Issue #152).
--
-- Rollback: `DROP TABLE IF EXISTS user_webauthn_credentials;` – die Tabelle ist
-- eigenständig, es werden keine bestehenden Spalten verändert. Danach den
-- Eintrag `014` aus der Tabelle `migrations` entfernen.
--
-- Forward-only; erzeugt kein Result-Set, damit der Runner-Pfad über
-- PDO::exec() sauber bleibt (siehe migrations/README + 008).

CREATE TABLE IF NOT EXISTS user_webauthn_credentials (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id INT NOT NULL,

  -- Base64url-kodierte Credential-ID des Authenticators. ASCII + binäre
  -- Kollation, damit der Vergleich case-sensitiv exakt erfolgt (Base64url
  -- unterscheidet Groß-/Kleinschreibung). 512 ASCII-Zeichen bleiben unter dem
  -- InnoDB-Indexlimit und decken auch lange Credential-IDs ab.
  credential_id VARCHAR(512) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

  -- Base64url-kodierter öffentlicher COSE-Schlüssel.
  public_key TEXT NOT NULL,

  -- Signaturzähler des Authenticators; ein nicht steigender Zähler deutet auf
  -- ein geklontes Credential hin und führt zur Ablehnung des Logins. Wirksam
  -- nur bei Authenticatoren, die überhaupt einen Zähler führen: synchronisierte
  -- Passkeys melden durchgängig 0, der Wert bleibt hier 0 und die Prüfung
  -- greift nicht (spec-konform, siehe docs/security/passkeys.md).
  sign_count BIGINT UNSIGNED NOT NULL DEFAULT 0,

  -- JSON-Array der vom Authenticator gemeldeten Transports (z. B. ["internal"]).
  transports VARCHAR(255) NULL,

  -- AAGUID des Authenticator-Modells (UUID-Schreibweise).
  aaguid CHAR(36) NULL,

  -- Vom Nutzer vergebener Anzeigename ("MacBook Touch ID").
  nickname VARCHAR(100) NOT NULL,

  -- User-Handle, das der Authenticator für dieses Credential speichert.
  user_handle VARCHAR(255) NOT NULL,

  -- Der Attestation-Typ ('none') und der Backup-Status werden für spätere
  -- Auswertungen mitgeführt.
  attestation_type VARCHAR(32) NOT NULL DEFAULT 'none',
  is_backup_eligible BOOLEAN NULL,
  is_backed_up BOOLEAN NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP NULL,

  UNIQUE KEY uniq_credential_id (credential_id),
  INDEX idx_user_id (user_id),
  INDEX idx_last_used_at (last_used_at),

  CONSTRAINT fk_user_webauthn_credentials_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

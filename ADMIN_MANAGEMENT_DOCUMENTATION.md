# Admin-Management-System

## Übersicht

Das Admin-Management-System ermöglicht es "Head"-Admins, andere Admin-Benutzer zu verwalten. Das System implementiert eine rollenbasierte Zugriffskontrolle mit drei Rollen:

- **head**: Kann alle anderen Admins verwalten (erstellen, bearbeiten, löschen)
- **admin**: Standard-Administrator mit Zugriff auf Events und Nachrichten
- **moderator**: Basis-Moderator mit Zugriff auf Nachrichten

## Implementierte Features

### 1. Datenbankstruktur

- `users` Tabelle mit erweiterten Rollen (admin, moderator, head)
- Sichere Passwort-Speicherung mit password_hash()
- Benutzer-Status (aktiv/inaktiv)
- Zeitstempel für Erstellung und Aktualisierung

### 2. Backend-API

- **AdminUsersController**: Vollständiges CRUD für Admin-Benutzer
- Endpunkte:
  - `GET /api/admin/users` - Alle Benutzer auflisten
  - `POST /api/admin/users` - Neuen Benutzer erstellen
  - `GET /api/admin/users/{id}` - Spezifischen Benutzer anzeigen
  - `PUT /api/admin/users/{id}` - Benutzer aktualisieren
  - `DELETE /api/admin/users/{id}` - Benutzer löschen
  - `GET /api/admin/users/permissions` - Berechtigungen prüfen

### 3. Frontend-Komponente

- **AdminUsers.svelte**: Vollständige Benutzeroberfläche
- Features:
  - Übersichtliche Tabelle aller Admin-Benutzer
  - Erstellen neuer Benutzer mit Formular
  - Bearbeiten bestehender Benutzer (inline)
  - Löschen mit Bestätigungsdialog
  - Rollenbasierte Berechtigung (nur für Head-Admins sichtbar)

### 4. Sicherheitsfeatures

- **Rollen-Validierung**: Nur Head-Admins können auf die Verwaltung zugreifen
- **Selbstschutz**: Head-Admins können sich nicht selbst löschen oder degradieren
- **Passwort-Sicherheit**: Mindestlänge, sichere Hashing-Algorithmen
- **Eingabe-Validierung**: Vollständige Validierung aller Benutzereingaben

## Installation und Setup

### 1. Datenbank-Migration

Die notwendigen Datenbankänderungen wurden bereits angewendet:

```sql
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'moderator', 'head') DEFAULT 'admin';
```

### 2. Standard Head-Admin

Ein Standard Head-Admin wurde erstellt:

- **E-Mail**: <head@hypnose-stammtisch.de>
- **Passwort**: admin123
- **Rolle**: head

**⚠️ WICHTIG**: Ändern Sie dieses Passwort sofort nach dem ersten Login!

## Nutzung

### 1. Login als Head-Admin

1. Besuchen Sie <http://localhost:5174/admin/login>
2. Melden Sie sich mit den obigen Anmeldedaten an
3. Navigieren Sie zu "Admin-Benutzer" in der Seitenleiste

### 2. Benutzer verwalten

- **Erstellen**: Klicken Sie auf "Neuen Admin-Benutzer erstellen"
- **Bearbeiten**: Klicken Sie auf "Bearbeiten" neben einem Benutzer
- **Löschen**: Klicken Sie auf "Löschen" und bestätigen Sie die Aktion
- **Status ändern**: Bearbeiten Sie einen Benutzer und ändern Sie den "Aktiv"-Status

### 3. Rollen-Hierarchie

- **Head**: Vollzugriff auf alle Funktionen + Benutzerverwaltung
- **Admin**: Zugriff auf Events und Nachrichten
- **Moderator**: Nur Zugriff auf Nachrichten

## API-Dokumentation

### Authentifizierung

Alle Endpunkte erfordern eine gültige Admin-Session mit Head-Admin-Rolle.

### Endpunkte

#### `GET /api/admin/users`

Listet alle Admin-Benutzer auf.

**Response:**

```json
[
  {
    "id": 1,
    "username": "headadmin",
    "email": "head@hypnose-stammtisch.de",
    "role": "head",
    "is_active": true,
    "last_login": "2025-08-07T20:30:00Z",
    "created_at": "2025-08-07T19:00:00Z",
    "updated_at": "2025-08-07T20:30:00Z"
  }
]
```

#### `POST /api/admin/users`

Erstellt einen neuen Admin-Benutzer.

**Request Body:**

```json
{
  "username": "newadmin",
  "email": "admin@example.com",
  "password": "securepassword123",
  "role": "admin",
  "is_active": true
}
```

#### `PUT /api/admin/users/{id}`

Aktualisiert einen bestehenden Benutzer.

**Request Body:**

```json
{
  "username": "updatedname",
  "email": "newemail@example.com",
  "role": "moderator",
  "is_active": false
}
```

#### `DELETE /api/admin/users/{id}`

Löscht einen Benutzer (außer sich selbst).

#### `GET /api/admin/users/permissions`

Gibt die Berechtigungen des aktuellen Benutzers zurück.

**Response:**

```json
{
  "can_manage_users": true,
  "can_manage_events": true,
  "can_view_messages": true,
  "role": "head"
}
```

## Sicherheitshinweise

1. **Starke Passwörter**: Verwenden Sie immer starke, einzigartige Passwörter
2. **Regelmäßige Updates**: Überprüfen Sie regelmäßig die Benutzerkonten
3. **Minimale Berechtigungen**: Vergeben Sie nur die minimal notwendigen Rollen
4. **Audit-Trail**: Überwachen Sie die Admin-Aktivitäten
5. **Backup**: Erstellen Sie regelmäßige Datensicherungen der Benutzerdaten

## Troubleshooting

### Problem: Kann nicht auf Benutzerverwaltung zugreifen

- **Lösung**: Stellen Sie sicher, dass Sie als Head-Admin angemeldet sind

### Problem: "Head admin role required" Fehler

- **Lösung**: Nur Benutzer mit der Rolle "head" können auf diese Funktion zugreifen

### Problem: Kann Standard-Admin nicht erstellen

- **Lösung**: Überprüfen Sie die Datenbankverbindung und führen Sie das Setup-Skript erneut aus:

```bash
php backend/setup_users.php
```

## Dateien und Struktur

### Backend

- `backend/src/Controllers/AdminUsersController.php` - Haupt-Controller
- `backend/migrations/005_add_head_admin_role.sql` - Datenbank-Migration
- `backend/setup_users.php` - Setup-Skript für Benutzer-Tabelle

### Frontend

- `src/components/admin/AdminUsers.svelte` - Haupt-Komponente
- `src/pages/admin/AdminUsersPage.svelte` - Seiten-Wrapper
- `src/components/admin/AdminLayout.svelte` - Navigation erweitert

### API-Routes

- `backend/api/admin.php` - API-Router erweitert um Benutzer-Endpunkte

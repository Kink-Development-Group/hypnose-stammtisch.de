---
description: Verwaltung der "Bekannten Event-Reihen" auf der Startseite
outline: deep
---

# Bekannte Event-Reihen

## Übersicht

Die Sektion **„Bekannte Event-Reihen“** auf der Startseite stellt regelmäßige
Stammtische und Reihen vor — eigene wie fremde. Ihre Inhalte lagen früher als
festes Array in `src/components/sections/EventSeries.svelte`; jede Änderung
brauchte damit einen Commit und ein Deployment, und die Termine veralteten
unbemerkt. Seit Migration `015` sind die Karten Datensätze in der Tabelle
`known_event_series` und werden im Admin-Bereich gepflegt.

::: tip Nicht verwechseln
`known_event_series` ist **nicht** `event_series`. Letztere hält die
wiederkehrenden Serien der eigenen Veranstaltungen (RRULE, Termine, Overrides).
Eine Zeile in `known_event_series` ist eine redaktionelle Karte, die eine Reihe
beschreibt — und optional per `linked_series_id` auf eine echte Serie zeigt.
:::

## Berechtigungen

Die Verwaltung steht **ausschließlich Head-Admins und Admins** offen.

| Rolle           | Zugriff                                             |
| --------------- | --------------------------------------------------- |
| `head`          | vollständig                                         |
| `admin`         | vollständig                                         |
| `event_manager` | kein Zugriff (403), Navigationseintrag ausgeblendet |
| `moderator`     | kein Zugriff (403), Navigationseintrag ausgeblendet |

Ein Event-Manager pflegt seine Events, entscheidet aber nicht, welche Reihen die
Startseite bewirbt. Serverseitig prüft `AdminKnownEventSeriesController` gegen
`AdminAuth::EVENT_FULL_ACCESS_ROLES`; im Frontend blenden
`User.canManageKnownEventSeries()` und `AdminKnownEventSeriesGuarded.svelte` die
Oberfläche aus. Maßgeblich ist die Prüfung im Backend — der Guard erspart nur den
Fehlklick.

## Bedienung

Die Verwaltung liegt unter `/admin/known-event-series`.

- **Anlegen und Bearbeiten** über ein Modal-Formular: Titel, Ort, Rhythmus,
  Beschreibung, Formate, Preis, Tags und das Ziel des „Details“-Buttons.
- **Sortieren** über die Pfeil-Buttons in der ersten Spalte. Bewusst keine
  Drag-and-drop-Lösung: die Reihenfolge muss auch mit der Tastatur änderbar sein.
- **Veröffentlichen und Zurückziehen** direkt in der Tabelle, ohne das Formular
  zu öffnen.
- **Löschen** mit Rückfrage.

Der Status steuert die Sichtbarkeit: nur `published` **und** `is_active` erscheint
auf der Startseite. Gibt es keine solche Reihe, blendet die Startseite die gesamte
Sektion samt Überschrift aus, statt eine leere Fläche zu zeigen.

Die Städteliste im Untertitel der Sektion wird aus den Reihen abgeleitet: aus dem
Teil des Ortes vor dem Trenner „•“ („Hamburg • Club Catonium“ → „Hamburg“).

## Nächster Termin

Das Feld, das früher am schnellsten veraltete, kennt drei Modi:

| Modus    | Verhalten                                                                     |
| -------- | ----------------------------------------------------------------------------- |
| `none`   | Die Karte zeigt keinen Termin.                                                |
| `manual` | Ein eingetragener Text, z. B. für Reihen außerhalb des eigenen Kalenders.     |
| `auto`   | Der Termin wird aus der verknüpften Event-Serie berechnet und bleibt aktuell. |

Bei `auto` expandiert das Backend die RRULE der verknüpften Serie, berücksichtigt
EXDATEs und gespeicherte Instanz-Overrides — ein verschobener Termin liefert
seine neue Startzeit, ein abgesagter wird übersprungen — und liefert den ersten
Termin ab „jetzt“ als ISO-Zeitstempel. Formatiert wird erst im Frontend.

Wie im öffentlichen Kalender zählen dabei nur veröffentlichte Overrides
(`override_type` `changed`/`cancelled` mit Status `published`/`cancelled`). Ein
Override im Entwurf verschiebt den Termin auf der Startseite also noch nicht.

Ebenfalls wie im Kalender wird ein Termin über seinen ursprünglichen Tag
zugeordnet: Ein bereits vergangener Termin, der nach vorne in die Zukunft
verschoben wurde, taucht deshalb nicht als „nächster Termin“ auf.

Ein `manual` gepflegter Text veraltet weiterhin von selbst; wo es geht, ist die
Verknüpfung die bessere Wahl.

## Technische Umsetzung

### Frontend

```text
src/components/sections/EventSeries.svelte        # Öffentliche Sektion
src/components/admin/AdminKnownEventSeries.svelte # Verwaltung
src/pages/admin/AdminKnownEventSeriesPage.svelte
src/pages/admin/AdminKnownEventSeriesGuarded.svelte
src/enums/knownEventSeries.ts                     # KnownEventSeriesStatus, NextEventSource
src/types/knownEventSeries.ts
```

### Backend

```text
backend/src/Models/KnownEventSeries.php
backend/src/Controllers/KnownEventSeriesController.php       # öffentlich
backend/src/Controllers/AdminKnownEventSeriesController.php  # Verwaltung
backend/migrations/015_add_known_event_series.sql
```

### Endpunkte

| Methode  | Pfad                                            | Zugriff     |
| -------- | ----------------------------------------------- | ----------- |
| `GET`    | `/api/known-event-series`                       | öffentlich  |
| `GET`    | `/api/admin/known-event-series`                 | head, admin |
| `GET`    | `/api/admin/known-event-series/linkable-series` | head, admin |
| `GET`    | `/api/admin/known-event-series/{id}`            | head, admin |
| `POST`   | `/api/admin/known-event-series`                 | head, admin |
| `PUT`    | `/api/admin/known-event-series/{id}`            | head, admin |
| `DELETE` | `/api/admin/known-event-series/{id}`            | head, admin |
| `POST`   | `/api/admin/known-event-series/reorder`         | head, admin |

Der öffentliche Endpunkt liefert ausschließlich veröffentlichte, aktive Reihen mit
bereits aufgelöstem Termin. Alle schreibenden Admin-Endpunkte verlangen einen
gültigen CSRF-Token und validieren serverseitig; das Ziel des „Details“-Buttons
akzeptiert nur interne Pfade und `http(s)`-Adressen.

## Migration der Bestandsdaten

Migration `015` legt die drei zuvor hartkodierten Reihen als veröffentlichte
Datensätze an, damit die Startseite nach dem Deployment unverändert aussieht. Die
Termine starten dabei als `none`: die Daten im alten Array waren längst
vergangen. Jeder Seed ist über seinen Slug abgesichert und daher wiederholbar.

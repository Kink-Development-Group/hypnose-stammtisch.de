# Hypnose-Stammtisch.de - Community Kalender

Eine moderne, barrierefreie Webanwendung für die Hypnose-Community mit Kalender-Funktionalität, Event-Management und RRULE-Unterstützung.

## 📋 Projektübersicht

### Funktionen

- 🗓️ **Interaktiver Kalender** mit Monats- und Wochenansicht
- 🔄 **Wiederkehrende Events** mit vollständiger RRULE-Unterstützung
- 📱 **Responsive Design** für alle Geräte
- ♿ **WCAG 2.2 AA konform** - vollständig barrierefrei
- 📥 **ICS-Export** für Kalender-Apps
- 🌐 **RSS/iCal-Feeds** mit Token-Authentifizierung
- 📝 **Event-Einreichung** mit Moderations-Workflow
- 💬 **Kontaktformular** mit Spam-Schutz
- 🔒 **DSGVO-konform** mit Datenschutz-Features

### Technologie-Stack

#### Frontend

- **Svelte 5** - Moderne Reactive Framework
- **TypeScript** - Type-Safe Development
- **Vite** - Schneller Build-Prozess
- **Tailwind CSS** - Utility-First CSS Framework
- **Day.js** - Moderne Datums-Bibliothek mit Timezone-Support
- **RRULE.js** - Wiederkehrende Event-Verarbeitung
- **Zod** - Schema-Validierung

#### Backend

- **PHP 8.1+** - Server-side Logic
- **MySQL/MariaDB** - Datenbank
- **Composer** - Dependency Management
- **PHPMailer** - E-Mail-Versand
- **Carbon** - Erweiterte Datums-Verarbeitung

#### Entwicklung & Testing

- **Playwright** - E2E Testing
- **@axe-core/playwright** - Accessibility Testing
- **ESLint + Prettier** - Code-Qualität

## 🚀 Quick Start

### Voraussetzungen

- Node.js 18+ mit npm
- PHP 8.1+
- MySQL 8.0+ oder MariaDB 10.6+
- Composer

### Installation

1. **Repository klonen**

```bash
git clone https://github.com/ihr-username/hypnose-stammtisch.de.git
cd hypnose-stammtisch.de
```

2. **Frontend Setup**

```bash
npm install
```

3. **Backend Setup**

```bash
cd backend
composer install
```

4. **Datenbank einrichten**

```bash
# MySQL-Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE hypnose_stammtisch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Schema importieren
mysql -u root -p hypnose_stammtisch < migrations/001_initial_schema.sql
```

5. **Konfiguration**

```bash
# Backend-Konfiguration
cp backend/.env.example backend/.env
# .env-Datei mit Ihren Datenbankdaten bearbeiten
```

### Entwicklung starten

```bash
# Frontend Development Server (Port 5173)
npm run dev

# Backend Development Server (Port 8080)
cd backend
php -S localhost:8080 -t . start-dev.php
```

Die Anwendung ist dann verfügbar unter:

- Frontend: <http://localhost:5173>
- Backend API: <http://localhost:8080>

## 🏗️ Architektur

### Frontend-Struktur

```bash
src/
├── components/           # Wiederverwendbare Komponenten
│   ├── calendar/         # Kalender-spezifische Komponenten
│   ├── forms/           # Formular-Komponenten
│   ├── layout/          # Layout-Komponenten
│   ├── sections/        # Seiten-Abschnitte
│   └── ui/              # UI-Primitives
├── pages/               # SPA-Seiten
├── stores/              # Svelte Stores (State Management)
├── types/               # TypeScript-Definitionen
└── utils/               # Hilfsfunktionen
```

### Backend-Struktur

```bash
backend/
├── src/
│   ├── Controllers/     # API-Controller
│   ├── Models/          # Datenmodelle
│   ├── Utils/           # RRULE-Processor, ICS-Generator
│   ├── Database/        # Datenbankverbindung
│   └── Config/          # Konfiguration
├── api/                 # API-Endpunkt
├── migrations/          # Datenbank-Migrationen
└── config/              # Konfigurationsdateien
```

### API-Endpunkte

#### Events

- `GET /api/events` - Alle Events abrufen
- `GET /api/events/{id}` - Einzelnes Event
- `GET /api/events/{id}/ics` - ICS-Download für Event
- `POST /api/events/{id}/preview` - RRULE-Vorschau

#### Kalender

- `GET /api/calendar/feed?token=xxx` - ICS-Kalender-Feed
- `GET /api/calendar/expanded?start=...&end=...` - Expandierte Events

#### Formulare

- `POST /api/forms/event` - Event einreichen
- `POST /api/forms/contact` - Kontakt-Nachricht

#### RRULE

- `POST /api/rrule/validate` - RRULE validieren
- `POST /api/rrule/expand` - RRULE expandieren

## ♿ Barrierefreiheit

### WCAG 2.2 AA Compliance

- ✅ **Tastaturnavigation** für alle interaktiven Elemente
- ✅ **Screen Reader** kompatibel mit ARIA-Attributen
- ✅ **Farbkontrast** mindestens 4.5:1 für normalen Text
- ✅ **Focus-Management** mit sichtbaren Focus-Indikatoren
- ✅ **Responsive Design** bis 320px Breite
- ✅ **Zoom-Unterstützung** bis 200% ohne horizontales Scrollen
- ✅ **Reduced Motion** Unterstützung

### Accessibility Testing

```bash
# Automated Accessibility Tests
npm run test:a11y

# Manual Testing Checklist
# - Keyboard-only Navigation
# - Screen Reader Testing (NVDA/VoiceOver)
# - High Contrast Mode
# - Zoom Testing
```

## 🧪 Testing

### Test-Suites

```bash
# Alle Tests ausführen
npm test

# Accessibility Tests
npm run test:a11y

# E2E Tests
npm run test:e2e

# Unit Tests
npm run test:unit
```

### Coverage Reports

```bash
# Test Coverage generieren
npm run test:coverage
```

## 📦 Build & Deployment

### Production Build

```bash
# Frontend Build
npm run build

# Backend für Production vorbereiten
cd backend
composer install --no-dev --optimize-autoloader
```

### Deployment

Siehe [DEPLOYMENT.md](./DEPLOYMENT.md) für detaillierte Deployment-Anweisungen auf Hetzner Shared Hosting.

## 🔧 Entwicklung

### Code-Standards

- **TypeScript** für Type Safety
- **ESLint** + **Prettier** für Code-Formatierung
- **Konventionelle Commits** für Git-Messages
- **WCAG 2.2 AA** für Accessibility

### Git Workflow

```bash
# Feature Branch erstellen
git checkout -b feature/neue-funktion

# Changes commiten
git add .
git commit -m "feat: neue Funktion hinzugefügt"

# Push und Pull Request
git push origin feature/neue-funktion
```

### Environment Variables

#### Frontend (.env)

```env
VITE_API_URL=http://localhost:8080
VITE_APP_TITLE="Hypnose Stammtisch"
```

#### Backend (.env)

```env
# Datenbank
DB_HOST=localhost
DB_NAME=hypnose_stammtisch
DB_USER=username
DB_PASS=password

# E-Mail
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=mail@example.com
MAIL_PASSWORD=password
MAIL_FROM_ADDRESS=noreply@example.com

# Security
APP_SECRET=your-secret-key
RATE_LIMIT_MAX_REQUESTS=10
```

## 📊 Performance

### Optimierungen

- **Tree Shaking** für minimale Bundle-Größe
- **Code Splitting** für bessere Load-Zeiten
- **Image Optimization** mit Lazy Loading
- **Caching** für API-Responses
- **Gzip Compression** für Textressourcen

### Metriken

- **LCP**: < 2.5s (Largest Contentful Paint)
- **FID**: < 100ms (First Input Delay)
- **CLS**: < 0.1 (Cumulative Layout Shift)
- **Bundle Size**: < 500KB (gzipped)

## 🔒 Sicherheit

### Implementierte Maßnahmen

- **Input Validation** mit Zod-Schemas
- **SQL Injection** Schutz durch Prepared Statements
- **XSS Protection** durch automatisches Escaping
- **CSRF Protection** für Formulare
- **Rate Limiting** für API-Endpunkte
- **Honeypot Fields** für Spam-Schutz
- **HTTPS Enforcement** in Production

### Datenschutz (DSGVO)

- ✅ **Minimale Datenerhebung** - nur notwendige Daten
- ✅ **Transparente Datenschutzerklärung**
- ✅ **Einwilligungsmanagement** für Kontaktformulare
- ✅ **Löschfristen** für temporäre Daten
- ✅ **Keine Tracking-Cookies** ohne Einwilligung

## 🤝 Contributing

### Beitragen

1. Fork das Repository
2. Feature Branch erstellen
3. Changes implementieren
4. Tests hinzufügen/aktualisieren
5. Pull Request erstellen

### Code of Conduct

Bitte lesen Sie unseren [Code of Conduct](./CODE_OF_CONDUCT.md) vor dem Beitragen.

## 📝 Changelog

Siehe [CHANGELOG.md](./CHANGELOG.md) für Versionshistorie.

## 📜 Lizenz

Dieses Projekt steht unter der [MIT Lizenz](./LICENSE).

## 📞 Support

### Community

- **Website**: <https://hypnose-stammtisch.de>
- **E-Mail**: <info@hypnose-stammtisch.de>
- **GitHub Issues**: Für Bugs und Feature-Requests

### Entwickler-Support

- **Dokumentation**: Inline-Kommentare und JSDoc
- **API-Dokumentation**: OpenAPI-Spezifikation verfügbar
- **Accessibility Guide**: Siehe `src/utils/accessibility.ts`

---

**_Erstellt mit ❤️ für die Hypnose-Community_**

_Diese Anwendung wurde unter Berücksichtigung von Barrierefreiheit, Performance und Sicherheit entwickelt, um eine inklusive und sichere Plattform für alle Nutzer zu bieten._

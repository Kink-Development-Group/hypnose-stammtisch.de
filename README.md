# 🧘‍♀️ Hypnose Stammtisch - Event Platform

Zentrale Plattform für geprüfte Hypnose-Events im deutschsprachigen Raum. Spezialisiert auf Freizeit- und erotische Hypnose mit Fokus auf Konsens, Professionalität und Sicherheit.

## 🎯 Über die Plattform

Diese Website dient als **zentraler Kalender und Informationsquelle** für die deutschsprachige Hypnose-Community:

- **Zielgruppe**: Erwachsene mit Interesse an Freizeit- und erotischer Hypnose
- **Geografischer Fokus**: Hamburg, Rhein-Main-Gebiet, Bremen und weitere deutsche Städte
- **Community**: Vom neugierigen Anfänger bis zum erfahrenen Hypnotiseur/Subject
- **Werte**: Konsens, Professionalität, Sicherheit

### Bekannte Event-Reihen

- **Hamburger Hypnose Munch** - Monatliche Treffen im Club Catonium
- **Hypnose-Stammtisch Rhein-Main** - Events in Mainz
- **Hypno Study Frankfurt** - Deeptalk-Stammtisch für Fortgeschrittene

## ⚡ Quick Start

```bash
# 1. Repository klonen
git clone <repository-url>
cd hypnose-stammtisch.de

# 2. Dependencies installieren
npm run setup

# 3. Development starten (Frontend + Backend parallel)
npm run dev
```

**Das war's!** 🎉

- **Frontend**: [http://localhost:5173](http://localhost:5173)
- **Backend API**: [http://localhost:8000](http://localhost:8000)
- **API Test Suite**: [http://localhost:5173/test-api.html](http://localhost:5173/test-api.html)

## 🚀 Verfügbare Befehle

### Development Commands

```bash
# Alles parallel starten
npm run dev

# Einzeln starten
npm run dev:frontend  # Nur Frontend
npm run dev:backend   # Nur Backend
```

### Setup & Installation

```bash
npm run setup          # Alles installieren
npm install            # Nur Frontend
npm run install:backend # Nur Backend
```

## 🏗️ Tech Stack

### Frontend

- **Svelte** + TypeScript
- **Vite** (Build Tool)
- **Tailwind CSS** (Styling)
- **Auto-Import** (Components)

### Backend

- **PHP 8.1+** (API Server)
- **PDO** (Database)
- **Composer** (Autoloading)
- **SQLite** (Development DB)

### Development Tools

- **concurrently** (Parallel Execution)
- **Vite Proxy** (API Integration)
- **Hot Module Replacement**

## 📁 Projektstruktur

```bash
hypnose-stammtisch.de/
├── src/                    # Frontend (Svelte)
│   ├── components/         # UI Components
│   ├── pages/             # Route Pages
│   ├── stores/            # Svelte Stores
│   └── App.svelte         # Main App
├── backend/               # Backend (PHP)
│   ├── api/               # API Endpoints
│   ├── src/               # PHP Classes
│   ├── migrations/        # Database
│   └── start-dev.*        # Dev Scripts
├── public/                # Static Assets
└── package.json           # Main Config
```

## 🔧 Features

### Frontend Features

- 📱 Responsive Design
- 🎨 Moderne UI mit Tailwind
- ⚡ Hot Module Replacement
- 🧩 Component Auto-Import
- 📄 Multi-Page Application

### Backend Features

- 🚀 REST API
- 📅 Event Management
- 📧 Contact Form
- 📅 ICS Calendar Export
- 🔒 CORS Support

### Development Features

- 🔄 Parallel Frontend/Backend
- 🔗 Proxy Integration
- 🧪 API Test Suite
- 📊 Error Handling
- 🔧 Cross-Platform Scripts

## 📚 API Endpoints

```bash
GET  /api/events           # Alle Events
GET  /api/events/upcoming  # Kommende Events
GET  /api/events/featured  # Featured Events
GET  /api/events/{id}      # Einzelnes Event
POST /api/contact          # Kontakt Form
GET  /calendar.ics         # ICS Calendar
```

## 🎯 Nächste Schritte

1. **Backend Database**: Echte Datenbank konfigurieren
2. **Frontend Design**: Branding und Custom Styles
3. **Content Management**: Admin Interface
4. **Deployment**: Production Setup

## 🆘 Hilfe & Support

- 📖 **Vollständige Dokumentation**: [`DEV-GUIDE.md`](./DEV-GUIDE.md)
- 🧪 **API Tests**: [http://localhost:5173/test-api.html](http://localhost:5173/test-api.html)
- 🐛 **Troubleshooting**: Siehe DEV-GUIDE.md

---

**Happy Coding!** 🎉

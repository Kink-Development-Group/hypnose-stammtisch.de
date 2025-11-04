# Lokal gehostete Fonts

Diese Verzeichnis enthält lokal gehostete Google Fonts, um die Performance zu verbessern und externe Abhängigkeiten zu reduzieren.

## Verwendete Fonts

- **Inter** (300, 400, 500, 600, 700)
- **Playfair Display** (400, 600, 700)
- **Plus Jakarta Sans** (300, 400, 500, 600, 700)

## Font-Dateien aktualisieren

Um die Font-Dateien neu herunterzuladen (z.B. wenn Google Updates veröffentlicht):

```bash
npm run fonts:download
```

Dieses Script:

1. Lädt die neuesten WOFF2-Versionen von Google Fonts herunter
2. Speichert sie in `public/fonts/`
3. Generiert die `src/fonts.css` mit allen @font-face Definitionen

## Integration

Die Fonts werden automatisch durch den Import in `src/app.css` geladen:

```css
@import "./fonts.css";
```

## Performance-Vorteile

- **Keine externen Requests**: Alle Font-Dateien werden vom eigenen Server geladen
- **Bessere Caching-Kontrolle**: Langzeit-Caching möglich (1 Jahr)
- **Keine CORS-Probleme**: Alle Ressourcen von derselben Domain
- **DSGVO-konform**: Keine Verbindung zu Google-Servern
- **Schnellere Ladezeiten**: Reduzierte DNS-Lookups und Verbindungsaufbau

## Format

Alle Fonts liegen im modernen **WOFF2**-Format vor, das:

- Hervorragende Kompression bietet (bis zu 30% kleiner als WOFF)
- Von allen modernen Browsern unterstützt wird
- Optimale Performance gewährleistet

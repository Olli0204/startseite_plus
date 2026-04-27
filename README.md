# Startseite Plus

JTL-Shop 5 Plugin, das drei OPC-Portlets für den OnPage Composer bereitstellt.

## Portlets

### Slider Plus
Vollbild-Bildslider mit automatischem Wechsel und Navigationspfeilen.

**Einstellungen:**
- **Name** — ID des Sliders (wird als HTML-ID verwendet)
- **Farbe für Hover-Effekt** — Textfarbe des Button-Links beim Hover
- **Slides** — Bilder mit Titel, Beschreibung, Alt-Text, Button-Text und Link

### Heading
Zentrierte Überschrift mit konfigurierbarer Hover-Farbe.

**Einstellungen:**
- **Name** — Anzeigetext der Überschrift
- **Textfarbe bei Hover** — Linkfarbe beim Hover

### Bilder Box
Responsive Bildgalerie, die Bilder paarweise nebeneinander anzeigt. Jedes Bild kann mit zwei Kategorie-Links versehen werden.

**Einstellungen:**
- **Bilder** — Bilder mit Titel, Beschreibung, Alt-Text, Kategorie-Links (links/rechts) und zugehörigen URLs

## Kompatibilität

| Plugin-Version | JTL-Shop      |
|----------------|---------------|
| 1.1.0          | 5.5.1 – 5.7.0 |
| 1.0.2          | 5.5.1 – 5.5.3 |

## Installation

1. Plugin-Ordner in das Verzeichnis `plugins/` des JTL-Shops kopieren
2. Im Shop-Backend unter **Plugin Manager** → **Startseite Plus** installieren
3. Portlets stehen anschließend im OnPage Composer unter der Gruppe **Startseite Plus** zur Verfügung

## Changelog

### 1.1.0
- Slider: CSS-Typo `borderstyle` behoben, Button-Border auf `none` gesetzt
- Slider: fehlende `getButtonHtml()`-Methode ergänzt
- Bilder Box: nicht geschlossener `<i>`-Tag im Preview behoben
- Bilder Box: undefinierte Variable `$slideTitle` auf `$slide.title` korrigiert
- Bilder Box: fehlende schließende `</div>` bei ungerader Slide-Anzahl behoben
- Heading: veraltetes `<center>`-Tag durch CSS ersetzt
- Heading: Inline-`font-size` in CSS-Block verschoben
- Namespaces aller Portlets an Verzeichnisnamen angeglichen
- MaxShopVersion auf 5.7.0 erhöht

### 1.0.2
- Initiales Release

# Startseite Plus

JTL-Shop-5-Plugin mit sechs OPC-Portlets (OnPage Composer) für eine moderne, ansprechende Startseite.
Alle Bausteine sind responsiv, nutzen die Primärfarbe des Templates (`--primary`) als Standard-Akzent,
laden Bilder lazy mit `srcset`/WebP und lassen sich komplett im OPC konfigurieren – ohne CSS-Kenntnisse.

## Portlets (Gruppe „Startseite Plus“)

| Portlet | Klasse | Zweck |
|---|---|---|
| Hero-Slider | `sliderPlus` | Großer Bild-Slider mit Kicker, Überschrift, Text und Button je Slide |
| Überschrift Plus | `headingPlus` | Überschrift mit Linien/Akzentstrich/Balken, Kicker, Unterzeile, optional verlinkt |
| Kategorie-Kacheln | `pictureBox` | Bild-Grid (2–4 Spalten) mit Titel, Hauptlink und zwei Unterlinks (z. B. Männer / Frauen) |
| Vorteile-Leiste | `uspBar` | Icons + Texte („Gratis Versand ab 100 €“), als Leiste, Karten oder kompakt |
| Aktions-Banner | `promoBanner` | Bild mit Text, bis zu zwei Buttons und optionalem Countdown; blendet sich nach Ablauf aus |
| Text & Bild | `textImage` | Zweispaltige Sektion (z. B. „Über uns“) mit Fließtext, Kennzahlen und Button |

### Hero-Slider
- Seitenverhältnis getrennt für Desktop und Mobil (Bild wird per `object-fit: cover` zugeschnitten, Bildausschnitt je Slide wählbar) – ersetzt den alten „200 %-Breite“-Hack für Smartphones
- Übergänge: Schieben, Überblenden, Ken Burns (langsamer Zoom)
- Abdunkelung (Overlay), Textposition, Textstil (frei, dunkler/heller Kasten), Schriftgröße, Button-Stil
- Autoplay, Intervall, Pause bei Mouseover, Pfeile/Punkte ein- und ausschaltbar, Touch-Swipe
- Ohne Button-Text ist das ganze Bild verlinkt
- Basis: Bootstrap-4-Carousel aus NOVA, kein zusätzliches JavaScript

### Kategorie-Kacheln
- 2/3/4 Spalten (Desktop), 1/2 Spalten (Smartphone), festes Seitenverhältnis oder Originalhöhe
- Hover-Zoom oder Anheben, Verlauf/Abdunkelung, Titelposition und -stil, abgerundete Ecken
- Hauptlink der Kachel plus zwei Unterlinks als Pill-Buttons, Buttons oder Text mit Schrägstrich
- Datenformat der Version 1.x (`kat`, `link3`, `kat2`, `link2`) bleibt gültig

### Aktions-Banner
- Layouts: Text auf dem Bild, Bild links, Bild rechts; optionales separates Smartphone-Bild
- Kicker, Überschrift (H1–H4), Rich-Text, zwei Buttons mit eigenem Stil
- Countdown bis zu einem Endzeitpunkt; danach Banner ausblenden, Hinweistext zeigen oder ohne Countdown weiterzeigen

### Text & Bild
- Bild links/rechts mit 40/50/60 % Breite; Bildstile: abgerundet, Schatten, versetzter Akzentrahmen, Kreis
- Kicker, Überschrift (H1–H4), Rich-Text, Kennzahlen („4000+ Boards auf Lager“) nebeneinander oder in Kästchen, Button
- Hintergrund: keiner, hellgrau, Akzent (hell), dunkel, Akzentfarbe

### Vorteile-Leiste
- Font-Awesome-Icon (z. B. `fas fa-truck`) oder eigenes Bild, Titel, Text, optionaler Link je Eintrag
- Layouts Leiste / Karten / Kompakt, Icon im Kreis/Quadrat/frei, Trennlinien, Hintergrund
- Auf Smartphones wird die Leiste horizontal wischbar

### Überschrift Plus
- HTML-Tag H1–H6, Ausrichtung, Stil (Linien, Akzentstrich, Balken, ohne), Größe, Großbuchstaben
- Kicker, Unterzeile, optionaler Link

Alle Portlets haben zusätzlich die OPC-Standard-Tabs **Styles** (Hintergrund, Schriftfarbe, Abstände, eigene
CSS-Klasse, Ausblenden je Breakpoint) und **Animation** (WOW.js-Einblendungen).

## Technik

- `Portlets/Common/PortletHelper.php` – Trait mit gemeinsamen Property-Bausteinen (`propSelect`, `propRepeater`, …),
  abgesicherten Lesefunktionen (`getKey`, `getNum`, `isTrue`, `getItems`, `safeColor`, `safeUrl`) und der Migration
  der 1.x-Einstellungen.
- `Portlets/Common/common.css` – gemeinsame Styles (Buttons `.sp-btn--*`, Kicker, Hintergründe, Countdown, `.sp-full-bleed`).
- Jedes Portlet hat ein eigenes `style.css`; beide Dateien werden über `getExtraCssFiles()` mit Versions-Parameter
  eingebunden (auch im OPC-Editor). Es gibt kein Inline-CSS mehr in den Templates.
- Farben: `--sp-accent` (Akzentfarbe des Portlets) → `--primary` (Template) → `#FFA54F`.
- `portlet_input_types/repeater.tpl` – generischer Listen-Editor (Bild + beliebige Felder: text, textarea, select,
  checkbox, number, color) mit Drag-&-Drop-Sortierung, Kopieren und Löschen. Leere Einträge werden beim Speichern verworfen.
- Alle Ausgaben sind escaped; URLs mit `javascript:`/`data:` werden verworfen, Farben und CSS-Schlüssel validiert.
- Breite: In Bereichen außerhalb des Containers (z. B. `opc_before_main`) kann jedes Portlet auf Inhaltsbreite
  begrenzt werden, innerhalb des Containers auf volle Bildschirmbreite (`.sp-full-bleed`) ausbrechen.

## Update von Version 1.x

Bestehende Seiten laufen ohne Nacharbeit weiter:

- **Hero-Slider:** `name` (alte HTML-ID) entfällt, die Hover-Farbe `color` wird beim ersten Laden nach `accent-color`
  übernommen. Slides behalten `url`, `title`, `desc`, `button`, `link`, `alt`. Standard-Seitenverhältnis bleibt
  „Originalhöhe“; für Smartphones gilt neu 3:2 mit Bildzuschnitt.
- **Überschrift:** `name` wird nach `text` übernommen, `color` nach `accent-color`; Standardstil „Linien“ entspricht dem alten Look.
- **Kategorie-Kacheln:** alle Felder bleiben erhalten. Neue Standards: Verlauf von unten, freistehender Titel mit Schatten,
  Unterlinks als Pill-Buttons. Den alten Look erhält man mit Titelstil „Heller Kasten“ und Unterlinks „Text mit Schrägstrich“.
- Die alten Klassen (`.bilder_box`, `.head-banner-main`, `.button-slider-index`, `#nohover`) werden nicht mehr ausgegeben;
  die zugehörigen Regeln in `snowshop_template/themes/snowshop/sass/snowshop.scss` sind damit toter Code.

## Kompatibilität

| Plugin-Version | JTL-Shop      |
|----------------|---------------|
| 2.0.0          | 5.5.1 – 5.8.0 |
| 1.1.0          | 5.5.1 – 5.7.0 |
| 1.0.2          | 5.5.1 – 5.5.3 |

## Installation / Update

1. Plugin-Ordner in das Verzeichnis `plugins/` des JTL-Shops kopieren
2. Im Shop-Backend unter **Plugin-Manager** → **Startseite Plus** installieren bzw. **aktualisieren**
3. Portlets stehen im OnPage Composer unter der Gruppe **Startseite Plus** zur Verfügung
4. Nach dem Update Template-Cache leeren (Systemverwaltung → Cache)

## Changelog

### 2.0.0
- Neu: Vorteile-Leiste, Aktions-Banner mit Countdown, Text & Bild mit Kennzahlen
- Hero-Slider: Seitenverhältnis Desktop/Mobil, Bildausschnitt, Overlays, Textposition/-stil, Übergänge, Autoplay-Optionen,
  Kicker und Text je Slide, Bild-Verlinkung ohne Button, Touch-Swipe, `srcset`/WebP/Lazy-Loading
- Kategorie-Kacheln: CSS-Grid mit 2–4 Spalten, Seitenverhältnis, Hover-Effekte, Overlays, Titel-/Linkstile, Hauptlink
- Überschrift: HTML-Tag, Stile, Größen, Kicker, Unterzeile, Link
- OPC: generischer Listen-Editor `repeater` ersetzt `image-set-button`; Auswahlfelder, Hilfetexte, Standardwerte,
  Styles- und Animation-Tabs für alle Portlets
- Technik: gemeinsamer Helper-Trait, CSS pro Portlet statt Inline-`<style>`, konsequentes Escaping,
  Migration der 1.x-Einstellungen, MaxShopVersion 5.8.0

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

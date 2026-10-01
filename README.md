# Startseite Plus

JTL-Shop-5-Plugin mit sieben OPC-Portlets (OnPage Composer) für eine moderne, ansprechende Startseite.
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
| Text und Bild | `textImage` | Zweispaltige Sektion (z. B. „Über uns“) mit Fließtext, Kennzahlen und Button |
| Deal-Banner | `dealBanner` | Bewirbt einen JTL-Kupon (z. B. Bundle-Rabatt in einer Kategorie); „In den Warenkorb“ legt alle Artikel ab und löst den Code automatisch ein |

### Hero-Slider
- Seitenverhältnis getrennt für Desktop und Mobil (Bild wird per `object-fit: cover` zugeschnitten, Bildausschnitt je Slide wählbar) – ersetzt den alten „200 %-Breite“-Hack für Smartphones
- Übergänge: Schieben, Überblenden, Ken Burns (langsamer Zoom)
- Abdunkelung (Overlay), Textposition, Textstil (frei, dunkler/heller Kasten), Schriftgröße, Button-Stil
- Autoplay, Intervall, Pause bei Mouseover, Pfeile/Punkte ein- und ausschaltbar, Touch-Swipe
- Ohne Button-Text ist das ganze Bild verlinkt
- Basis: Bootstrap-4-Carousel aus NOVA, kein zusätzliches JavaScript

### Deal-Slides im Hero-Slider (ab 2.6.0)
- Pro Slide wählt man den **Slide-Typ**: „Bild-Slide“ (wie bisher) oder „Deal-Slide“. Der Editor zeigt je Typ nur die
  passenden Felder; beim Deal-Slide gibt es Kupon- und Artikel-Picker wie im Deal-Banner (ab 2.7.0)
- Der Deal-Slide zeigt statt der Beschriftung eine Deal-Karte mit möglichst vielen Infos: Artikel mit Bild, Name,
  Variante und Einzelpreis, Bundle-Preis, „Du sparst …“, Gültigkeit und Code; ohne gewählte Artikel gelten die des Kupons
- Button (ab 2.9.0): verlinkt auf eine per Kategorie-Picker gewählte Kategorie (z. B. die Aktionsseite mit Deal-Banner)
  oder einen eigenen Link; Text frei wählbar, Standard „Zur Aktion“. Ohne Ziel: „In den Warenkorb“ mit Code-Einlösung
- Überschrift, Kicker und Text des Slides ersetzen die automatischen Texte; die Karte steht an der Textposition des Sliders
- Bild optional: ohne Bild bekommt der Slide eine Hintergrundfarbe und das Seitenverhältnis des ersten Bild-Slides
  (ab 2.8.0; ohne Bild-Slides 21:9, mobil 4:3), bei festem Seitenverhältnis dieses
- Slider mit Deal-Slide: alle Slides gleich hoch, Mindesthöhe 16rem (mobil 23rem, ab 2.10.0), Bilder werden dafür zugeschnitten
- Smartphone (ab 2.10.0): Artikelzeilen mit Bild, Name, Variante und Preis (ab drei Artikeln zweispaltig), Bundle-Preis,
  „Du sparst …“, Infozeile mit Code und Gültigkeit, Button; bei mehr Höhe zusätzlich Beschreibung und Code-Feld
- Die Karte richtet sich nach dem Platz im Slide (CSS Container Queries): hoch → senkrechte Karte, breit und flach →
  waagerechte Leiste (Artikel | Titel und Preis | Button), schmal → kompakte Karte (Titel, Preis, Button); Browser ohne
  Container Queries nutzen die Darstellung nach Bildschirmbreite
- Ungültige Kupons blenden den Slide im Shop aus; läuft der Kupon während des Besuchs ab, entfernt das Skript den Slide

### Kategorie-Kacheln
- 2/3/4 Spalten (Desktop), 1/2 Spalten (Smartphone), festes Seitenverhältnis oder Originalhöhe
- Hover-Zoom oder Anheben, Verlauf/Abdunkelung, Titelposition und -stil, abgerundete Ecken
- Hauptlink der Kachel plus zwei Unterlinks als Pill-Buttons, Buttons oder Text mit Schrägstrich
- Datenformat der Version 1.x (`kat`, `link3`, `kat2`, `link2`) bleibt gültig

### Aktions-Banner
- Layouts: Text auf dem Bild, Bild links, Bild rechts; optionales separates Smartphone-Bild
- Kicker, Überschrift (H1–H4), Rich-Text, zwei Buttons mit eigenem Stil
- Countdown bis zu einem Endzeitpunkt; danach Banner ausblenden, Hinweistext zeigen oder ohne Countdown weiterzeigen

### Deal-Banner
- Gedacht für Kategorie- und Aktionsseiten, z. B. über der Überschrift „Taschen“ (OPC-Bereich oberhalb des Inhalts)
- Einzige Pflichtangabe ist der **Kupon-Code**. Rabatt (fester Betrag oder Prozent), Gültig-bis-Datum und – wenn im
  Portlet keine Artikel ausgewählt sind – die Artikel werden aus dem JTL-Kupon gelesen
- Kupon-Picker (ab 2.5.0): Kupon nach Name oder Code suchen (ohne Suchtext die neuesten), mit Rabatt, Gültigkeit,
  Status (aktiv, abgelaufen, …) und Anzahl hinterlegter Artikel; gespeichert wird weiterhin der Code
- Ausgeblendet (ab 2.6.1, auch in der Deal-Auswahl des Hero-Sliders): Einmal-Codes (z. B. Newsletter-Kupons),
  auf einzelne Kunden beschränkte Kupons und Massenerstellungen (erkannt an mehrfach vorkommendem Kupon-Namen).
  Ein bereits gespeicherter Kupon bleibt sichtbar bzw. ausgewählt
- Artikel-Picker (ab 2.4.0): Suche nach Name, Artikelnummer oder GTIN direkt im OPC, Treffer mit Vorschaubild anklicken,
  Reihenfolge per Ziehen ändern (höchstens 4). Früher eingetragene Artikelnummern werden automatisch übernommen
- Varianten (ab 2.7.1): Kinderartikel erscheinen eingerückt unter ihrem Vaterartikel, mit Variationswerten
  („Größe: 158 / Breite: Wide“); die Suche nach dem Vaterartikel listet alle Kinder. Kinderartikel legt der Warenkorb-Button
  mit ihren Variationswerten ab; Vaterartikel sind markiert („Variante wählen“), weil sie keinen direkten Kauf erlauben
- Layouts: Bundle-Karte (Artikelbilder, Summe, Bundle-Preis), schlanke Coupon-Leiste, Gutschein-Ticket
- Button „Beide/Alle in den Warenkorb“: legt alle Artikel per IO-Aufruf (`startseitePlusDeal`) in den Warenkorb und löst den
  Kupon mit der Core-Prüfung (`Kupon::check()`/`accept()`) ein; ein bereits eingelöster anderer Kupon wird nicht ersetzt.
  Nur für Artikel ohne Variationsauswahl – sonst bleibt es beim Code mit „Kopieren“-Button
  Seit 2.13.2 cache-sicher: das HTML enthält kein CSRF-Token; `deal.js` holt beim Klick zuerst per IO
  (`startseitePlusDealToken`, nur POST) das Token der eigenen Sitzung und schickt es dann mit dem Warenkorb-Aufruf
- Ist der Kupon inaktiv, abgelaufen, aufgebraucht oder für die Kundengruppe nicht gültig, erscheint der Banner im Shop nicht;
  im OPC-Editor steht dann ein Hinweis mit dem Grund
- Countdown optional: bis zum Ablauf des Kupons oder aus der Countdown-Verwaltung
- **Kupon für ein Bundle anlegen:** Standardkupon, Wert z. B. 15 € (fester Betrag), unter „Artikel“ beide Artikelnummern
  eintragen und als Mindestbestellwert die Summe beider Artikel setzen – dann greift der Code nur, wenn beide im Warenkorb liegen

### Text und Bild
- Bild links/rechts mit 40/50/60 % Breite; Bildstile: abgerundet, Schatten, versetzter Akzentrahmen, Kreis
- Kicker, Überschrift (H1–H4), Rich-Text, Kennzahlen („4000+ Boards auf Lager“) nebeneinander oder in Kästchen, Button
- Hintergrund: keiner, hellgrau, Akzent (hell), dunkel, Akzentfarbe

### Vorteile-Leiste
- Font-Awesome-Icon oder eigenes Bild, Titel, Text, optionaler Link je Eintrag. NOVA enthält Font Awesome **5** Free: `fas fa-snowboarding`, `fas fa-skiing`, `fas fa-mountain`, `fas fa-truck` funktionieren, Version-6-Namen wie `fa-person-snowboarding` nicht
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
- Jedes Portlet hat eine `preview.css` für den OPC-Editor (lädt `common.css` und `style.css` per `@import`), damit neu
  hineingezogene Portlets sofort gestylt sind. Versions-Parameter bei jedem Release mit anheben.
- Jedes Portlet hat ein eigenes `style.css`; beide Dateien werden über `getExtraCssFiles()` mit Versions-Parameter
  eingebunden (auch im OPC-Editor). Es gibt kein Inline-CSS mehr in den Templates.
- Farben: `--sp-accent` (Akzentfarbe des Portlets) → `--primary` (Template) → `#FFA54F`.
- `Portlets/Common/deal.css`, `deal.js`, `deal-code.tpl`, `deal-button.tpl`, `deal-hero.tpl` – gemeinsame Deal-Bausteine
  für Deal-Banner und Deal-Slides (Portlets geben benötigte Common-Stylesheets über `getSharedCssFiles()` an).
- `portlet_input_types/picker-core.tpl` – gemeinsamer Kern (JS/CSS) von Kupon- und Artikel-Picker, auch als Repeater-Feldtypen
  `coupon` / `products`. Der Repeater kennt außerdem `hint` (Hinweistext) und `showIf` (Feld nur bei bestimmtem Wert eines
  Auswahlfelds im selben Eintrag zeigen).
- Kategorie-Picker: Repeater-Feldtyp `category` (Admin-IO `startseitePlusCategorySearch`, Wert = Kategorie-ID,
  Anzeige mit Pfad „Männer › Zubehör“); die Shop-URL baut `DealService::categoryUrl()` aus `tseo` (Fallback `?k=ID`).
- `portlet_input_types/couponpicker.tpl` – Kupon-Picker (Admin-IO `startseitePlusCouponSearch`, gespeichert als Code).
- `portlet_input_types/productpicker.tpl` – Artikel-Picker (Admin-IO `startseitePlusProductSearch`, gespeichert als `"101;102"`).
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

## Countdown-Verwaltung (seit 2.1.0)

Unter **Plugins → Startseite Plus → Countdowns** werden Countdowns zentral gepflegt (Tabelle `startseite_plus_countdown`):

| Feld | Bedeutung |
|---|---|
| Name | interner Name, erscheint in der Auswahl des Aktions-Banners |
| Endzeitpunkt | Shop-Zeitzone; die Serverzeit wird im Formular angezeigt |
| Beschriftung DE/EN | Text vor den Ziffern; auf der Artikelseite die Überschrift der Box (EN leer = DE) |
| Darstellung | Kästchen oder Textzeile |
| Nach Ablauf | Ausblenden (blendet auch den Banner aus), Hinweistext anzeigen, ohne Countdown weiter anzeigen |
| Hinweistext DE/EN | Text nach Ablauf bei „Hinweistext anzeigen“ |
| Auf Artikeldetailseiten | Nein, nur bei aktivem Sonderpreis, immer |
| Aktiv | inaktive Countdowns werden nirgends ausgegeben |

Verwendung:
- **Aktions-Banner**: Tab „Countdown“ → Dropdown „Countdown“: *Kein Countdown*, ein Countdown aus der Verwaltung oder *Eigener Endzeitpunkt*. Bei einem Countdown aus der Verwaltung kommen Beschriftung, Darstellung und Ablaufverhalten von dort; die Felder darunter gelten nur für den eigenen Endzeitpunkt. Banner aus 2.0.x mit angehaktem „Countdown anzeigen“ werden beim Laden automatisch auf „Eigener Endzeitpunkt“ gesetzt.
- **Artikeldetailseite**: freigegebene Countdowns erscheinen oberhalb der Variationen/Kaufbox (NOVA-Block `productdetails-details-include-variation`) als Box in der Akzentfarbe; „nur bei Sonderpreis“ prüft `Preise->Sonderpreis_aktiv`. Abgelaufene Countdowns erscheinen dort nur mit Hinweistext. Diese Anzeige ersetzt den Countdown aus `artikel_details_plus` (dort seit 0.3.0 entfernt).

Technik: `Countdown/CountdownService.php` liefert View-Arrays (ISO-Endzeit mit Zeitzone, Beschriftungen je Sprache), `Portlets/Common/countdown.tpl` ist das gemeinsame Snippet, `Portlets/Common/countdown.js` der Zähler (vom Portlet über `getExtraJsFiles()`, auf der Artikelseite per `<script defer>`). Admin: `ModelBackendController` auf Basis von `GenericModelController` mit `Models/Countdown.php`.

## Newsletter-Deals (seit 2.11.0)

Versteckte Aktionsseiten für Newsletter-Abonnenten, verwaltet im Plugin-Tab **„Newsletter-Deals“**:

- Jede Seite ist eine **normale Artikelliste** (Filter, Sortierung, Seiten, Mobil-Filter wie auf Kategorieseiten) mit den im
  Artikel-Picker gewählten Artikeln (bis 200; ohne Auswahl die im Kupon hinterlegten Artikel). Gewählte Varianten bringen
  ihren Vaterartikel in die Liste.
- Erreichbar nur über den **geheimen Link** `https://<shop>/newsletter-deals-<zufall>` (frei änderbar, Kollisionen mit
  Shop-URLs werden abgelehnt). Kein Menüeintrag, `noindex, nofollow`.
- **Deutsch und Englisch (seit 2.12.0):** jede Seite hat einen deutschen und einen englischen Link (Standard
  `<deutscher Link>-en`). Wie bei Kategorien bestimmt der Link die Sprache (Session-Sprache wechselt mit): Überschrift/Text
  EN, Artikelnamen, Preise und Hinweise auf Englisch; der Sprachumschalter wechselt zwischen beiden Links.
- Kopf der Liste: Überschrift, Text (DE/EN) und – mit Kupon – eine Code-Karte mit Rabatt, Kopieren-Button, Gültigkeit und
  Countdown. Der Rabatt kommt aus dem JTL-Kupon; damit er nur für die Deal-Artikel gilt, im Kupon die Artikel hinterlegen.
- **Deal-Preise (seit 2.13.0):** je Seite beliebig viele Regeln – *Festpreis* (gewählte Artikel kosten je Stück X €,
  Vaterartikel gelten für alle Varianten) und *Set-Preis* (Artikel kostet X €, wenn ein Set-Partner im Warenkorb liegt,
  höchstens so oft wie Partner im Warenkorb). Beispiel Newsletter: Boards 250 €, Transfer 150 €, Upshot 120 €, Upshot im
  Set mit dem Odyssey 100 €. Freigeschaltet pro Sitzung durch den Besuch des Links oder den **Deal-Code** im Kupon-Feld
  des Warenkorbs. Der Warenkorb übernimmt den Deal-Preis direkt als Positionspreis (Hinweis „Newsletter-Deal (statt …)“),
  nur wenn er günstiger als der Shop-Preis ist; Liste und Artikelseite zeigen „Newsletter-Preis …“ / „Im Set mit …“ –
  seit 2.13.1 cache-sicher: das HTML enthält nur leere Platzhalter, `frontend/js/newsletter-deal.js` lädt die Preise per
  IO (`startseitePlusNlDealPrices`) ausschließlich für freigeschaltete Sitzungen nach. Deal-Seiten senden
  `X-LiteSpeed-Cache-Control: no-cache`. **Nach dem Update einmal den LiteSpeed-Cache leeren.**
  Ohne eigene Artikelauswahl zeigt die Seite alle Artikel der Deal-Preise.
- **Set-Konfigurator (seit 2.14.0):** zu jeder Set-Regel eine Karte mit beiden Artikeln (Bild, Deal-Preis, „statt“-Preis,
  Größen-/Variantenauswahl, ausverkaufte Größen gesperrt, Auswahl bei mehreren Set-Partnern) und „Set in den Warenkorb –
  350 €“ – auf der Deal-Seite über der Liste und auf den Artikelseiten von Set-Artikel und Set-Partner unter der Kaufbox
  (Variante der Seite vorausgewählt). Cache-sicher: Platzhalter im HTML, Karten per IO (`startseitePlusNlDealSets`) nur für
  freigeschaltete Sitzungen, Warenkorb per `startseitePlusNlDealSetAdd` mit dem Sitzungs-Token aus `startseitePlusDealToken`.
- Zeitraum optional: vor dem Start und bei deaktivierten Seiten sehen Kunden eine 404-Seite, Admins (Link aus dem Tab,
  `?fromAdmin=yes`) eine Vorschau mit Hinweisen; nach dem Ende zeigt die Seite „Aktion beendet“ statt der Artikel.

Technik: `NewsletterDeal/DealPageRoute` registriert in `HOOK_ROUTER_PRE_DISPATCH` je Seite eine Route (inkl. angehängter
SEO-Filter `_s2`, `::…`, `__…`), lässt den Core-`DefaultController` Filter/Seiten parsen, setzt `DealPageState` als
Basiszustand des Produktfilters und rendert mit dem Core-`ProductListController`. `HOOK_PRODUCTFILTER_INIT_STATES`
initialisiert vorher den `DummyState`, sonst leitet `ProductFilter::validate()` bei Hersteller-/Kategoriefilter weg.
Tabelle `startseite_plus_nl_deal`, Template-Block `productlist-header-heading`.

## Kompatibilität

| Plugin-Version | JTL-Shop      |
|----------------|---------------|
| 2.2.0          | 5.5.1 – 5.8.0 |
| 2.1.2          | 5.5.1 – 5.8.0 |
| 2.1.1          | 5.5.1 – 5.8.0 |
| 2.1.0          | 5.5.1 – 5.8.0 |
| 2.0.2          | 5.5.1 – 5.8.0 |
| 2.0.1          | 5.5.1 – 5.7.3 |
| 2.0.0          | 5.5.1 – 5.8.0 |
| 1.1.0          | 5.5.1 – 5.7.0 |
| 1.0.2          | 5.5.1 – 5.5.3 |

## Installation / Update

1. Plugin-Ordner in das Verzeichnis `plugins/` des JTL-Shops kopieren
2. Im Shop-Backend unter **Plugin-Manager** → **Startseite Plus** installieren bzw. **aktualisieren**
3. Portlets stehen im OnPage Composer unter der Gruppe **Startseite Plus** zur Verfügung
4. Nach dem Update Template-Cache leeren (Systemverwaltung → Cache)

## Changelog

### 2.14.0
- Newsletter-Deals: Set-Konfigurator (Board- und Bindungsgröße wählen, beides mit einem Klick in den Warenkorb) auf der
  Deal-Seite und auf den Artikelseiten der Set-Artikel; `NewsletterDeal/DealSets.php`, `frontend/js/newsletter-deal.js`

### 2.13.2
- Fix: „In den Warenkorb“ im Deal-Banner und in Deal-Slides des Hero-Sliders scheiterte auf Seiten aus dem
  LiteSpeed-Seitencache („Die Artikel konnten nicht in den Warenkorb gelegt werden“) – das Button-Markup enthielt das
  CSRF-Token der Sitzung, die die Seite gerendert hatte. Das Token steht nicht mehr im HTML, `deal.js` holt es vor dem
  Warenkorb-Aufruf per IO (`startseitePlusDealToken`, nur POST); der Server prüft es weiter mit `Form::validateToken()`

### 2.13.1
- Fix: Newsletter-Preis-Hinweise waren im Live-Shop für alle Kunden sichtbar – der LiteSpeed-Seitencache lieferte eine
  für einen freigeschalteten Kunden gerenderte Artikelseite an alle aus. Hinweise werden jetzt per IO pro Sitzung
  nachgeladen (Platzhalter im HTML für alle gleich); Deal-Seiten werden nicht mehr gecacht

### 2.13.0
- Newsletter-Deals: Deal-Preise je Seite (Festpreis je Artikel, Set-Preis mit Partnerartikel, Tabelle
  `startseite_plus_nl_deal_rule`), Freischaltung per Link oder eigenem Deal-Code im Kupon-Feld, Positionspreis und
  Hinweis im Warenkorb (`HOOK_SETZTE_POSITIONSPREISE`), Preis-Hinweis in Liste und Artikelseite
  (`productdetails/price.tpl`), Kopfkarte „Deine Newsletter-Preise sind aktiv“ mit Code

### 2.12.0
- Newsletter-Deals auf Englisch: eigener englischer Link je Seite (Spalte `slug_en`, Migration setzt `<slug>-en`),
  Route stellt die Sprache um, Sprachumschalter und alle Listen-Links nutzen den Link der jeweiligen Sprache
- Kompatibel mit JTL-Shop 5.8.1 (MaxShopVersion 5.8.1; genutzte Core-Dateien 5.8.0 = 5.8.1)

### 2.11.0
- Neu: Newsletter-Deals – versteckte Artikellisten mit Kupon-Code, nur per geheimem Link (Admin-Tab „Newsletter-Deals“)
- Artikel- und Kupon-Picker funktionieren jetzt auch im Backend-Tab (globales `ioCall`), Vaterartikel ohne Warnhinweis
  über `data-parents-ok`
- Countdown-Tab erzwingt sich nicht mehr als aktiven Tab, wenn die URL einen anderen Tab verlangt

### 2.2.0
- Aktions-Banner: Countdown-Auswahl ist jetzt ein Dropdown direkt im Tab „Countdown“ (Kein Countdown / Countdowns aus der Verwaltung / Eigener Endzeitpunkt) statt einer Checkbox mit Unterfeldern; die Liste wird in jedem Kontext aus der Datenbank geladen (in 2.1.2 nur im Backend, wodurch der OPC-Editor keine Countdowns anbot)
- Banner aus 2.0.x werden beim Laden automatisch auf „Eigener Endzeitpunkt“ migriert

### 2.1.2
- Fix: Startseite mit Aktions-Banner lieferte HTTP 500. JTL rendert Templates im Legacy-Modus mit Smarty 4, das die Zuweisung `{$var = bedingung ? a : b}` nicht kennt. Zusätzlich: Countdown-Auswahlliste wird nur im Backend aus der Datenbank geladen, Datenbankfehler blenden den Countdown aus statt die Seite zu brechen, Include-Pfad des Snippets ohne `file:`-Präfix.

### 2.1.1
- Fix: „Erstellen“ in der Countdown-Verwaltung führte zu einem White-Screen. Das Formular las Felder mit Unterstrich über magische Getter (`getLabelEn()`), die JTLs DataModel nicht auflöst, und die Auswahllisten fehlten, wenn der Standard-Controller selbst auf das Formular umschaltet.

### 2.1.0
- Neu: Countdown-Verwaltung (Plugin-Tab „Countdowns“) mit mehreren Countdowns, Beschriftungen DE/EN, Ablaufverhalten und Freigabe für Artikeldetailseiten (immer oder nur bei Sonderpreis)
- Aktions-Banner wählt Countdowns aus der Verwaltung; eigener Endzeitpunkt bleibt als Fallback
- Countdown-Snippet und -Script liegen zentral in `Portlets/Common/` (kein Inline-Script mehr im Banner), Einheiten-Beschriftungen je Sprache
- Neu: `Bootstrap.php` (Artikelseiten-Hook, Admin-Tab), Migration für `startseite_plus_countdown`

### 2.0.2
- Kompatibilität mit JTL-Shop 5.8.0: `initInstance()` bekam dort den zweiten Parameter `bool $isFrontend`; die Überschreibungen in Hero-Slider und Überschrift nutzen jetzt die vollständige Signatur (vorher Whitescreen/500 auf der Startseite)

### 2.0.1
- Portlet-Titel „Text & Bild“ in „Text und Bild“ geändert: JTL erlaubt in Titeln nur `[\w/\-() ]` (Installer-Fehlercode 201)

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

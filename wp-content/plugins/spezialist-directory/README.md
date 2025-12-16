# Spezialist Directory Plugin

Ein umfassendes WordPress Directory Plugin für Spezialisten-Einträge mit Premium-Features, Stripe-Integration und User-Dashboard.

## Features

✅ **Custom Post Type "Spezialist"** mit allen notwendigen Meta-Feldern
✅ **Taxonomien** für Kategorien und Standorte
✅ **Frontend-Submission** - Nutzer können eigene Einträge erstellen
✅ **Claim-System** - Nutzer können bestehende Einträge beanspruchen
✅ **User Dashboard** - Verwaltung eigener Einträge
✅ **Premium Listings** mit Stripe Abo-Zahlungen
✅ **Such- und Filterfunktion** (SSR-basiert)
✅ **Admin-Freigabe** für neue Einträge
✅ **Responsive Design** mit professionellem Farbschema (Blau/Grün/Grau)
✅ **SEO-optimiert** mit strukturierten Daten

---

## Installation

### 1. Plugin aktivieren

Das Plugin wurde bereits erstellt unter:
`wp-content/plugins/spezialist-directory/`

Gehen Sie zu **WordPress Admin → Plugins** und aktivieren Sie **"Spezialist Directory"**.

### 2. WordPress-Seiten erstellen

Erstellen Sie folgende WordPress-Seiten im Admin-Bereich (**Seiten → Neu hinzufügen**):

#### Seite 1: Startseite / Spezialisten-Übersicht
- **Seitentitel:** Spezialisten finden
- **Permalink:** `/spezialisten/`
- **Inhalt:**
  ```
  [spezialist_listings]
  ```

#### Seite 2: Spezialist hinzufügen
- **Seitentitel:** Spezialist hinzufügen
- **Permalink:** `/spezialist-hinzufugen/`
- **Inhalt:**
  ```
  [spezialist_submit]
  ```

#### Seite 3: Dashboard
- **Seitentitel:** Mein Dashboard
- **Permalink:** `/dashboard/`
- **Inhalt:**
  ```
  [spezialist_dashboard]
  ```

**Wichtig:** Alle Seiten veröffentlichen!

---

## Konfiguration

### Kategorien und Standorte anlegen

1. Gehen Sie zu **Spezialisten → Kategorien**
2. Fügen Sie relevante Kategorien hinzu (z.B. "Steuerberater", "Rechtsanwalt", "Architekt")

3. Gehen Sie zu **Spezialisten → Standorte**
4. Fügen Sie Standorte hinzu (z.B. "Berlin", "München", "Hamburg")

### Stripe Integration einrichten

1. Gehen Sie zu **Spezialisten → Stripe Einstellungen**

2. **Testmodus (empfohlen für Entwicklung):**
   - Haken bei "Testmodus aktivieren" setzen
   - Test Publishable Key eintragen: `pk_test_...`
   - Test Secret Key eintragen: `sk_test_...`

3. **Produkte in Stripe Dashboard erstellen:**
   - Gehen Sie zu https://dashboard.stripe.com/products
   - Erstellen Sie ein wiederkehrendes Produkt "Premium Listing Monatlich"
   - Erstellen Sie ein wiederkehrendes Produkt "Premium Listing Jährlich"
   - Kopieren Sie die jeweiligen **Price IDs** (beginnen mit `price_...`)

4. **Price IDs im Plugin eintragen:**
   - Monatliches Abo Price ID: `price_...`
   - Jährliches Abo Price ID: `price_...`

5. **Webhook einrichten:**
   - Kopieren Sie die Webhook URL aus den Einstellungen
   - Fügen Sie sie in Stripe hinzu unter: Dashboard → Developers → Webhooks
   - Überwachen Sie folgende Events:
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`

---

## Stripe PHP SDK installieren (WICHTIG!)

Für die vollständige Stripe-Funktionalität muss das Stripe PHP SDK installiert werden:

### Option 1: Via Composer (empfohlen)
```bash
cd wp-content/plugins/spezialist-directory/
composer require stripe/stripe-php
```

### Option 2: Manuelle Installation
1. Download: https://github.com/stripe/stripe-php/releases
2. Entpacken nach: `wp-content/plugins/spezialist-directory/vendor/stripe/`

---

## Wichtige Einstellungen

### Nutzer-Registrierung aktivieren

Damit Nutzer Einträge erstellen und beanspruchen können:

1. **Einstellungen → Allgemein**
2. Haken bei **"Jeder kann sich registrieren"** setzen
3. Standard-Benutzerrolle: **Abonnent**

### Permalinks prüfen

1. **Einstellungen → Permalinks**
2. Empfohlen: **"Beitragsname"** oder **"Benutzerdefiniert"**
3. Speichern (wichtig für den Custom Post Type!)

---

## Nutzung

### Als Administrator

#### Einträge verwalten
- **Spezialisten → Alle Spezialisten** - Übersicht aller Einträge
- Einträge bearbeiten, löschen oder freigeben
- Premium-Status manuell aktivieren/deaktivieren

#### Claim-Anfragen verwalten
- **Spezialisten → Claim-Anfragen**
- Anfragen genehmigen oder ablehnen
- Bei Genehmigung wird der Nutzer als Autor gesetzt

#### Premium Listings
- In der Eintrags-Übersicht: Spalte "Beansprucht" und "Premium"
- Premium-Badge im Meta-Box "Premium Status"
- Manuelles Setzen oder via Stripe Abo

### Als Nutzer (Frontend)

#### Eintrag einreichen
1. Zu `/spezialist-hinzufugen/` navigieren
2. Formular ausfüllen
3. Eintrag wird zur Freigabe eingereicht (Status: "Ausstehend")

#### Eintrag beanspruchen
1. Zu einem nicht-beanspruchten Eintrag navigieren
2. Button "Eintrag beanspruchen" klicken
3. Nachricht eingeben
4. Auf Admin-Freigabe warten

#### Dashboard nutzen
1. Zu `/dashboard/` navigieren
2. Eigene Einträge anzeigen
3. Einträge bearbeiten oder löschen
4. Premium-Upgrade durchführen

---

## Shortcodes

### `[spezialist_listings]`
Zeigt die Spezialisten-Übersicht mit Such- und Filterfunktion an.

**Parameter:**
- `per_page` - Anzahl pro Seite (Standard: 12)
- `category` - Filtere nach Kategorie-Slug
- `location` - Filtere nach Standort-Slug
- `premium` - Nur Premium (1 oder 0)
- `orderby` - Sortierung (date, title, premium)

**Beispiele:**
```
[spezialist_listings per_page="20"]
[spezialist_listings category="steuerberater" location="berlin"]
[spezialist_listings orderby="premium"]
```

### `[spezialist_submit]`
Zeigt das Frontend-Formular zum Einreichen neuer Einträge.

### `[spezialist_dashboard]`
Zeigt das User-Dashboard zur Verwaltung eigener Einträge.

### `[spezialist_detail]`
Zeigt einen einzelnen Spezialist-Eintrag an (optional, standardmäßig auf Single-Seiten).

**Parameter:**
- `id` - Post-ID des Eintrags

---

## Design & Farbschema

Das Plugin verwendet ein professionelles Farbschema:

- **Primärfarbe (Blau):** `#2563EB`
- **Sekundärfarbe (Grün):** `#059669`
- **Grautöne:** `#6B7280`, `#F3F4F6`, etc.

### Anpassungen im Child Theme

Die Datei `wp-content/themes/generatepress-child/style.css` enthält bereits Theme-spezifische Anpassungen.

Weitere Anpassungen können dort vorgenommen werden.

---

## Technische Details

### Dateistruktur
```
spezialist-directory/
├── spezialist-directory.php      # Haupt-Plugin-Datei
├── includes/                      # PHP-Klassen
│   ├── class-cpt-spezialist.php  # Custom Post Type
│   ├── class-taxonomies.php      # Kategorien & Standorte
│   ├── class-meta-boxes.php      # Meta-Felder
│   ├── class-user-submissions.php # Frontend-Formular
│   ├── class-claim-system.php    # Claim-Funktionalität
│   ├── class-user-dashboard.php  # User Dashboard
│   ├── class-stripe-integration.php # Stripe
│   └── class-premium-features.php   # Premium Logik
├── templates/                    # Template-Dateien
│   ├── listing-grid.php
│   ├── listing-detail.php
│   ├── submission-form.php
│   └── user-dashboard.php
├── assets/                       # CSS & JavaScript
│   ├── css/
│   │   ├── frontend-styles.css
│   │   └── admin-styles.css
│   └── js/
│       └── minimal-interactions.js
└── languages/                    # Übersetzungen
```

### Datenbank

Das Plugin erstellt folgende Custom Tables:

- `wp_sd_claims` - Speichert Claim-Anfragen

### Post Meta Keys

Alle Meta-Felder beginnen mit `_sd_`:
- `_sd_phone`, `_sd_email`, `_sd_website`
- `_sd_address`, `_sd_zip`, `_sd_city`
- `_sd_facebook`, `_sd_twitter`, `_sd_instagram`, etc.
- `_sd_is_premium`, `_sd_premium_until`
- `_sd_is_claimed`, `_sd_claimed_by`, `_sd_claimed_date`
- `_sd_stripe_subscription_id`, `_sd_stripe_customer_id`

---

## Fehlerbehebung

### Problem: Shortcodes zeigen nur Text
**Lösung:** Plugin aktivieren und Permalinks neu speichern

### Problem: 404 auf Spezialisten-Seiten
**Lösung:** Einstellungen → Permalinks → Speichern

### Problem: Stripe funktioniert nicht
**Lösung:**
1. Stripe PHP SDK installieren (siehe oben)
2. API Keys prüfen
3. Webhook korrekt konfigurieren

### Problem: Bilder werden nicht hochgeladen
**Lösung:**
1. Upload-Verzeichnis-Rechte prüfen (wp-content/uploads/)
2. PHP Upload-Limit erhöhen (upload_max_filesize in php.ini)

### Problem: E-Mails werden nicht versendet
**Lösung:**
1. SMTP-Plugin installieren (z.B. WP Mail SMTP)
2. E-Mail-Konfiguration prüfen

---

## Performance

Das Plugin ist für Performance optimiert:

✅ **SSR-First** - Minimales JavaScript
✅ **Lazy Loading** - Bilder werden verzögert geladen
✅ **Caching-kompatibel** - Funktioniert mit Cache-Plugins
✅ **Optimierte Queries** - Nur notwendige Daten laden

### Empfohlene Plugins für bessere Performance:
- **WP Rocket** oder **W3 Total Cache** - Caching
- **Imagify** oder **ShortPixel** - Bildoptimierung
- **Autoptimize** - CSS/JS Minifizierung

---

## Sicherheit

Das Plugin implementiert WordPress Best Practices:

✅ Nonce-Validierung für alle Formulare
✅ Capability-Checks für User-Aktionen
✅ SQL Injection Prevention (Prepared Statements)
✅ XSS-Schutz (esc_html, esc_attr, wp_kses)
✅ CSRF-Schutz

---

## Support & Erweiterungen

### Geplante Features (optional):
- Bewertungs-System für Spezialisten
- Favoriten-Funktion
- Email-Benachrichtigungen bei neuen Einträgen
- Import/Export von Einträgen
- Erweiterte Such-Filter (Umkreissuche, etc.)

### Entwickler-Hooks

Das Plugin bietet folgende Hooks für Erweiterungen:

**Actions:**
- `spezialist_directory_init` - Nach Plugin-Initialisierung
- `sd_after_submission` - Nach erfolgreicher Einreichung
- `sd_claim_approved` - Nach Claim-Genehmigung

**Filters:**
- `sd_submission_fields` - Formular-Felder anpassen
- `sd_premium_benefits` - Premium-Vorteile anpassen

---

## Credits

Entwickelt mit ❤️ für Spezialist-für.de

- **WordPress Version:** 6.0+
- **PHP Version:** 7.4+
- **Theme Kompatibilität:** GeneratePress (und die meisten anderen Themes)
- **Design:** Professionelles Blau/Grün/Grau Schema

---

## Changelog

### Version 1.0.0
- Initial Release
- Custom Post Type "Spezialist"
- Frontend Submissions
- Claim-System
- User Dashboard
- Stripe Integration
- Premium Features
- SSR-basierte Listings

---

**Bei Fragen oder Problemen, wenden Sie sich an den Entwickler.**

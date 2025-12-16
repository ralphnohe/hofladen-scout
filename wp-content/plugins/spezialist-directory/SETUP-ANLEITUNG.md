# 🚀 Setup-Anleitung - Spezialist Directory

## ✅ Erfolgreich installierte Komponenten

### 1. Stripe PHP SDK ✓
- **Version:** v19.0.0
- **Installiert via:** Composer
- **Pfad:** `vendor/stripe/stripe-php/`
- **Autoloader:** Wird automatisch in der Haupt-Plugin-Datei geladen

### 2. Taxonomien (Kategorien & Standorte) ✓
- **Setup-Script erstellt:** `setup-taxonomies.php`
- **15 Kategorien vorbereitet**
- **20 Standorte vorbereitet**

---

## 📝 Nächste Schritte

### Schritt 1: Plugin aktivieren

1. Gehen Sie zu **WordPress Admin → Plugins**
2. Aktivieren Sie **"Spezialist Directory"**
3. Permalinks werden automatisch neu generiert

### Schritt 2: Kategorien und Standorte anlegen

**Option A: Via Setup-Script (empfohlen)**

Rufen Sie folgende URL im Browser auf:
```
https://ihre-domain.de/wp-content/plugins/spezialist-directory/setup-taxonomies.php
```

Das Script legt automatisch an:
- ✅ 15 Kategorien (Steuerberater, Rechtsanwalt, Architekt, etc.)
- ✅ 20 Standorte (Berlin, München, Hamburg, etc.)

**Nach dem ersten Ausführen:**
- Script-Datei umbenennen oder löschen
- Oder: Ausführung ist mehrfach möglich (überspringt bereits vorhandene Terms)

**Option B: Manuell anlegen**

1. **Kategorien:** Admin → Spezialisten → Kategorien
2. **Standorte:** Admin → Spezialisten → Standorte

### Schritt 3: WordPress-Seiten erstellen

Erstellen Sie folgende Seiten (**Seiten → Neu hinzufügen**):

#### Seite 1: Spezialisten-Übersicht
```
Titel: Spezialisten finden
Permalink: /spezialisten/
Inhalt: [spezialist_listings]
```

#### Seite 2: Eintrag hinzufügen
```
Titel: Spezialist hinzufügen
Permalink: /spezialist-hinzufugen/
Inhalt: [spezialist_submit]
```

#### Seite 3: User Dashboard
```
Titel: Mein Dashboard
Permalink: /dashboard/
Inhalt: [spezialist_dashboard]
```

### Schritt 4: Nutzer-Registrierung aktivieren

1. **Einstellungen → Allgemein**
2. ✓ **"Jeder kann sich registrieren"**
3. Standard-Benutzerrolle: **Abonnent**
4. Speichern

### Schritt 5: Stripe konfigurieren (für Premium Features)

1. Gehen Sie zu **Spezialisten → Stripe Einstellungen**

2. **Für Entwicklung (Testmodus):**
   - ✓ Testmodus aktivieren
   - Test Publishable Key: `pk_test_...`
   - Test Secret Key: `sk_test_...`

3. **Stripe Dashboard - Produkte erstellen:**
   - Gehen Sie zu: https://dashboard.stripe.com/products
   - Erstellen Sie zwei wiederkehrende Produkte:
     - "Premium Listing Monatlich" (z.B. 10€/Monat)
     - "Premium Listing Jährlich" (z.B. 100€/Jahr)
   - Kopieren Sie die Price IDs (beginnen mit `price_...`)

4. **Price IDs im Plugin eintragen:**
   - Monatliches Abo Price ID
   - Jährliches Abo Price ID

5. **Webhook einrichten:**
   - URL aus Plugin-Einstellungen kopieren
   - In Stripe hinzufügen: Dashboard → Developers → Webhooks
   - Events überwachen:
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`

---

## 📂 Verzeichnisstruktur

```
spezialist-directory/
├── spezialist-directory.php      # ✓ Haupt-Plugin-Datei
├── composer.json                  # ✓ Composer-Konfiguration
├── composer.lock                  # ✓ Dependency Lock-File
├── .gitignore                     # ✓ Git Ignore File
├── README.md                      # ✓ Ausführliche Dokumentation
├── SETUP-ANLEITUNG.md            # ✓ Diese Datei
├── setup-taxonomies.php          # ✓ Taxonomien-Setup-Script
├── includes/                      # ✓ 8 PHP-Klassen
│   ├── class-cpt-spezialist.php
│   ├── class-taxonomies.php
│   ├── class-meta-boxes.php
│   ├── class-user-submissions.php
│   ├── class-claim-system.php
│   ├── class-user-dashboard.php
│   ├── class-stripe-integration.php
│   └── class-premium-features.php
├── templates/                     # ✓ 4 Template-Dateien
│   ├── listing-grid.php
│   ├── listing-detail.php
│   ├── submission-form.php
│   └── user-dashboard.php
├── assets/                        # ✓ CSS & JavaScript
│   ├── css/
│   │   ├── frontend-styles.css
│   │   └── admin-styles.css
│   └── js/
│       └── minimal-interactions.js
├── vendor/                        # ✓ Composer Dependencies
│   ├── autoload.php
│   ├── composer/
│   └── stripe/
│       └── stripe-php/            # ✓ Stripe PHP SDK v19.0.0
└── languages/                     # Bereit für Übersetzungen
```

---

## 🎨 Vorbereitete Kategorien (15)

1. Steuerberater
2. Rechtsanwalt
3. Notar
4. Architekt
5. Ingenieur
6. Unternehmensberater
7. Finanzberater
8. IT-Berater
9. Marketing-Spezialist
10. Immobilienmakler
11. Gutachter
12. Sachverständiger
13. Versicherungsmakler
14. Personalberater
15. Wirtschaftsprüfer

## 📍 Vorbereitete Standorte (20)

1. Berlin
2. Hamburg
3. München
4. Köln
5. Frankfurt am Main
6. Stuttgart
7. Düsseldorf
8. Dortmund
9. Essen
10. Leipzig
11. Bremen
12. Dresden
13. Hannover
14. Nürnberg
15. Duisburg
16. Bochum
17. Wuppertal
18. Bielefeld
19. Bonn
20. Münster

---

## ✅ Checkliste für die Aktivierung

- [ ] Plugin aktiviert
- [ ] Permalinks gespeichert
- [ ] Setup-Script ausgeführt (Kategorien & Standorte angelegt)
- [ ] 3 WordPress-Seiten mit Shortcodes erstellt
- [ ] Nutzer-Registrierung aktiviert
- [ ] (Optional) Stripe konfiguriert

---

## 🔧 Technische Details

### Stripe PHP SDK
- **Version:** 19.0.0
- **Installiert am:** 20. November 2024
- **Autoload:** Wird automatisch beim Plugin-Start geladen
- **Composer Dependencies:** Nur Stripe, keine weiteren Abhängigkeiten

### PHP Requirements
- **Minimum:** PHP 7.4
- **Empfohlen:** PHP 8.0+
- **WordPress:** 6.0+

### Datenbank
- Erstellt Custom Table: `wp_sd_claims`
- Post Meta Keys beginnen alle mit `_sd_`
- Taxonomien: `spezialist_category`, `spezialist_location`

---

## 📞 Support

Bei Fragen oder Problemen konsultieren Sie:
- **Hauptdokumentation:** `README.md`
- **WordPress Admin:** Spezialisten → Alle Einstellungen

---

**Status:** ✅ Alle Komponenten erfolgreich installiert und einsatzbereit!

**Letztes Update:** 20. November 2024

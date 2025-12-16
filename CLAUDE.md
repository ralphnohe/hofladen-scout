# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hofladen-Scout.de is a German professional directory/marketplace WordPress site for farm shops and regional producers. It uses custom plugins, a GeneratePress child theme, and Stripe integration for premium listings.

## Architecture

- **Core System:** 6 custom plugins (spezialist-* prefix)
- **Theme:** GeneratePress child theme with extensive CSS customization
- **Database:** Remote MySQL production + Local MySQL development
- **Build Tools:** Composer for Stripe SDK only, no Node.js pipeline
- **Payment:** Stripe integration for premium subscriptions

## Custom Plugins

| Plugin | Purpose |
|--------|---------|
| `spezialist-directory` | Core listing system (CPT, taxonomies, user submissions, claims, dashboard, Stripe, analytics) |
| `spezialist-seo` | Schema.org JSON-LD, Open Graph, Twitter Cards |
| `spezialist-ratings` | AJAX-based rating & review system |
| `spezialist-screenshots` | Auto-generate featured images from websites |
| `spezialist-url-checker` | URL validation for listings |
| `spezialist-og-screenshots` | Social preview image generation |

## Key Plugin Classes (spezialist-directory)

- `SD_CPT_Spezialist` - Custom Post Type registration
- `SD_Taxonomies` - Categories & Locations
- `SD_Meta_Boxes` - Custom fields (all prefixed `_sd_*`)
- `SD_User_Submissions` - Frontend listing forms
- `SD_Claim_System` - Listing claim management
- `SD_User_Dashboard` - User dashboard UI
- `SD_Stripe_Integration` - Payment processing
- `SD_Premium_Features` - Premium listing logic
- `SD_Ajax_Filter` - Frontend AJAX filtering
- `SD_Login_Register` - Authentication system

## Shortcodes

- `[spezialist_listings]` - Main listing grid with filtering
- `[spezialist_submit]` - Frontend submission form
- `[spezialist_dashboard]` - User dashboard
- `[spezialist_detail]` - Single listing detail view

## Development Standards

- **Pattern:** Singleton classes with `protected static $_instance`
- **Prefixes:** Database tables/options use `sd_`, meta keys use `_sd_*`
- **Security:** WordPress nonces, `esc_*()` functions, prepared SQL statements
- **Structure:** Class-based organization in `/includes/` directories

## Theme Structure

```
wp-content/themes/generatepress-child/
├── functions.php          # Hooks, Google Fonts (DM Sans), Font Awesome 6
├── style.css              # Main styling (extensive, 155KB)
├── page-kontakt.php       # Contact page template
├── page-anmelden.php      # Login/signup template
├── page-merkliste.php     # Watchlist/favorites
└── page-spezialist-hinzufuegen.php  # Add listing page
```

## Database

### Production (Remote)
- **Host:** `localhost:3306` (auf Produktionsserver)
- **Credentials:** In `wp-config.php` (User: `5pHY2RCF8nweny`)

### Local Development
- **Database:** `hofladen_db1`
- **User:** `wpuser`
- **Password:** `wp_secure_pass_2025`
- **Host:** `localhost`
- **Tabellen:** 61 (Import vom 16.12.2025)

### Schema
- **Prefix:** `wp_`
- **Custom Table:** `wp_sd_claims` (listing claim requests)
- **Meta Keys:** All prefixed `_sd_*` (e.g., `_sd_phone`, `_sd_premium_until`)

### DB-Dumps
- **Speicherort:** `db-dumps/`
- **Aktueller Dump:** `Prod-DB_Dec16_2025__XOFjH3Tqa8NoiJ.sql` (~45MB)

## Key Integrations

- **Stripe:** Test & live modes, webhooks, subscription management
- **Supabase:** Remote database integration (use Supabase CLI & MCP)
- **Contact Form 7:** Form ID 7143 on contact page
- **Elementor:** Page building (dequeued on watchlist page)
- **OpenStreetMap:** Geocoding & mapping

## Local Development

```bash
# Start local dev server
php -S localhost:8000 wp-router.php

# MySQL-Befehle für lokale DB
mysql -u wpuser -pwp_secure_pass_2025 hofladen_db1

# SQL-Dump importieren
mysql -u wpuser -pwp_secure_pass_2025 hofladen_db1 < db-dumps/FILENAME.sql

# SQL-Dump exportieren
mysqldump -u wpuser -pwp_secure_pass_2025 hofladen_db1 > db-dumps/Local-DB_$(date +%b%d_%Y).sql
```

**Wichtig:** Für lokale Entwicklung muss `wp-config.php` angepasst werden:
- `DB_NAME` auf `hofladen_db1`
- `DB_USER` auf `wpuser`
- `DB_PASSWORD` auf `wp_secure_pass_2025`

## Important Notes

- Production + Local Development möglich
- All UI strings in German
- User registration enabled for directory submissions
- Premium listings via Stripe subscription model
- LiteSpeed Cache enabled for performance
- die production url lautet: https://www.hofladen-scout.de
- es gibt lokal keine wp-admin seite, das admin-login erfolgt über /einloggen
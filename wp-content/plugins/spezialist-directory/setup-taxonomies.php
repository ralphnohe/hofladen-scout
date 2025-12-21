<?php
/**
 * Setup Script: Taxonomies
 *
 * Dieses Script legt Beispiel-Kategorien und -Standorte an.
 * WICHTIG: Nur einmalig ausführen!
 *
 * Ausführung: Rufen Sie diese Datei direkt im Browser auf:
 * https://ihre-domain.de/wp-content/plugins/spezialist-directory/setup-taxonomies.php
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Load WordPress
require_once('../../../wp-load.php');

// Sicherheitscheck: Nur Administratoren dürfen dieses Script ausführen
if (!current_user_can('manage_options')) {
    die('Keine Berechtigung. Bitte als Administrator anmelden.');
}

// Kategorien definieren
$categories = array(
    'Steuerberater',
    'Rechtsanwalt',
    'Notar',
    'Architekt',
    'Ingenieur',
    'Unternehmensberater',
    'Finanzberater',
    'IT-Berater',
    'Marketing-Spezialist',
    'Immobilienmakler',
    'Gutachter',
    'Sachverständiger',
    'Versicherungsmakler',
    'Personalberater',
    'Wirtschaftsprüfer',
);

// Standorte definieren
$locations = array(
    'Berlin',
    'Hamburg',
    'München',
    'Köln',
    'Frankfurt am Main',
    'Stuttgart',
    'Düsseldorf',
    'Dortmund',
    'Essen',
    'Leipzig',
    'Bremen',
    'Dresden',
    'Hannover',
    'Nürnberg',
    'Duisburg',
    'Bochum',
    'Wuppertal',
    'Bielefeld',
    'Bonn',
    'Münster',
);

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxonomien Setup - Spezialist Directory</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563EB;
            margin-top: 0;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #2563EB;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2563EB;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1E40AF;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Taxonomien Setup</h1>
        <p>Kategorien und Standorte für das Spezialist Directory werden angelegt...</p>
';

$categories_created = 0;
$categories_skipped = 0;
$locations_created = 0;
$locations_skipped = 0;

// Kategorien anlegen
echo '<h2>📁 Kategorien</h2>';
foreach ($categories as $category) {
    // Prüfen ob Kategorie bereits existiert
    $term = term_exists($category, 'spezialist_category');

    if ($term) {
        echo '<div class="info">ℹ️ Kategorie bereits vorhanden: ' . esc_html($category) . '</div>';
        $categories_skipped++;
    } else {
        $result = wp_insert_term(
            $category,
            'spezialist_category',
            array(
                'description' => 'Spezialisten im Bereich ' . $category,
                'slug'        => sanitize_title($category),
            )
        );

        if (is_wp_error($result)) {
            echo '<div class="error">❌ Fehler beim Anlegen: ' . esc_html($category) . ' - ' . $result->get_error_message() . '</div>';
        } else {
            echo '<div class="success">✅ Kategorie angelegt: ' . esc_html($category) . '</div>';
            $categories_created++;
        }
    }
}

// Standorte anlegen
echo '<h2>📍 Standorte</h2>';
foreach ($locations as $location) {
    // Prüfen ob Standort bereits existiert
    $term = term_exists($location, 'spezialist_location');

    if ($term) {
        echo '<div class="info">ℹ️ Standort bereits vorhanden: ' . esc_html($location) . '</div>';
        $locations_skipped++;
    } else {
        $result = wp_insert_term(
            $location,
            'spezialist_location',
            array(
                'description' => 'Spezialisten in ' . $location,
                'slug'        => sanitize_title($location),
            )
        );

        if (is_wp_error($result)) {
            echo '<div class="error">❌ Fehler beim Anlegen: ' . esc_html($location) . ' - ' . $result->get_error_message() . '</div>';
        } else {
            echo '<div class="success">✅ Standort angelegt: ' . esc_html($location) . '</div>';
            $locations_created++;
        }
    }
}

// Statistik anzeigen
echo '
        <h2>📊 Zusammenfassung</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number">' . $categories_created . '</div>
                <div class="stat-label">Kategorien angelegt</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">' . $locations_created . '</div>
                <div class="stat-label">Standorte angelegt</div>
            </div>
        </div>

        <div class="info" style="margin-top: 20px;">
            <strong>Hinweis:</strong> ' . $categories_skipped . ' Kategorien und ' . $locations_skipped . ' Standorte waren bereits vorhanden und wurden übersprungen.
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="' . admin_url('edit-tags.php?taxonomy=spezialist_category&post_type=hofladen') . '" class="btn">Kategorien anzeigen</a>
            <a href="' . admin_url('edit-tags.php?taxonomy=spezialist_location&post_type=hofladen') . '" class="btn">Standorte anzeigen</a>
            <a href="' . admin_url('edit.php?post_type=hofladen') . '" class="btn">Zu den Spezialisten</a>
        </div>

        <div class="success" style="margin-top: 30px;">
            <strong>✅ Setup abgeschlossen!</strong><br>
            Sie können jetzt Spezialist-Einträge mit diesen Kategorien und Standorten erstellen.
        </div>

        <div class="info" style="margin-top: 20px;">
            <strong>Wichtig:</strong> Sie können dieses Setup-Script jetzt löschen oder die Datei umbenennen, damit sie nicht mehr ausgeführt werden kann.
        </div>
    </div>
</body>
</html>';

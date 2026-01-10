<?php
/**
 * Geocode Missing Coordinates
 *
 * Run this script via WP-CLI or browser to geocode listings without coordinates.
 * Usage: wp eval-file wp-content/plugins/spezialist-directory/tools/geocode-missing.php
 * Or via browser: /wp-content/plugins/spezialist-directory/tools/geocode-missing.php?run=1&secret=YOUR_SECRET
 *
 * @package Spezialist_Directory
 */

// Define secret key for browser access (change this!)
define( 'GEOCODE_SECRET', 'hofladen_geocode_2025' );

// Check if running via WP-CLI
$is_cli = defined( 'WP_CLI' ) && WP_CLI;

// If not CLI, load WordPress and check secret
if ( ! $is_cli ) {
    // Load WordPress
    $wp_load_paths = array(
        dirname( __FILE__ ) . '/../../../../wp-load.php',
        dirname( __FILE__ ) . '/../../../../../wp-load.php',
    );

    $loaded = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            $loaded = true;
            break;
        }
    }

    if ( ! $loaded ) {
        die( 'Could not load WordPress.' );
    }

    // Security check
    if ( ! isset( $_GET['run'] ) || ! isset( $_GET['secret'] ) || $_GET['secret'] !== GEOCODE_SECRET ) {
        die( 'Access denied. Use: ?run=1&secret=YOUR_SECRET' );
    }

    header( 'Content-Type: text/plain; charset=utf-8' );
}

/**
 * Geocode address using Nominatim API
 */
function geocode_address_nominatim( $address ) {
    if ( empty( $address ) ) {
        return false;
    }

    $url = add_query_arg( array(
        'q'      => urlencode( $address ),
        'format' => 'json',
        'limit'  => 1,
    ), 'https://nominatim.openstreetmap.org/search' );

    $response = wp_remote_get( $url, array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'Hofladen-Scout.de/1.0 (https://www.hofladen-scout.de)',
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( empty( $data ) || ! isset( $data[0]['lat'] ) || ! isset( $data[0]['lon'] ) ) {
        return false;
    }

    return array(
        'lat' => floatval( $data[0]['lat'] ),
        'lng' => floatval( $data[0]['lon'] ),
    );
}

/**
 * Output message
 */
function output_msg( $msg ) {
    echo $msg . "\n";
    if ( ! defined( 'WP_CLI' ) ) {
        flush();
        ob_flush();
    }
}

// Find listings without coordinates
global $wpdb;

$query = "
    SELECT p.ID, p.post_title,
           addr.meta_value as address,
           zip.meta_value as zip,
           city.meta_value as city,
           lat.meta_value as latitude,
           lng.meta_value as longitude
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} addr ON p.ID = addr.post_id AND addr.meta_key = '_sd_address'
    LEFT JOIN {$wpdb->postmeta} zip ON p.ID = zip.post_id AND zip.meta_key = '_sd_zip'
    LEFT JOIN {$wpdb->postmeta} city ON p.ID = city.post_id AND city.meta_key = '_sd_city'
    LEFT JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = '_sd_latitude'
    LEFT JOIN {$wpdb->postmeta} lng ON p.ID = lng.post_id AND lng.meta_key = '_sd_longitude'
    WHERE p.post_type = 'hofladen'
    AND p.post_status IN ('publish', 'pending')
    AND (lat.meta_value IS NULL OR lat.meta_value = '' OR lng.meta_value IS NULL OR lng.meta_value = '')
";

$listings = $wpdb->get_results( $query );

output_msg( '=== Geocode Missing Coordinates ===' );
output_msg( 'Found ' . count( $listings ) . ' listings without coordinates.' );
output_msg( '' );

if ( empty( $listings ) ) {
    output_msg( 'Nothing to do!' );
    exit;
}

$success = 0;
$failed = 0;

foreach ( $listings as $listing ) {
    $address = trim( $listing->address );
    $zip = trim( $listing->zip );
    $city = trim( $listing->city );

    if ( empty( $address ) || empty( $zip ) || empty( $city ) ) {
        output_msg( "[SKIP] ID {$listing->ID}: Missing address data" );
        $failed++;
        continue;
    }

    $full_address = sprintf( '%s, %s %s, Deutschland', $address, $zip, $city );
    output_msg( "[GEOCODING] ID {$listing->ID} ({$listing->post_title}): {$full_address}" );

    $coords = geocode_address_nominatim( $full_address );

    if ( $coords ) {
        update_post_meta( $listing->ID, '_sd_latitude', $coords['lat'] );
        update_post_meta( $listing->ID, '_sd_longitude', $coords['lng'] );
        output_msg( "  -> SUCCESS: {$coords['lat']}, {$coords['lng']}" );
        $success++;
    } else {
        output_msg( "  -> FAILED: Could not geocode address" );
        $failed++;
    }

    // Respect Nominatim rate limit (1 request per second)
    sleep( 1 );
}

output_msg( '' );
output_msg( '=== Summary ===' );
output_msg( "Success: {$success}" );
output_msg( "Failed: {$failed}" );
output_msg( 'Done!' );

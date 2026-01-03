<?php
/**
 * Sync all existing posts' _sd_neighborhood from their spezialist_location terms
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/spezialist-directory/sync-neighborhood.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Allow running from command line
    require_once dirname( __FILE__ ) . '/../../../wp-load.php';
}

// Check if running via WP-CLI
$is_cli = defined( 'WP_CLI' ) && WP_CLI;

function sd_log( $message, $is_cli ) {
    if ( $is_cli ) {
        WP_CLI::log( $message );
    } else {
        echo $message . "\n";
    }
}

function sd_success( $message, $is_cli ) {
    if ( $is_cli ) {
        WP_CLI::success( $message );
    } else {
        echo "SUCCESS: " . $message . "\n";
    }
}

$args = array(
    'post_type'      => 'hofladen',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
);

$post_ids = get_posts( $args );
$updated  = 0;
$skipped  = 0;
$no_location = 0;

sd_log( "Starting migration for " . count( $post_ids ) . " posts...", $is_cli );

foreach ( $post_ids as $post_id ) {
    $locations = wp_get_object_terms( $post_id, 'spezialist_location', array( 'fields' => 'all' ) );

    if ( empty( $locations ) || is_wp_error( $locations ) ) {
        $no_location++;
        continue;
    }

    $first_location       = $locations[0];
    $current_neighborhood = get_post_meta( $post_id, '_sd_neighborhood', true );

    // Nur aktualisieren wenn Wert anders ist
    if ( $current_neighborhood !== $first_location->name ) {
        update_post_meta( $post_id, '_sd_neighborhood', $first_location->name );
        $updated++;
        sd_log( "Updated post {$post_id}: '{$current_neighborhood}' -> '{$first_location->name}'", $is_cli );
    } else {
        $skipped++;
    }
}

sd_success( "Migration complete. Updated: {$updated}, Skipped (already correct): {$skipped}, No location: {$no_location}", $is_cli );

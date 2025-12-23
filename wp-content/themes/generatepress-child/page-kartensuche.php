<?php
/**
 * Template Name: Kartensuche
 * Template for the Map Search page - Full-screen map-first experience
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch Bundeslaender for dropdown (same as frontpage)
$all_categories = get_terms( array(
    'taxonomy'   => 'spezialist_category',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );

// Get URL parameters
$current_search = isset( $_GET['sd_search'] ) ? sanitize_text_field( $_GET['sd_search'] ) : '';
$current_category = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
$current_premium = isset( $_GET['sd_premium'] ) && '1' === $_GET['sd_premium'];

get_header();
?>

<div class="sd-kartensuche-wrapper">
    <!-- Floating Search Header -->
    <header class="sd-kartensuche-header">
        <div class="sd-kartensuche-header-row">
            <form id="sd-kartensuche-form" class="sd-kartensuche-search" method="get" action="">
                <!-- Search Input -->
                <div class="sd-kartensuche-input-wrapper">
                    <svg class="sd-kartensuche-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                    </svg>
                    <input
                        type="text"
                        id="sd-kartensuche-search"
                        name="sd_search"
                        placeholder="<?php esc_attr_e( 'Ort, Hofladen, Produkt...', 'spezialist-directory' ); ?>"
                        value="<?php echo esc_attr( $current_search ); ?>"
                        autocomplete="off"
                    >
                    <button type="button" class="sd-kartensuche-clear" aria-label="<?php esc_attr_e( 'Suche löschen', 'spezialist-directory' ); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>

                <!-- Submit Button (icon only) -->
                <button type="submit" class="sd-kartensuche-submit" aria-label="<?php esc_attr_e( 'Suchen', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Results Count - Centered -->
        <div class="sd-kartensuche-stats">
            <span id="sd-kartensuche-count">0</span> <?php _e( 'Hofläden', 'spezialist-directory' ); ?>
        </div>
    </header>

    <!-- Full-Screen Map Container -->
    <main class="sd-kartensuche-map-container" id="sd-kartensuche-map-container">
        <div id="sd-kartensuche-map" class="sd-kartensuche-map"></div>

        <!-- Loading Overlay -->
        <div class="sd-kartensuche-loading" id="sd-kartensuche-loading">
            <div class="sd-kartensuche-spinner"></div>
            <span><?php _e( 'Hofläden werden geladen...', 'spezialist-directory' ); ?></span>
        </div>

        <!-- Custom Zoom Controls -->
        <div class="sd-kartensuche-controls">
            <button type="button" class="sd-kartensuche-control" id="sd-karte-zoom-in" title="<?php esc_attr_e( 'Vergrößern', 'spezialist-directory' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="#222"/>
                </svg>
            </button>
            <button type="button" class="sd-kartensuche-control" id="sd-karte-zoom-out" title="<?php esc_attr_e( 'Verkleinern', 'spezialist-directory' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 13H5v-2h14v2z" fill="#222"/>
                </svg>
            </button>
            <button type="button" class="sd-kartensuche-control" id="sd-karte-fit-all" title="<?php esc_attr_e( 'Alle anzeigen', 'spezialist-directory' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 3l2.3 2.3-2.89 2.87 1.42 1.42L18.7 6.7 21 9V3h-6zM3 9l2.3-2.3 2.87 2.89 1.42-1.42L6.7 5.3 9 3H3v6zm6 12l-2.3-2.3 2.89-2.87-1.42-1.42L5.3 17.3 3 15v6h6zm12-6l-2.3 2.3-2.87-2.89-1.42 1.42 2.89 2.87L15 21h6v-6z" fill="#222"/>
                </svg>
            </button>
            <button type="button" class="sd-kartensuche-control" id="sd-karte-location" title="<?php esc_attr_e( 'Mein Standort', 'spezialist-directory' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z" fill="#222"/>
                </svg>
            </button>
        </div>

        <!-- Floating List Toggle Button -->
        <button type="button" class="sd-kartensuche-list-toggle" id="sd-list-toggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" fill="currentColor"/>
            </svg>
            <span class="sd-toggle-text"><?php _e( 'Listenansicht', 'spezialist-directory' ); ?></span>
            <span class="sd-toggle-count" id="sd-toggle-count">0</span>
        </button>
    </main>

    <!-- Sliding List Panel -->
    <aside class="sd-kartensuche-list-panel" id="sd-list-panel" aria-hidden="true">
        <div class="sd-kartensuche-list-header">
            <div class="sd-list-drag-handle"></div>
            <h2>
                <span id="sd-list-count">0</span> <?php _e( 'Hofläden gefunden', 'spezialist-directory' ); ?>
            </h2>
            <button type="button" id="sd-back-to-map" class="sd-kartensuche-back-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z" fill="currentColor"/>
                </svg>
                <span><?php _e( 'Zur Karte', 'spezialist-directory' ); ?></span>
            </button>
        </div>
        <div class="sd-kartensuche-list-content" id="sd-list-content">
            <!-- Listings loaded via AJAX -->
            <div class="sd-kartensuche-list-loading">
                <div class="sd-kartensuche-spinner"></div>
            </div>
        </div>
    </aside>
</div>

<?php get_footer(); ?>

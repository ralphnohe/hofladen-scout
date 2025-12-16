<?php
/**
 * Template: Favorites Page (Merkliste)
 *
 * Displays user's favorited listings stored in localStorage
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="sd-favorites-page">
    <div class="sd-favorites-header">
        <button type="button" class="sd-clear-favorites">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
            </svg>
            <?php _e( 'Alle entfernen', 'spezialist-directory' ); ?>
        </button>
    </div>

    <div class="sd-favorites-empty" style="display: none;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2zm0 15l-5-2.18L7 18V5h10v13z" fill="currentColor"/>
        </svg>
        <h3><?php _e( 'Deine Merkliste ist leer', 'spezialist-directory' ); ?></h3>
        <p><?php _e( 'Füge Spezialisten zu deiner Merkliste hinzu, um sie später wiederzufinden.', 'spezialist-directory' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sd-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
            </svg>
            <?php _e( 'Spezialisten entdecken', 'spezialist-directory' ); ?>
        </a>
    </div>

    <div class="sd-favorites-list">
        <!-- Content loaded via JavaScript -->
    </div>
</div>

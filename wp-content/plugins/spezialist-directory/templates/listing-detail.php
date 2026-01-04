<?php
/**
 * Template: Listing Detail
 *
 * Displays single specialist listing details
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = get_the_ID();
$is_premium = SD_Premium_Features::is_premium( $post_id );
$is_verified = SD_Premium_Features::is_verified( $post_id );
$is_claimed = get_post_meta( $post_id, '_sd_is_claimed', true );

// Meta data
$phone = get_post_meta( $post_id, '_sd_phone', true );
$email = get_post_meta( $post_id, '_sd_email', true );
$website = get_post_meta( $post_id, '_sd_website', true );
$address = get_post_meta( $post_id, '_sd_address', true );
$zip = get_post_meta( $post_id, '_sd_zip', true );
$city = get_post_meta( $post_id, '_sd_city', true );

// Social media
$facebook = get_post_meta( $post_id, '_sd_facebook', true );
$twitter = get_post_meta( $post_id, '_sd_twitter', true );
$instagram = get_post_meta( $post_id, '_sd_instagram', true );
$linkedin = get_post_meta( $post_id, '_sd_linkedin', true );
$youtube = get_post_meta( $post_id, '_sd_youtube', true );
$xing = get_post_meta( $post_id, '_sd_xing', true );

// Taxonomies
$categories = wp_get_object_terms( $post_id, 'spezialist_category' );
$locations = wp_get_object_terms( $post_id, 'spezialist_location' );
?>

<?php
// Get thumbnail URL for blurred backdrop
$thumbnail_url = '';
if ( has_post_thumbnail() ) {
    $thumbnail_url = get_the_post_thumbnail_url( $post_id, 'large' );
} else {
    $thumbnail_url = home_url( '/wp-content/uploads/2025/12/placeholder.webp' );
}

// Build full address for directions
$full_address_parts = array_filter( array( $address, $zip, $city ) );
$full_address = implode( ', ', $full_address_parts );
$directions_url = $full_address ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode( $full_address ) : '';

// Generate SEO-optimized alt text for images
$primary_category = ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '';
$seo_alt_text = sprintf(
    '%s - %s%s',
    get_the_title(),
    $primary_category ? $primary_category . ' in ' : '',
    $city ?: ''
);
?>

<?php
// Breadcrumbs (visual navigation)
if ( class_exists( 'SDSEO_Breadcrumbs' ) ) {
    SDSEO_Breadcrumbs::render();
}
?>

<div class="sd-listing-detail <?php echo $is_premium ? 'sd-detail-premium' : ''; ?>">
    <!-- Hero Section with Blurred Backdrop -->
    <div class="sd-detail-hero" style="--hero-bg: url('<?php echo esc_url( $thumbnail_url ); ?>');">
        <div class="sd-detail-hero-backdrop"></div>
        <div class="sd-detail-hero-content">
            <div class="sd-detail-hero-image">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium_large', array(
                        'class' => 'sd-hero-profile-img',
                        'alt'   => esc_attr( $seo_alt_text ),
                    ) ); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url( home_url( '/wp-content/uploads/2025/12/placeholder.webp' ) ); ?>"
                         alt="<?php echo esc_attr( $seo_alt_text ); ?>"
                         class="sd-hero-profile-img" />
                <?php endif; ?>
                <?php if ( $is_premium ) : ?>
                    <div class="sd-hero-premium-badge">
                        <?php echo SD_Premium_Features::get_premium_badge( $post_id ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sd-detail-hero-info">
                <h1 class="sd-detail-hero-title">
                    <?php the_title(); ?>
                    <?php if ( $is_verified ) : ?>
                        <?php echo SD_Premium_Features::get_verified_badge( $post_id ); ?>
                    <?php endif; ?>
                </h1>

                <div class="sd-detail-hero-meta">
                    <?php if ( ! empty( $categories ) ) : ?>
                        <span class="sd-hero-category"><?php echo esc_html( $categories[0]->name ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $locations ) || $city ) : ?>
                        <span class="sd-hero-location">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                            </svg>
                            <?php
                            if ( ! empty( $locations ) ) {
                                echo esc_html( $locations[0]->name );
                            } elseif ( $city ) {
                                echo esc_html( $city );
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php
                    /**
                     * Hook: sd_detail_hero_rating
                     * Used by spezialist-ratings plugin to display rating in hero meta (last element)
                     *
                     * @param int $post_id The listing post ID
                     */
                    do_action( 'sd_detail_hero_rating', $post_id );
                    ?>
                </div>

                <!-- Hero CTA Buttons -->
                <div class="sd-hero-actions">
                    <?php if ( $phone ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" class="sd-hero-cta sd-hero-cta-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="currentColor"/>
                            </svg>
                            <?php _e( 'Anrufen', 'spezialist-directory' ); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( $website ) : ?>
                        <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" class="sd-hero-cta">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                            </svg>
                            <?php _e( 'Website', 'spezialist-directory' ); ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #f1c232;">
                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" fill="currentColor"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php if ( $directions_url ) : ?>
                        <a href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer" class="sd-hero-cta">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z" fill="currentColor"/>
                            </svg>
                            <?php _e( 'Route', 'spezialist-directory' ); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="sd-hero-cta sd-share-btn" onclick="navigator.share ? navigator.share({title: '<?php echo esc_js( get_the_title() ); ?>', url: '<?php echo esc_js( get_permalink() ); ?>'}) : navigator.clipboard.writeText('<?php echo esc_js( get_permalink() ); ?>').then(() => alert('Link kopiert!'))">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z" fill="currentColor"/>
                        </svg>
                        <?php _e( 'Teilen', 'spezialist-directory' ); ?>
                    </button>
                    <button type="button"
                            class="sd-hero-cta sd-bookmark-btn"
                            data-post-id="<?php echo esc_attr( $post_id ); ?>"
                            title="<?php esc_attr_e( 'Zur Merkliste hinzufügen', 'spezialist-directory' ); ?>">
                        <svg class="sd-bookmark-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2zm0 15l-5-2.18L7 18V5h10v13z" fill="currentColor"/>
                        </svg>
                        <svg class="sd-bookmark-icon-filled" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" fill="currentColor"/>
                        </svg>
                        <span class="sd-bookmark-text"><?php _e( 'Merken', 'spezialist-directory' ); ?></span>
                    </button>
                    <?php if ( $email ) : ?>
                        <button type="button" class="sd-hero-cta sd-hero-cta-quote sd-quote-request-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z" fill="currentColor"/>
                                <path d="M7 9h10v2H7zm0-3h10v2H7z" fill="currentColor"/>
                            </svg>
                            <?php _e( 'Anfrage senden', 'spezialist-directory' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Check if gallery images exist (Premium feature only)
    $gallery_images = get_post_meta( $post_id, '_sd_gallery_images', true );
    $has_gallery = $is_premium && is_array( $gallery_images ) && ! empty( $gallery_images );

    // Check if video exists (Premium feature only)
    $video_id = get_post_meta( $post_id, '_sd_video', true );
    $has_video = $is_premium && ! empty( $video_id );
    $video_url = $has_video ? wp_get_attachment_url( $video_id ) : '';
    ?>

    <!-- Tab Navigation (Sticky) -->
    <nav class="sd-detail-tabs">
        <div class="sd-tabs-container">
            <button type="button" class="sd-tab active" data-tab="ueber"><?php _e( 'Beschreibung', 'spezialist-directory' ); ?></button>
            <?php if ( $has_gallery ) : ?>
                <button type="button" class="sd-tab" data-tab="galerie"><?php _e( 'Galerie', 'spezialist-directory' ); ?></button>
            <?php endif; ?>
            <?php if ( $has_video ) : ?>
                <button type="button" class="sd-tab" data-tab="video"><?php _e( 'Video', 'spezialist-directory' ); ?></button>
            <?php endif; ?>
            <?php if ( $phone || $email || $website ) : ?>
                <button type="button" class="sd-tab" data-tab="kontakt"><?php _e( 'Kontakt', 'spezialist-directory' ); ?></button>
            <?php endif; ?>
            <?php if ( $address || $city ) : ?>
                <button type="button" class="sd-tab" data-tab="standort"><?php _e( 'Standort', 'spezialist-directory' ); ?></button>
            <?php endif; ?>
            <?php
            /**
             * Hook: sd_detail_tabs_after
             * Used by spezialist-ratings plugin to add Bewertungen tab button
             *
             * @param int $post_id The listing post ID
             */
            do_action( 'sd_detail_tabs_after', $post_id );
            ?>
        </div>
    </nav>

    <div class="sd-detail-body">
        <div class="sd-detail-main">
            <!-- Tab Panel: Beschreibung -->
            <div class="sd-tab-content active" data-tab="ueber">
                <div class="sd-detail-section">
                    <h2><?php _e( 'Beschreibung', 'spezialist-directory' ); ?></h2>
                    <div class="sd-detail-content">
                        <?php the_content(); ?>
                    </div>
                </div>

                <?php
                // Services/Offerings Display
                $services = get_post_meta( $post_id, '_sd_services', true );
                if ( is_array( $services ) && ! empty( $services ) ) :
                ?>
                    <div class="sd-detail-section sd-services-section">
                        <h2><?php _e( 'Angebotene Leistungen', 'spezialist-directory' ); ?></h2>
                        <ul class="sd-services-list">
                            <?php foreach ( $services as $service ) : ?>
                                <li class="sd-service-item">
                                    <svg class="sd-service-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                                    </svg>
                                    <?php echo esc_html( $service ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Panel: Galerie -->
            <?php if ( $has_gallery ) : ?>
                <div class="sd-tab-content" data-tab="galerie">
                    <div class="sd-detail-section">
                        <h2><?php _e( 'Bildergalerie', 'spezialist-directory' ); ?></h2>
                        <div class="sd-gallery-grid">
                            <?php foreach ( $gallery_images as $image_id ) :
                                $image_url = wp_get_attachment_image_url( $image_id, 'large' );
                                $image_full = wp_get_attachment_image_url( $image_id, 'full' );
                                $image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
                                if ( ! $image_url ) continue;
                            ?>
                                <a href="<?php echo esc_url( $image_full ); ?>"
                                   class="sd-gallery-item"
                                   data-lightbox="gallery"
                                   data-alt="<?php echo esc_attr( $image_alt ); ?>">
                                    <img src="<?php echo esc_url( $image_url ); ?>"
                                         alt="<?php echo esc_attr( $image_alt ?: get_the_title() ); ?>"
                                         loading="lazy" />
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab Panel: Video -->
            <?php if ( $has_video ) : ?>
                <div class="sd-tab-content" data-tab="video">
                    <div class="sd-detail-section">
                        <h2><?php _e( 'Video', 'spezialist-directory' ); ?></h2>
                        <div class="sd-video-container">
                            <video controls preload="metadata" class="sd-listing-video">
                                <source src="<?php echo esc_url( $video_url ); ?>" type="<?php echo esc_attr( get_post_mime_type( $video_id ) ); ?>">
                                <?php _e( 'Dein Browser unterstützt das Video-Element nicht.', 'spezialist-directory' ); ?>
                            </video>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab Panel: Kontakt -->
            <?php if ( $phone || $email || $website ) : ?>
                <div class="sd-tab-content" data-tab="kontakt">
                    <div class="sd-detail-section">
                        <h2><?php _e( 'Kontakt', 'spezialist-directory' ); ?></h2>
                        <div class="sd-contact-info-panel">
                            <?php if ( $phone ) : ?>
                                <div class="sd-contact-item">
                                    <div class="sd-contact-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <div class="sd-contact-details">
                                        <span class="sd-contact-label"><?php _e( 'Telefon', 'spezialist-directory' ); ?></span>
                                        <a href="tel:<?php echo esc_attr( $phone ); ?>" class="sd-contact-value"><?php echo esc_html( $phone ); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ( $email ) : ?>
                                <div class="sd-contact-item">
                                    <div class="sd-contact-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <div class="sd-contact-details">
                                        <span class="sd-contact-label"><?php _e( 'E-Mail', 'spezialist-directory' ); ?></span>
                                        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="sd-contact-value"><?php echo esc_html( $email ); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ( $website ) : ?>
                                <div class="sd-contact-item">
                                    <div class="sd-contact-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <div class="sd-contact-details">
                                        <span class="sd-contact-label"><?php _e( 'Website', 'spezialist-directory' ); ?></span>
                                        <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener" class="sd-contact-value">
                                            <?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?>
                                            <svg class="sd-contact-external-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" fill="currentColor"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Business Hours Display
                        if ( class_exists( 'SD_Business_Hours' ) && SD_Business_Hours::has_hours( $post_id ) ) :
                        ?>
                            <div class="sd-business-hours-section">
                                <h3><?php _e( 'Öffnungszeiten', 'spezialist-directory' ); ?></h3>
                                <?php echo SD_Business_Hours::render_status_badge( $post_id ); ?>
                                <?php echo SD_Business_Hours::render_hours_table( $post_id ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tab Panel: Standort (Location + Map) -->
            <?php if ( $address || $zip || $city ) : ?>
                <?php
                // Build full address for geocoding
                $full_address_parts = array_filter( array( $address, $zip, $city, 'Deutschland' ) );
                $full_address = implode( ', ', $full_address_parts );
                ?>
                <div class="sd-tab-content" data-tab="standort">
                    <div class="sd-detail-section">
                        <h2><?php _e( 'Standort', 'spezialist-directory' ); ?></h2>
                        <div class="sd-detail-address">
                            <?php if ( $address ) : ?>
                                <p><?php echo esc_html( $address ); ?></p>
                            <?php endif; ?>
                            <?php if ( $zip || $city ) : ?>
                                <p><?php echo esc_html( $zip . ' ' . $city ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="sd-map-container">
                            <div id="sd-map" data-address="<?php echo esc_attr( $full_address ); ?>"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            /**
             * Hook: sd_detail_tab_content_after
             * Used by spezialist-ratings plugin to add Bewertungen tab content
             *
             * @param int $post_id The listing post ID
             */
            do_action( 'sd_detail_tab_content_after', $post_id );
            ?>
        </div>

        <div class="sd-detail-sidebar">

            <?php if ( $facebook || $twitter || $instagram || $linkedin || $youtube || $xing ) : ?>
                <div class="sd-detail-sidebar-box sd-social-box">
                    <h3><?php _e( 'Social Media', 'spezialist-directory' ); ?></h3>
                    <div class="sd-social-links">
                        <?php if ( $facebook ) : ?>
                            <a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="Facebook">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $twitter ) : ?>
                            <a href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="X">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $instagram ) : ?>
                            <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="Instagram">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $linkedin ) : ?>
                            <a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="LinkedIn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $youtube ) : ?>
                            <a href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="YouTube">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $xing ) : ?>
                            <a href="<?php echo esc_url( $xing ); ?>" target="_blank" rel="noopener" class="sd-social-link" title="XING">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Claim Box Logic - 4 different states
            if ( ! $is_claimed ) :
                $current_user_id = get_current_user_id();
                $has_pending_claim = false;

                if ( $current_user_id ) {
                    $has_pending_claim = SD_Claim_System::instance()->user_has_pending_claim( $post_id, $current_user_id );
                }

                // Check if we should show the claim form (user just logged in with claim=1 param)
                $show_claim_form = is_user_logged_in() && isset( $_GET['claim'] ) && $_GET['claim'] == '1';
            ?>
                <div class="sd-detail-sidebar-box sd-claim-box">
                    <?php if ( ! is_user_logged_in() ) : ?>
                        <!-- State 1: User not logged in - Show info and redirect button -->
                        <h3><?php _e( 'Ist dies Dein Eintrag?', 'spezialist-directory' ); ?></h3>
                        <p><?php _e( 'Beanspruche diesen Eintrag, um ihn zu verwalten.', 'spezialist-directory' ); ?></p>
                        <?php
                        // Build redirect URL back to this page with claim=1 parameter
                        $return_url = add_query_arg( 'claim', '1', get_permalink( $post_id ) );
                        $register_url = add_query_arg( 'redirect_to', urlencode( $return_url ), sd_get_page_url( 'anmelden/?action=register' ) );
                        ?>
                        <a href="<?php echo esc_url( $register_url ); ?>" class="sd-button sd-button-primary">
                            <?php _e( 'Eintrag beanspruchen', 'spezialist-directory' ); ?>
                        </a>

                    <?php elseif ( $has_pending_claim ) : ?>
                        <!-- State 2: User has pending claim - Show status message -->
                        <h3><?php _e( 'Anfrage eingereicht', 'spezialist-directory' ); ?></h3>
                        <div class="sd-claim-pending-notice">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#f59e0b"/>
                            </svg>
                            <p><?php _e( 'Deine Anfrage wird derzeit bearbeitet. Du erhältst eine E-Mail, sobald wir Deine Anfrage überprüft haben.', 'spezialist-directory' ); ?></p>
                        </div>

                    <?php elseif ( $show_claim_form ) : ?>
                        <!-- State 3: Show claim form (user just logged in with claim param) -->
                        <h3><?php _e( 'Eintrag beanspruchen', 'spezialist-directory' ); ?></h3>
                        <p><?php _e( 'Bitte erkläre kurz, warum Du diesen Eintrag beanspruchen möchtest.', 'spezialist-directory' ); ?></p>
                        <form id="sd-claim-form" class="sd-claim-form">
                            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                            <?php wp_nonce_field( 'sd_claim_listing', 'sd_claim_nonce' ); ?>
                            <div class="sd-form-group">
                                <label for="sd-claim-reason"><?php _e( 'Begründung', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                                <textarea id="sd-claim-reason" name="message" rows="4" required placeholder="<?php esc_attr_e( 'z.B. Ich bin der Inhaber dieses Unternehmens...', 'spezialist-directory' ); ?>"></textarea>
                            </div>
                            <button type="submit" class="sd-button sd-button-primary sd-claim-submit-btn">
                                <span class="sd-btn-text"><?php _e( 'Anfrage absenden', 'spezialist-directory' ); ?></span>
                                <span class="sd-btn-loading" style="display: none;">
                                    <svg class="sd-spinner" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" transform="rotate(-90 12 12)">
                                            <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                                        </circle>
                                    </svg>
                                </span>
                            </button>
                        </form>
                        <div id="sd-claim-success" class="sd-claim-success" style="display: none;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/>
                            </svg>
                            <h4><?php _e( 'Anfrage erfolgreich!', 'spezialist-directory' ); ?></h4>
                            <p><?php _e( 'Deine Anfrage wurde eingereicht. Du erhältst eine E-Mail, sobald wir Deine Anfrage überprüft haben.', 'spezialist-directory' ); ?></p>
                        </div>

                    <?php else : ?>
                        <!-- State 4: User logged in but no claim param - Show simple button -->
                        <h3><?php _e( 'Ist dies Dein Eintrag?', 'spezialist-directory' ); ?></h3>
                        <p><?php _e( 'Beanspruche diesen Eintrag, um ihn zu verwalten.', 'spezialist-directory' ); ?></p>
                        <button type="button" class="sd-button sd-button-primary sd-show-claim-form-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            <?php _e( 'Eintrag beanspruchen', 'spezialist-directory' ); ?>
                        </button>
                        <!-- Hidden claim form that appears on button click -->
                        <div id="sd-inline-claim-form" class="sd-inline-claim-form" style="display: none;">
                            <p class="sd-claim-form-intro"><?php _e( 'Bitte erkläre kurz, warum Du diesen Eintrag beanspruchen möchtest.', 'spezialist-directory' ); ?></p>
                            <form id="sd-claim-form" class="sd-claim-form">
                                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                                <?php wp_nonce_field( 'sd_claim_listing', 'sd_claim_nonce' ); ?>
                                <div class="sd-form-group">
                                    <label for="sd-claim-reason"><?php _e( 'Begründung', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                                    <textarea id="sd-claim-reason" name="message" rows="4" required placeholder="<?php esc_attr_e( 'z.B. Ich bin der Inhaber dieses Unternehmens...', 'spezialist-directory' ); ?>"></textarea>
                                </div>
                                <button type="submit" class="sd-button sd-button-primary sd-claim-submit-btn">
                                    <span class="sd-btn-text"><?php _e( 'Anfrage absenden', 'spezialist-directory' ); ?></span>
                                    <span class="sd-btn-loading" style="display: none;">
                                        <svg class="sd-spinner" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" transform="rotate(-90 12 12)">
                                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                                            </circle>
                                        </svg>
                                    </span>
                                </button>
                            </form>
                            <div id="sd-claim-success" class="sd-claim-success" style="display: none;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/>
                                </svg>
                                <h4><?php _e( 'Anfrage erfolgreich!', 'spezialist-directory' ); ?></h4>
                                <p><?php _e( 'Deine Anfrage wurde eingereicht. Du erhältst eine E-Mail, sobald wir Deine Anfrage überprüft haben.', 'spezialist-directory' ); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Similar Specialists Section
    $similar_listings = SD_CPT_Spezialist::get_similar_listings( $post_id, 6 );
    if ( ! empty( $similar_listings ) ) :
    ?>
    <div class="sd-similar-specialists">
        <h2><?php _e( 'Diese Einträge könnten Dich auch interessieren', 'spezialist-directory' ); ?></h2>
        <div class="sd-similar-grid">
            <?php foreach ( $similar_listings as $similar_post ) :
                $similar_id = $similar_post->ID;
                $similar_categories = wp_get_object_terms( $similar_id, 'spezialist_category' );
                $similar_locations = wp_get_object_terms( $similar_id, 'spezialist_location' );
                $similar_city = get_post_meta( $similar_id, '_sd_city', true );
                $similar_is_premium = SD_Premium_Features::is_premium( $similar_id );
                $similar_is_claimed = get_post_meta( $similar_id, '_sd_is_claimed', true );
            ?>
                <a href="<?php echo get_permalink( $similar_id ); ?>" class="sd-similar-card <?php echo $similar_is_premium ? 'sd-card-premium' : ''; ?>">
                    <div class="sd-similar-image">
                        <?php if ( has_post_thumbnail( $similar_id ) ) : ?>
                            <?php echo get_the_post_thumbnail( $similar_id, 'medium', array( 'class' => 'sd-similar-img' ) ); ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url( home_url( '/wp-content/uploads/2025/12/placeholder.webp' ) ); ?>"
                                 alt="<?php esc_attr_e( 'Platzhalterbild', 'spezialist-directory' ); ?>"
                                 class="sd-similar-img" />
                        <?php endif; ?>
                        <?php if ( $similar_is_premium ) : ?>
                            <span class="sd-similar-premium-badge"><?php _e( 'Empfohlen', 'spezialist-directory' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="sd-similar-info">
                        <h3 class="sd-similar-title">
                            <?php echo esc_html( $similar_post->post_title ); ?>
                            <?php if ( $similar_is_claimed ) : ?>
                                <svg class="sd-similar-verified" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="currentColor"/>
                                </svg>
                            <?php endif; ?>
                        </h3>
                        <?php if ( ! empty( $similar_categories ) ) : ?>
                            <span class="sd-similar-category"><?php echo esc_html( $similar_categories[0]->name ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $similar_locations ) || $similar_city ) : ?>
                            <span class="sd-similar-location">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                                </svg>
                                <?php
                                if ( ! empty( $similar_locations ) ) {
                                    echo esc_html( $similar_locations[0]->name );
                                } elseif ( $similar_city ) {
                                    echo esc_html( $similar_city );
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                        <?php
                        // Show rating if ratings plugin is active
                        if ( class_exists( 'SR_Ratings' ) && method_exists( 'SR_Ratings', 'get_average' ) ) :
                            $rating_avg = SR_Ratings::get_average( $similar_id );
                            $rating_count = SR_Ratings::get_count( $similar_id );
                            if ( $rating_count > 0 ) :
                        ?>
                            <span class="sd-similar-rating">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="currentColor"/>
                                </svg>
                                <?php echo number_format( $rating_avg, 1, ',', '.' ); ?>
                                <span class="sd-similar-rating-count">(<?php echo (int) $rating_count; ?>)</span>
                            </span>
                        <?php endif; endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';

    var mapInitialized = false;

    // Tab Switching Logic
    document.addEventListener('DOMContentLoaded', function() {
        var tabs = document.querySelectorAll('.sd-detail-tabs .sd-tab');
        var tabContents = document.querySelectorAll('.sd-tab-content');

        // Function to activate a tab
        function activateTab(targetTab) {
            // Update active tab button
            tabs.forEach(function(t) {
                if (t.getAttribute('data-tab') === targetTab) {
                    t.classList.add('active');
                } else {
                    t.classList.remove('active');
                }
            });

            // Show/hide content panels
            tabContents.forEach(function(content) {
                if (content.getAttribute('data-tab') === targetTab) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });

            // Initialize map when Standort tab is shown (lazy load)
            if (targetTab === 'standort' && !mapInitialized) {
                initMap();
            }
        }

        // Check URL hash on page load and activate corresponding tab
        var hash = window.location.hash.replace('#', '');
        if (hash && document.querySelector('.sd-tab[data-tab="' + hash + '"]')) {
            activateTab(hash);
        }

        // Tab click handler
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var targetTab = this.getAttribute('data-tab');

                // Update URL hash without scrolling
                history.pushState(null, null, '#' + targetTab);

                // Activate the tab
                activateTab(targetTab);
            });
        });

        // Handle browser back/forward navigation
        window.addEventListener('popstate', function() {
            var hash = window.location.hash.replace('#', '');
            if (hash && document.querySelector('.sd-tab[data-tab="' + hash + '"]')) {
                activateTab(hash);
            } else {
                // Default to first tab if no hash
                activateTab('ueber');
            }
        });
    });

    // Map Initialization (Lazy loaded)
    function initMap() {
        if (typeof L === 'undefined') return;

        var mapContainer = document.getElementById('sd-map');
        if (!mapContainer) return;

        var address = mapContainer.getAttribute('data-address');
        if (!address) return;

        mapInitialized = true;

        // Initialize map with default view (Germany center)
        var map = L.map('sd-map').setView([51.1657, 10.4515], 6);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        // Geocode address using Nominatim (OpenStreetMap)
        var encodedAddress = encodeURIComponent(address);
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodedAddress + '&limit=1')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);

                    map.setView([lat, lon], 15);

                    L.marker([lat, lon]).addTo(map)
                        .bindPopup('<?php echo esc_js( get_the_title() ); ?>')
                        .openPopup();
                } else {
                    // If geocoding fails, hide the map container
                    mapContainer.parentElement.style.display = 'none';
                }
            })
            .catch(function() {
                mapContainer.parentElement.style.display = 'none';
            });

        // Invalidate map size after tab switch (Leaflet needs this)
        setTimeout(function() {
            map.invalidateSize();
        }, 100);
    }
})();
</script>

<!-- Analytics Tracking -->
<script>
(function() {
    'use strict';

    var postId = <?php echo (int) $post_id; ?>;
    var ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
    var analyticsNonce = '<?php echo wp_create_nonce( 'sd_analytics_nonce' ); ?>'; // Security fix: Add nonce
    var viewCookieName = 'sd_viewed_listings';

    // Check if already viewed in this session
    function hasViewedRecently() {
        try {
            var viewed = localStorage.getItem(viewCookieName);
            if (viewed) {
                var viewedList = JSON.parse(viewed);
                if (Array.isArray(viewedList) && viewedList.indexOf(postId) !== -1) {
                    return true;
                }
            }
        } catch (e) {}
        return false;
    }

    // Mark as viewed
    function markAsViewed() {
        try {
            var viewed = localStorage.getItem(viewCookieName);
            var viewedList = viewed ? JSON.parse(viewed) : [];
            if (!Array.isArray(viewedList)) viewedList = [];
            if (viewedList.indexOf(postId) === -1) {
                viewedList.push(postId);
                // Keep only last 100
                if (viewedList.length > 100) {
                    viewedList = viewedList.slice(-100);
                }
                localStorage.setItem(viewCookieName, JSON.stringify(viewedList));
            }
        } catch (e) {}
    }

    // Log view on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (!hasViewedRecently()) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    markAsViewed();
                }
            };
            xhr.send('action=sd_log_view&post_id=' + postId + '&nonce=' + analyticsNonce);
        }
    });

    // Track contact clicks
    function trackContact(type) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=sd_log_contact&post_id=' + postId + '&type=' + type + '&nonce=' + analyticsNonce);
    }

    // Attach click handlers to contact buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Phone links
        document.querySelectorAll('a[href^="tel:"]').forEach(function(el) {
            el.addEventListener('click', function() { trackContact('phone'); });
        });

        // Email links
        document.querySelectorAll('a[href^="mailto:"]').forEach(function(el) {
            el.addEventListener('click', function() { trackContact('email'); });
        });

        // Website links (action buttons and sidebar)
        document.querySelectorAll('.sd-action-btn[title="Website"], a.sd-contact-link[href*="http"]').forEach(function(el) {
            if (!el.href.startsWith('mailto:') && !el.href.startsWith('tel:') && !el.href.includes('google.com/maps')) {
                el.addEventListener('click', function() { trackContact('website'); });
            }
        });

        // Directions links (Google Maps)
        document.querySelectorAll('a[href*="google.com/maps"]').forEach(function(el) {
            el.addEventListener('click', function() { trackContact('directions'); });
        });

        // Hero action buttons
        var heroActions = document.querySelector('.sd-detail-hero-actions');
        if (heroActions) {
            heroActions.querySelectorAll('a').forEach(function(el) {
                var href = el.getAttribute('href') || '';
                if (href.startsWith('tel:')) {
                    el.addEventListener('click', function() { trackContact('phone'); });
                } else if (href.includes('google.com/maps')) {
                    el.addEventListener('click', function() { trackContact('directions'); });
                } else if (href.startsWith('http') && !href.includes('mailto:')) {
                    el.addEventListener('click', function() { trackContact('website'); });
                }
            });
        }
    });
})();
</script>

<!-- Quote Request Modal -->
<div id="sd-quote-modal" class="sd-modal" style="display: none;">
    <div class="sd-modal-backdrop"></div>
    <div class="sd-modal-content sd-quote-modal-content">
        <div class="sd-modal-header">
            <h3><?php _e( 'Anfrage senden', 'spezialist-directory' ); ?></h3>
            <button type="button" class="sd-modal-close">&times;</button>
        </div>
        <div class="sd-modal-body">
            <p class="sd-quote-modal-intro">
                <?php printf(
                    __( 'Sende eine unverbindliche Anfrage an <strong>%s</strong>.', 'spezialist-directory' ),
                    esc_html( get_the_title( $post_id ) )
                ); ?>
            </p>
            <form id="sd-quote-form" class="sd-quote-form">
                <input type="hidden" name="listing_id" value="<?php echo esc_attr( $post_id ); ?>">

                <div class="sd-form-row">
                    <div class="sd-form-group sd-form-half">
                        <label for="sd-quote-name"><?php _e( 'Name', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                        <input type="text" id="sd-quote-name" name="name" class="sd-form-input" required>
                    </div>
                    <div class="sd-form-group sd-form-half">
                        <label for="sd-quote-email"><?php _e( 'E-Mail', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                        <input type="email" id="sd-quote-email" name="email" class="sd-form-input" required>
                    </div>
                </div>

                <div class="sd-form-row">
                    <div class="sd-form-group sd-form-half">
                        <label for="sd-quote-phone"><?php _e( 'Telefon', 'spezialist-directory' ); ?></label>
                        <input type="tel" id="sd-quote-phone" name="phone" class="sd-form-input">
                    </div>
                    <div class="sd-form-group sd-form-half">
                        <label for="sd-quote-service"><?php _e( 'Gewünschter Service', 'spezialist-directory' ); ?></label>
                        <select id="sd-quote-service" name="service" class="sd-form-input">
                            <option value=""><?php _e( '-- Bitte wählen --', 'spezialist-directory' ); ?></option>
                            <?php
                            $services = get_post_meta( $post_id, '_sd_services', true );
                            if ( is_array( $services ) && ! empty( $services ) ) {
                                foreach ( $services as $service ) {
                                    echo '<option value="' . esc_attr( $service ) . '">' . esc_html( $service ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="sd-form-group">
                    <label for="sd-quote-message"><?php _e( 'Deine Nachricht', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                    <textarea id="sd-quote-message" name="message" class="sd-form-input" rows="5" required placeholder="<?php esc_attr_e( 'Beschreibe kurz dein Anliegen...', 'spezialist-directory' ); ?>"></textarea>
                </div>

                <div class="sd-form-group sd-form-consent">
                    <label>
                        <input type="checkbox" name="consent" required>
                        <?php printf(
                            __( 'Ich stimme der Verarbeitung meiner Daten gemäß der %sDatenschutzerklärung%s zu.', 'spezialist-directory' ),
                            '<a href="' . esc_url( get_privacy_policy_url() ) . '" target="_blank">',
                            '</a>'
                        ); ?>
                    </label>
                </div>

                <div class="sd-form-actions">
                    <button type="button" class="sd-button sd-button-secondary sd-modal-cancel">
                        <?php _e( 'Abbrechen', 'spezialist-directory' ); ?>
                    </button>
                    <button type="submit" class="sd-button sd-button-primary" id="sd-quote-submit">
                        <span class="sd-btn-text"><?php _e( 'Anfrage senden', 'spezialist-directory' ); ?></span>
                        <span class="sd-btn-loading" style="display: none;">
                            <svg class="sd-spinner" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" transform="rotate(-90 12 12)">
                                    <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>

            <div id="sd-quote-success" class="sd-quote-success" style="display: none;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/>
                </svg>
                <h4><?php _e( 'Anfrage erfolgreich gesendet!', 'spezialist-directory' ); ?></h4>
                <p><?php _e( 'Vielen Dank für deine Anfrage. Der Spezialist wurde benachrichtigt und wird sich in Kürze bei dir melden.', 'spezialist-directory' ); ?></p>
                <button type="button" class="sd-button sd-button-primary sd-modal-close-btn">
                    <?php _e( 'Schließen', 'spezialist-directory' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Template: User Dashboard
 *
 * Dashboard for users to manage their listings
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$premium_benefits = SD_Premium_Features::get_premium_benefits();
$pricing = SD_Premium_Features::get_pricing_options();

// Pagination settings
$per_page = 20;
$paged = isset( $_GET['dashboard_page'] ) ? max( 1, intval( $_GET['dashboard_page'] ) ) : 1;

// Get paginated listings (author OR claimed by user)
$user_listings = SD_User_Dashboard::get_user_listings( $user_id, $paged, $per_page );
$total_own_listings = $user_listings->found_posts;

// Get claimed listings IDs separately for flagging (only IDs, no full load)
$claimed_ids_query = new WP_Query( array(
    'post_type'      => 'hofladen',
    'posts_per_page' => -1,
    'post_status'    => array( 'publish', 'pending', 'draft' ),
    'fields'         => 'ids',
    'meta_query'     => array(
        array(
            'key'     => '_sd_claimed_by',
            'value'   => $user_id,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ),
    ),
) );
$claimed_listing_ids = $claimed_ids_query->posts;

// Build merged listings array from paginated results
$merged_listings = array();
if ( $user_listings->have_posts() ) {
    while ( $user_listings->have_posts() ) {
        $user_listings->the_post();
        $post_id = get_the_ID();
        $is_claimed = in_array( $post_id, $claimed_listing_ids );
        $claimed_date = $is_claimed ? get_post_meta( $post_id, '_sd_claimed_date', true ) : null;

        $merged_listings[ $post_id ] = array(
            'id'           => $post_id,
            'is_own'       => ( get_post_field( 'post_author', $post_id ) == $user_id ),
            'is_claimed'   => $is_claimed,
            'claimed_date' => $claimed_date,
        );
    }
    $user_listings->rewind_posts();
}
wp_reset_postdata();

// Get total count for stats (use efficient count method)
$total_listings_count = SD_User_Dashboard::get_user_listings_count( $user_id );
$total_claimed_count = count( $claimed_listing_ids );
// Remove duplicates (listings that are both owned AND claimed)
$merged_count = $total_listings_count; // For now, use author-based count as primary

// Pagination info
$total_pages = $user_listings->max_num_pages;

// Aggregate analytics for displayed listings only (not all)
$all_listing_ids = array_keys( $merged_listings );
$aggregated_analytics = SD_Analytics::get_aggregated_stats( $all_listing_ids );
$total_views = $aggregated_analytics['views'];
$total_contacts = $aggregated_analytics['contact_phone'] + $aggregated_analytics['contact_email'] + $aggregated_analytics['contact_website'] + $aggregated_analytics['contact_directions'];

// Get rankings for displayed listings
$listings_rankings = SD_Analytics::get_rankings_for_listings( $all_listing_ids );
?>

<!-- Hero Section -->
<section class="sd-contact-hero">
    <div class="sd-contact-hero-inner">
        <div class="sd-contact-hero-content">
            <h1 class="sd-contact-hero-title"><?php _e( 'Mein Dashboard', 'spezialist-directory' ); ?></h1>
            <p class="sd-contact-hero-subtitle">
                <?php _e( 'Verwalte deine Einträge', 'spezialist-directory' ); ?><br>
                <?php _e( 'und Einstellungen.', 'spezialist-directory' ); ?>
            </p>
        </div>
    </div>
    <div class="sd-contact-hero-accent"></div>
</section>

<div class="sd-dashboard-container">
    <div class="sd-dashboard-header">
        <div class="sd-dashboard-header-content">
            <h2><?php printf( __( 'Willkommen, %s!', 'spezialist-directory' ), esc_html( $current_user->display_name ) ); ?></h2>
            <p class="sd-dashboard-subtitle"><?php _e( 'Verwalte hier deine Einträge.', 'spezialist-directory' ); ?></p>
        </div>
        <div class="sd-dashboard-header-actions">
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="sd-button sd-button-secondary sd-button-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" fill="currentColor"/>
                </svg>
                <?php _e( 'Ausloggen', 'spezialist-directory' ); ?>
            </a>
        </div>
    </div>

    <div class="sd-notice sd-notice-info sd-dashboard-notice" style="display: none;"></div>

    <?php if ( isset( $_GET['submission'] ) && $_GET['submission'] === 'success' ) : ?>
        <div class="sd-notice sd-notice-success" style="margin-bottom: 20px;">
            <p><?php _e( 'Ihr Eintrag wurde erfolgreich eingereicht und wartet auf Freigabe durch einen Administrator.', 'spezialist-directory' ); ?></p>
        </div>
    <?php endif; ?>

    <!-- Dashboard Stats -->
    <div class="sd-dashboard-stats">
        <div class="sd-stat-box" id="sd-stat-listings">
            <div class="sd-stat-icon sd-stat-icon-listings">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V5H19V19Z" fill="currentColor"/>
                    <path d="M7 12H9V17H7V12ZM11 7H13V17H11V7ZM15 14H17V17H15V14Z" fill="currentColor"/>
                </svg>
            </div>
            <div class="sd-stat-content">
                <div class="sd-stat-number"><?php echo $merged_count; ?></div>
                <div class="sd-stat-label"><?php _e( 'Deine Einträge', 'spezialist-directory' ); ?></div>
            </div>
        </div>

        <div class="sd-stat-box" id="sd-stat-premium">
            <div class="sd-stat-icon sd-stat-icon-premium">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 17L6.12 20.55L7.72 13.97L2.44 9.45L9.16 8.95L12 2.5L14.84 8.95L21.56 9.45L16.28 13.97L17.88 20.55L12 17Z" fill="currentColor"/>
                </svg>
            </div>
            <div class="sd-stat-content">
                <div class="sd-stat-number">
                    <?php
                    // Zähle Premium aus merged_listings (keine Duplikate)
                    $premium_count = 0;
                    foreach ( $merged_listings as $listing_data ) {
                        if ( SD_Premium_Features::is_premium( $listing_data['id'] ) ) {
                            $premium_count++;
                        }
                    }
                    echo $premium_count;
                    ?>
                </div>
                <div class="sd-stat-label"><?php _e( 'Premium Einträge', 'spezialist-directory' ); ?></div>
            </div>
        </div>

        <div class="sd-stat-box">
            <div class="sd-stat-icon sd-stat-icon-views">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/>
                </svg>
            </div>
            <div class="sd-stat-content">
                <div class="sd-stat-number"><?php echo number_format_i18n( $total_views ); ?></div>
                <div class="sd-stat-label"><?php _e( 'Aufrufe gesamt', 'spezialist-directory' ); ?></div>
            </div>
        </div>

        <div class="sd-stat-box">
            <div class="sd-stat-icon sd-stat-icon-contacts">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/>
                </svg>
            </div>
            <div class="sd-stat-content">
                <div class="sd-stat-number"><?php echo number_format_i18n( $total_contacts ); ?></div>
                <div class="sd-stat-label"><?php _e( 'Kontakt-Aktionen', 'spezialist-directory' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sd-dashboard-actions">
        <a href="<?php echo esc_url( home_url( '/hofladen-hinzufuegen/' ) ); ?>" class="sd-button sd-button-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/>
            </svg>
            <?php _e( 'Neuen Eintrag hinzufügen', 'spezialist-directory' ); ?>
        </a>
    </div>

    <!-- Dashboard Tabs -->
    <div class="sd-dashboard-tabs">
        <button type="button" class="sd-tab-btn active" data-tab="listings">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z" fill="currentColor"/>
            </svg>
            <?php _e( 'Meine Einträge', 'spezialist-directory' ); ?>
        </button>
        <?php $new_leads_count = SD_Leads::get_new_leads_count( $user_id ); ?>
        <button type="button" class="sd-tab-btn" data-tab="leads">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z" fill="currentColor"/>
                <path d="M7 9h10v2H7zm0-3h10v2H7z" fill="currentColor"/>
            </svg>
            <?php _e( 'Anfragen', 'spezialist-directory' ); ?>
            <?php if ( $new_leads_count > 0 ) : ?>
                <span class="sd-tab-badge"><?php echo (int) $new_leads_count; ?></span>
            <?php endif; ?>
        </button>
        <button type="button" class="sd-tab-btn" data-tab="reviews">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="currentColor"/>
            </svg>
            <?php _e( 'Bewertungen', 'spezialist-directory' ); ?>
        </button>
        <button type="button" class="sd-tab-btn" data-tab="premium">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 17L6.12 20.55L7.72 13.97L2.44 9.45L9.16 8.95L12 2.5L14.84 8.95L21.56 9.45L16.28 13.97L17.88 20.55L12 17Z" fill="currentColor"/>
            </svg>
            <?php _e( 'Premium', 'spezialist-directory' ); ?>
        </button>
        <button type="button" class="sd-tab-btn" data-tab="settings">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" fill="currentColor"/>
            </svg>
            <?php _e( 'Einstellungen', 'spezialist-directory' ); ?>
        </button>
    </div>

    <!-- Tab Content: Listings -->
    <div class="sd-tab-content active" data-tab-content="listings">

    <!-- Unified Listings Section -->
    <div class="sd-dashboard-section">
        <h3><?php _e( 'Meine Einträge', 'spezialist-directory' ); ?></h3>

        <?php if ( ! empty( $merged_listings ) ) : ?>
            <div class="sd-listings-cards">
                <?php foreach ( $merged_listings as $listing_data ) :
                    global $post;
                    $post_id = $listing_data['id'];
                    $post = get_post( $post_id );
                    setup_postdata( $post );

                    $is_premium = SD_Premium_Features::is_premium( $post_id );
                    $is_paused = SD_User_Dashboard::is_listing_paused( $post_id );
                    $is_pending_premium = get_post_meta( $post_id, '_sd_pending_premium', true ) === '1';
                    $status = get_post_status();

                    // Get analytics for this listing
                    $listing_stats = SD_Analytics::get_stats( $post_id );
                    $listing_views = $listing_stats['views'];
                    $listing_contacts = SD_Analytics::get_total_contacts( $post_id );
                    $listing_ranking = isset( $listings_rankings[ $post_id ] ) ? $listings_rankings[ $post_id ] : array( 'position' => 0, 'total' => 0 );
                    $status_labels = array(
                        'publish' => '<span class="sd-status-badge sd-status-published"><i class="fa-solid fa-circle-check"></i>' . __( 'Veröffentlicht', 'spezialist-directory' ) . '</span>',
                        'pending' => '<span class="sd-status-badge sd-status-pending"><i class="fa-solid fa-clock"></i>' . __( 'Ausstehend', 'spezialist-directory' ) . '</span>',
                        'draft'   => '<span class="sd-status-badge sd-status-draft"><i class="fa-solid fa-file-lines"></i>' . __( 'Entwurf', 'spezialist-directory' ) . '</span>',
                    );

                    $categories = wp_get_object_terms( $post_id, 'spezialist_category', array( 'fields' => 'names' ) );
                ?>
                    <div class="sd-listing-card-item <?php echo $is_paused ? 'sd-listing-paused' : ''; ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-paused="<?php echo $is_paused ? '1' : '0'; ?>">
                        <!-- Row 1: Thumbnail + Title + Categories -->
                        <div class="sd-listing-row-title">
                            <div class="sd-listing-thumb-small sd-listing-thumb-title">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( array( 40, 40 ) ); ?>
                                <?php else : ?>
                                    <div class="sd-thumb-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="sd-listing-title-content">
                                <strong><?php the_title(); ?></strong>
                            <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                                <span class="sd-listing-category-small"><?php echo esc_html( implode( ', ', $categories ) ); ?></span>
                            <?php endif; ?>
                            <?php if ( $is_paused ) : ?>
                                <span class="sd-paused-badge">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/>
                                    </svg>
                                    <?php _e( 'Pausiert', 'spezialist-directory' ); ?>
                                </span>
                            <?php endif; ?>
                            </div><!-- /.sd-listing-title-content -->
                        </div>
                        <!-- Row 1.5: Verified Owner Badge (wenn claimed) -->
                        <?php if ( $listing_data['is_claimed'] ) : ?>
                            <div class="sd-verified-badge-row">
                                <span class="sd-claimed-badge">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="currentColor"/>
                                    </svg>
                                    <?php _e( 'Verifizierter Inhaber', 'spezialist-directory' ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <!-- Row 1.6: Analytics Stats -->
                        <div class="sd-listing-analytics-row">
                            <span class="sd-listing-stat" title="<?php esc_attr_e( 'Aufrufe', 'spezialist-directory' ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/>
                                </svg>
                                <?php echo number_format_i18n( $listing_views ); ?>
                            </span>
                            <span class="sd-listing-stat" title="<?php esc_attr_e( 'Kontaktaktionen', 'spezialist-directory' ); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="currentColor"/>
                                </svg>
                                <?php echo number_format_i18n( $listing_contacts ); ?>
                            </span>
                            <?php if ( $listing_ranking['total'] > 0 && 'publish' === $status ) : ?>
                                <span class="sd-listing-stat sd-listing-ranking" title="<?php esc_attr_e( 'Platzierung nach Beliebtheit', 'spezialist-directory' ); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7.5 21H2V9h5.5v12zm7.25-18h-5.5v18h5.5V3zm7.25 6h-5.5v12H22V9z" fill="currentColor"/>
                                    </svg>
                                    <?php
                                    printf(
                                        /* translators: %1$s: current position, %2$s: total listings */
                                        __( 'Platz %1$s von %2$s', 'spezialist-directory' ),
                                        '<strong>' . number_format_i18n( $listing_ranking['position'] ) . '</strong>',
                                        number_format_i18n( $listing_ranking['total'] )
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Row 2: Status | Premium | Date | Actions -->
                        <div class="sd-listing-row-details">
                            <div class="sd-listing-status">
                                <?php echo $status_labels[ $status ] ?? $status; ?>
                            </div>
                            <div class="sd-listing-premium">
                                <?php if ( $is_premium ) : ?>
                                    <span class="sd-status-badge sd-status-premium">
                                        <i class="fa-solid fa-star"></i>
                                        <?php _e( 'Premium', 'spezialist-directory' ); ?>
                                    </span>
                                <?php elseif ( $is_pending_premium ) : ?>
                                    <span class="sd-status-badge sd-status-pending-premium-type">
                                        <i class="fa-solid fa-star"></i>
                                        <?php _e( 'Premium', 'spezialist-directory' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="sd-status-badge sd-status-standard"><i class="fa-solid fa-layer-group"></i><?php _e( 'Standard', 'spezialist-directory' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="sd-listing-date">
                                <span class="sd-listing-date-text">
                                <i class="fa-solid fa-calendar"></i>
                                <?php
                                // Zeige claimed_date wenn nur claimed (nicht eigener), sonst post_date
                                $date_prefix = __( 'Erstellt:', 'spezialist-directory' );
                                if ( ! $listing_data['is_own'] && $listing_data['claimed_date'] ) {
                                    echo esc_html( $date_prefix . ' ' . date_i18n( 'j.n.Y', strtotime( $listing_data['claimed_date'] ) ) );
                                } else {
                                    echo esc_html( $date_prefix . ' ' . get_the_date( 'j.n.Y' ) );
                                }
                                ?>
                                </span>
                            </div>
                            <div class="sd-listing-actions">
                                <a href="<?php the_permalink(); ?>" class="sd-action-link" target="_blank" title="<?php esc_attr_e( 'Ansehen', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/>
                                    </svg>
                                </a>
                                <button type="button" class="sd-action-link sd-edit-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Bearbeiten', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                    </svg>
                                </button>
                                <?php // Pause/Unpause button - show for published listings ?>
                                <?php if ( 'publish' === $status ) : ?>
                                    <button type="button" class="sd-action-link sd-pause-listing <?php echo $is_paused ? 'sd-action-paused' : ''; ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php echo $is_paused ? esc_attr__( 'Aktivieren', 'spezialist-directory' ) : esc_attr__( 'Pausieren', 'spezialist-directory' ); ?>">
                                        <?php if ( $is_paused ) : ?>
                                            <svg class="sd-icon-play" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 5v14l11-7z" fill="currentColor"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg class="sd-icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" fill="currentColor"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>
                                <?php // Duplicate button - available for all listings ?>
                                <button type="button" class="sd-action-link sd-duplicate-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Duplizieren', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/>
                                    </svg>
                                </button>
                                <?php // Delete-Button nur wenn eigener Eintrag ?>
                                <?php if ( $listing_data['is_own'] ) : ?>
                                    <button type="button" class="sd-action-link sd-action-delete sd-delete-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Löschen', 'spezialist-directory' ); ?>">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php // Prominent Upgrade Button for non-premium listings ?>
                        <?php if ( ! $is_premium && ! $is_pending_premium ) : ?>
                            <div class="sd-listing-upgrade-row">
                                <button type="button" class="sd-upgrade-listing-btn sd-upgrade-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/>
                                    </svg>
                                    <?php _e( 'Eintrag upgraden!', 'spezialist-directory' ); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php
                    wp_reset_postdata();
                endforeach; ?>
            </div>

        <?php else : ?>
            <div class="sd-empty-state">
                <div class="sd-empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V5H19V19Z" fill="currentColor"/>
                    </svg>
                </div>
                <h3><?php _e( 'Noch keine Einträge', 'spezialist-directory' ); ?></h3>
                <p><?php _e( 'Du hast noch keine Hofladen-Einträge erstellt.', 'spezialist-directory' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/hofladen-hinzufuegen/' ) ); ?>" class="sd-button sd-button-primary">
                    <?php _e( 'Ersten Eintrag erstellen', 'spezialist-directory' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php // Pagination ?>
        <?php if ( $total_pages > 1 ) : ?>
            <div class="sd-dashboard-pagination">
                <div class="sd-pagination-info">
                    <?php
                    $start = ( ( $paged - 1 ) * $per_page ) + 1;
                    $end = min( $paged * $per_page, $total_own_listings );
                    printf(
                        __( 'Zeige %1$d-%2$d von %3$d Einträgen', 'spezialist-directory' ),
                        $start,
                        $end,
                        $total_own_listings
                    );
                    ?>
                </div>
                <div class="sd-pagination-links">
                    <?php if ( $paged > 1 ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'dashboard_page', $paged - 1 ) ); ?>" class="sd-pagination-link sd-pagination-prev">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="currentColor"/>
                            </svg>
                            <?php _e( 'Zurück', 'spezialist-directory' ); ?>
                        </a>
                    <?php endif; ?>

                    <span class="sd-pagination-current">
                        <?php printf( __( 'Seite %1$d von %2$d', 'spezialist-directory' ), $paged, $total_pages ); ?>
                    </span>

                    <?php if ( $paged < $total_pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'dashboard_page', $paged + 1 ) ); ?>" class="sd-pagination-link sd-pagination-next">
                            <?php _e( 'Weiter', 'spezialist-directory' ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z" fill="currentColor"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    </div><!-- End Tab Content: Listings -->

    <!-- Tab Content: Leads/Anfragen -->
    <div class="sd-tab-content" data-tab-content="leads">
        <div class="sd-dashboard-section">
            <h3><?php _e( 'Kundenanfragen', 'spezialist-directory' ); ?></h3>
            <p class="sd-section-desc"><?php _e( 'Hier siehst du alle Anfragen, die potenzielle Kunden über deine Einträge gesendet haben.', 'spezialist-directory' ); ?></p>

            <?php
            $user_leads = SD_Leads::get_user_leads( $user_id );
            ?>

            <?php if ( ! empty( $user_leads ) ) : ?>
                <div class="sd-leads-list">
                    <?php foreach ( $user_leads as $lead ) :
                        $status_class = 'sd-lead-status-' . $lead['status'];
                        $status_label = SD_Leads::get_status_label( $lead['status'] );
                    ?>
                        <div class="sd-lead-card <?php echo esc_attr( $status_class ); ?>" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                            <div class="sd-lead-header">
                                <div class="sd-lead-info">
                                    <strong class="sd-lead-name"><?php echo esc_html( $lead['name'] ); ?></strong>
                                    <span class="sd-lead-listing"><?php echo esc_html( $lead['listing_name'] ); ?></span>
                                </div>
                                <div class="sd-lead-meta">
                                    <span class="sd-lead-date"><?php echo esc_html( $lead['date'] ); ?></span>
                                    <span class="sd-lead-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
                                </div>
                            </div>
                            <div class="sd-lead-preview">
                                <?php if ( $lead['service'] ) : ?>
                                    <span class="sd-lead-service"><?php echo esc_html( $lead['service'] ); ?></span>
                                <?php endif; ?>
                                <p><?php echo esc_html( wp_trim_words( $lead['message'], 30, '...' ) ); ?></p>
                            </div>
                            <div class="sd-lead-actions">
                                <button type="button" class="sd-button sd-button-small sd-view-lead-btn" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                                    <?php _e( 'Details ansehen', 'spezialist-directory' ); ?>
                                </button>
                                <?php if ( $lead['email'] ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>?subject=<?php echo esc_attr( sprintf( __( 'Re: Deine Anfrage bei %s', 'spezialist-directory' ), get_bloginfo( 'name' ) ) ); ?>" class="sd-button sd-button-small sd-button-primary sd-reply-lead-btn" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                                        <?php _e( 'Antworten', 'spezialist-directory' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="sd-empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z" fill="#D1D5DB"/>
                        <path d="M7 9h10v2H7zm0-3h10v2H7z" fill="#D1D5DB"/>
                    </svg>
                    <h4><?php _e( 'Keine Anfragen', 'spezialist-directory' ); ?></h4>
                    <p><?php _e( 'Du hast noch keine Kundenanfragen erhalten. Sobald jemand eine Anfrage über einen deiner Einträge sendet, erscheint sie hier.', 'spezialist-directory' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- End Tab Content: Leads -->

    <!-- Tab Content: Reviews -->
    <div class="sd-tab-content" data-tab-content="reviews">
        <div class="sd-dashboard-section">
            <h3><?php _e( 'Bewertungen deiner Einträge', 'spezialist-directory' ); ?></h3>
            <p class="sd-section-desc"><?php _e( 'Hier kannst du Bewertungen deiner Einträge einsehen und darauf antworten.', 'spezialist-directory' ); ?></p>

            <?php
            // Get all reviews for this user's listings
            $owner_reviews = array();
            if ( class_exists( 'SR_Ratings' ) ) {
                $owner_reviews = SR_Ratings::get_all_owner_reviews( get_current_user_id() );
            }
            ?>

            <?php if ( ! empty( $owner_reviews ) ) : ?>
                <div class="sd-reviews-list">
                    <?php foreach ( $owner_reviews as $review ) : ?>
                        <div class="sd-review-item" data-review-id="<?php echo esc_attr( $review->id ); ?>">
                            <div class="sd-review-header">
                                <div class="sd-review-listing-info">
                                    <a href="<?php echo esc_url( get_permalink( $review->post_id ) ); ?>" class="sd-review-listing-title">
                                        <?php echo esc_html( $review->post_title ); ?>
                                    </a>
                                </div>
                                <div class="sd-review-meta">
                                    <span class="sd-review-author"><?php echo esc_html( $review->user_name ); ?></span>
                                    <span class="sd-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
                                </div>
                            </div>
                            <div class="sd-review-rating">
                                <?php if ( class_exists( 'SR_Ratings' ) ) echo SR_Ratings::render_stars( $review->rating, 16 ); ?>
                            </div>
                            <?php if ( ! empty( $review->comment ) ) : ?>
                                <div class="sd-review-comment">
                                    <p><?php echo esc_html( $review->comment ); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $review->owner_response ) ) : ?>
                                <!-- Owner Response (already submitted) -->
                                <div class="sd-owner-response">
                                    <div class="sd-response-header">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" fill="currentColor"/>
                                        </svg>
                                        <span><?php _e( 'Deine Antwort', 'spezialist-directory' ); ?></span>
                                        <?php if ( ! empty( $review->owner_response_at ) ) : ?>
                                            <span class="sd-response-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->owner_response_at ) ) ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sd-response-content">
                                        <p><?php echo esc_html( $review->owner_response ); ?></p>
                                    </div>
                                </div>
                            <?php else : ?>
                                <!-- Response Form -->
                                <div class="sd-response-form-container">
                                    <button type="button" class="sd-button sd-button-secondary sd-button-small sd-show-response-form">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" fill="currentColor"/>
                                        </svg>
                                        <?php _e( 'Antworten', 'spezialist-directory' ); ?>
                                    </button>
                                    <form class="sd-response-form" style="display: none;">
                                        <input type="hidden" name="rating_id" value="<?php echo esc_attr( $review->id ); ?>">
                                        <div class="sd-form-group">
                                            <textarea name="response" rows="3" placeholder="<?php esc_attr_e( 'Deine Antwort auf diese Bewertung...', 'spezialist-directory' ); ?>" required></textarea>
                                        </div>
                                        <div class="sd-form-actions">
                                            <button type="button" class="sd-button sd-button-secondary sd-button-small sd-cancel-response">
                                                <?php _e( 'Abbrechen', 'spezialist-directory' ); ?>
                                            </button>
                                            <button type="submit" class="sd-button sd-button-primary sd-button-small sd-submit-response">
                                                <span class="sd-btn-text"><?php _e( 'Antwort senden', 'spezialist-directory' ); ?></span>
                                                <span class="sd-btn-loading" style="display: none;">
                                                    <span class="sd-spinner-small"></span>
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="sd-empty-state">
                    <div class="sd-empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#E5E7EB"/>
                        </svg>
                    </div>
                    <h3><?php _e( 'Noch keine Bewertungen', 'spezialist-directory' ); ?></h3>
                    <p><?php _e( 'Deine Einträge haben noch keine Bewertungen erhalten.', 'spezialist-directory' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- End Tab Content: Reviews -->

    <!-- Tab Content: Premium -->
    <div class="sd-tab-content" data-tab-content="premium">
        <?php
        // Collect all user's listings (owned + claimed) for the upgrade selector
        $all_user_listings = array();
        if ( $user_listings->have_posts() ) {
            while ( $user_listings->have_posts() ) {
                $user_listings->the_post();
                $post_id = get_the_ID();
                $is_premium = SD_Premium_Features::is_premium( $post_id );
                $all_user_listings[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'is_premium' => $is_premium,
                    'subscription_id' => get_post_meta( $post_id, '_sd_stripe_subscription_id', true ),
                    'subscription_plan' => $is_premium ? get_post_meta( $post_id, '_sd_subscription_plan', true ) : 'free',
                    'premium_until' => get_post_meta( $post_id, '_sd_premium_until', true ),
                    'cancel_at_period_end' => get_post_meta( $post_id, '_sd_subscription_cancel_at_period_end', true ),
                );
            }
            $user_listings->rewind_posts();
        }
        // Add claimed listings using the IDs already fetched at template start
        if ( ! empty( $claimed_listing_ids ) ) {
            // Get existing IDs to avoid duplicates
            $existing_ids = array_column( $all_user_listings, 'id' );
            foreach ( $claimed_listing_ids as $claimed_id ) {
                // Skip if already added from user_listings
                if ( in_array( $claimed_id, $existing_ids ) ) {
                    continue;
                }
                $is_premium = SD_Premium_Features::is_premium( $claimed_id );
                $all_user_listings[] = array(
                    'id' => $claimed_id,
                    'title' => get_the_title( $claimed_id ),
                    'is_premium' => $is_premium,
                    'subscription_id' => get_post_meta( $claimed_id, '_sd_stripe_subscription_id', true ),
                    'subscription_plan' => $is_premium ? get_post_meta( $claimed_id, '_sd_subscription_plan', true ) : 'free',
                    'premium_until' => get_post_meta( $claimed_id, '_sd_premium_until', true ),
                    'cancel_at_period_end' => get_post_meta( $claimed_id, '_sd_subscription_cancel_at_period_end', true ),
                    'claimed' => true,
                );
            }
        }
        wp_reset_postdata();

        // Separate premium and non-premium listings
        $premium_listings = array_filter( $all_user_listings, function( $l ) { return $l['is_premium']; } );
        $non_premium_listings = array_filter( $all_user_listings, function( $l ) { return ! $l['is_premium']; } );
        ?>

        <!-- Premium Intro Banner -->
        <div class="sd-premium-intro">
            <div class="sd-premium-intro-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 17L6.12 20.55L7.72 13.97L2.44 9.45L9.16 8.95L12 2.5L14.84 8.95L21.56 9.45L16.28 13.97L17.88 20.55L12 17Z" fill="currentColor"/>
                </svg>
            </div>
            <div class="sd-premium-intro-content">
                <h3><?php _e( 'Werte deinen Eintrag auf!', 'spezialist-directory' ); ?></h3>
                <p><?php _e( 'Mehr Sichtbarkeit und Funktionen zum attraktiven Preis', 'spezialist-directory' ); ?></p>
            </div>
        </div>

        <!-- Step 1: Listing Selector (shown first) -->
        <div id="sd-listing-selector" class="sd-listing-selector">
            <div class="sd-section-header">
                <h4><?php _e( 'Wähle einen Eintrag', 'spezialist-directory' ); ?></h4>
                <p class="sd-section-description"><?php _e( 'Wähle den Eintrag, dessen Plan du ansehen oder ändern möchtest.', 'spezialist-directory' ); ?></p>
            </div>

            <?php if ( ! empty( $all_user_listings ) ) : ?>
                <div class="sd-listing-select-grid">
                    <?php foreach ( $all_user_listings as $listing ) :
                        $current_plan = ! empty( $listing['subscription_plan'] ) ? $listing['subscription_plan'] : 'free';
                        $plan_labels = array(
                            'free' => __( 'Standard', 'spezialist-directory' ),
                            'monthly' => __( 'Premium Monatlich', 'spezialist-directory' ),
                            'yearly' => __( 'Premium Jährlich', 'spezialist-directory' ),
                        );
                        $plan_label = isset( $plan_labels[ $current_plan ] ) ? $plan_labels[ $current_plan ] : $plan_labels['free'];
                    ?>
                        <div class="sd-listing-select-item <?php echo $listing['is_premium'] ? 'is-premium sd-listing-disabled' : ''; ?>"
                             data-post-id="<?php echo esc_attr( $listing['id'] ); ?>"
                             data-current-plan="<?php echo esc_attr( $current_plan ); ?>"
                             data-listing-title="<?php echo esc_attr( $listing['title'] ); ?>"
                             <?php echo $listing['is_premium'] ? 'aria-disabled="true"' : ''; ?>>
                            <div class="sd-listing-select-title"><?php echo esc_html( $listing['title'] ); ?></div>
                            <div class="sd-listing-select-plan">
                                <?php if ( $listing['is_premium'] ) : ?>
                                    <span class="sd-plan-badge sd-plan-premium">
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/>
                                        </svg>
                                        <?php echo esc_html( $plan_label ); ?>
                                    </span>
                                    <span class="sd-listing-already-premium-hint"><?php _e( 'Bereits Premium', 'spezialist-directory' ); ?></span>
                                <?php else : ?>
                                    <span class="sd-plan-badge sd-plan-free"><?php echo esc_html( $plan_label ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( isset( $listing['claimed'] ) && $listing['claimed'] ) : ?>
                                <span class="sd-listing-select-badge"><?php _e( 'Beansprucht', 'spezialist-directory' ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="sd-notice sd-notice-info">
                    <?php _e( 'Du hast noch keine Einträge erstellt.', 'spezialist-directory' ); ?>
                    <a href="<?php echo esc_url( home_url( '/hofladen-hinzufuegen/' ) ); ?>" class="sd-button sd-button-primary">
                        <?php _e( 'Eintrag hinzufügen', 'spezialist-directory' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Step 2: Pricing Table (hidden initially, shown after listing selection) -->
        <div id="sd-pricing-table-container" class="sd-pricing-table-container" style="display: none;">
            <!-- Selected Listing Info Bar -->
            <div class="sd-selected-listing-info">
                <div class="sd-selected-listing-details">
                    <span><?php _e( 'Ausgewählt:', 'spezialist-directory' ); ?></span>
                    <strong id="sd-selected-listing-title"></strong>
                </div>
                <button type="button" class="sd-button sd-button-secondary sd-change-listing">
                    <?php _e( 'Anderes Listing wählen', 'spezialist-directory' ); ?>
                </button>
            </div>

            <!-- Pricing Table -->
            <div class="sd-pricing-table">
                <?php foreach ( $pricing as $plan_id => $plan ) : ?>
                    <div class="sd-pricing-card <?php echo isset( $plan['featured'] ) && $plan['featured'] ? 'sd-pricing-featured' : ''; ?>" data-plan="<?php echo esc_attr( $plan_id ); ?>">
                        <?php if ( isset( $plan['badge'] ) ) : ?>
                            <div class="sd-pricing-badge"><?php echo esc_html( $plan['badge'] ); ?></div>
                        <?php endif; ?>

                        <div class="sd-pricing-header">
                            <h4><?php echo esc_html( $plan['label'] ); ?></h4>
                        </div>

                        <div class="sd-pricing-price">
                            <span class="sd-price-amount"><?php echo esc_html( $plan['price'] ); ?></span>
                            <?php if ( ! empty( $plan['period'] ) ) : ?>
                                <span class="sd-price-period"><?php echo esc_html( $plan['period'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( isset( $plan['price_note'] ) ) : ?>
                                <span class="sd-price-note"><?php echo esc_html( $plan['price_note'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="sd-pricing-description"><?php echo esc_html( $plan['description'] ); ?></p>

                        <?php if ( isset( $plan['features'] ) ) : ?>
                            <ul class="sd-pricing-features">
                                <?php foreach ( $plan['features'] as $feature ) : ?>
                                    <li>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                                        </svg>
                                        <?php echo esc_html( $feature ); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="sd-pricing-action">
                            <!-- Will be dynamically updated by JavaScript based on current plan -->
                            <span class="sd-pricing-current" style="display: none;"><?php _e( 'Aktueller Plan', 'spezialist-directory' ); ?></span>
                            <button type="button" class="sd-button sd-button-primary sd-upgrade-plan-btn" data-plan="<?php echo esc_attr( $plan_id ); ?>" style="display: none;">
                                <?php _e( 'Upgrade wählen', 'spezialist-directory' ); ?>
                            </button>
                            <span class="sd-pricing-unavailable" style="display: none;">—</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Active Premium Subscriptions -->
        <?php if ( ! empty( $premium_listings ) ) : ?>
            <div class="sd-active-subscriptions">
                <h4><?php _e( 'Deine Premium Abonnements', 'spezialist-directory' ); ?></h4>

                <div class="sd-subscriptions-list">
                    <?php foreach ( $premium_listings as $listing ) : ?>
                        <div class="sd-subscription-item" data-post-id="<?php echo esc_attr( $listing['id'] ); ?>">
                            <div class="sd-subscription-info">
                                <div class="sd-subscription-title">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 15L3.5 18L5.5 11L0 6.5L7.5 6L10 0L12.5 6L20 6.5L14.5 11L16.5 18L10 15Z" fill="currentColor"/>
                                    </svg>
                                    <?php echo esc_html( $listing['title'] ); ?>
                                </div>
                                <div class="sd-subscription-details">
                                    <?php if ( ! empty( $listing['premium_until'] ) ) : ?>
                                        <?php if ( $listing['cancel_at_period_end'] ) : ?>
                                            <span class="sd-subscription-status sd-status-cancelling">
                                                <?php printf(
                                                    __( 'Läuft aus am %s', 'spezialist-directory' ),
                                                    date_i18n( get_option( 'date_format' ), strtotime( $listing['premium_until'] ) )
                                                ); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="sd-subscription-status">
                                                <?php printf(
                                                    __( 'Verlängert sich am %s', 'spezialist-directory' ),
                                                    date_i18n( get_option( 'date_format' ), strtotime( $listing['premium_until'] ) )
                                                ); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="sd-subscription-actions">
                                <button type="button" class="sd-button sd-button-small sd-button-secondary sd-manage-billing">
                                    <?php _e( 'Abrechnung verwalten', 'spezialist-directory' ); ?>
                                </button>
                                <?php if ( ! empty( $listing['subscription_id'] ) && ! $listing['cancel_at_period_end'] ) : ?>
                                    <button type="button" class="sd-button sd-button-small sd-button-danger sd-cancel-sub" data-post-id="<?php echo esc_attr( $listing['id'] ); ?>">
                                        <?php _e( 'Kündigen', 'spezialist-directory' ); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- End Tab Content: Premium -->

    <!-- Tab Content: Settings -->
    <div class="sd-tab-content" data-tab-content="settings">

    <!-- Account Settings -->
    <div class="sd-dashboard-section">
        <h3><?php _e( 'Account-Einstellungen', 'spezialist-directory' ); ?></h3>
        <div class="sd-account-info">
            <p><strong><?php _e( 'Name:', 'spezialist-directory' ); ?></strong> <span id="sd-profile-display-name"><?php echo esc_html( $current_user->display_name ); ?></span></p>
            <p><strong><?php _e( 'E-Mail:', 'spezialist-directory' ); ?></strong> <span id="sd-profile-email"><?php echo esc_html( $current_user->user_email ); ?></span></p>
            <p>
                <button type="button" class="sd-button sd-button-secondary sd-edit-profile"
                    data-first-name="<?php echo esc_attr( $current_user->first_name ); ?>"
                    data-last-name="<?php echo esc_attr( $current_user->last_name ); ?>"
                    data-email="<?php echo esc_attr( $current_user->user_email ); ?>">
                    <?php _e( 'Profil bearbeiten', 'spezialist-directory' ); ?>
                </button>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="sd-button sd-button-secondary">
                    <?php _e( 'Abmelden', 'spezialist-directory' ); ?>
                </a>
            </p>
        </div>
    </div>

    </div><!-- End Tab Content: Settings -->

</div><!-- End sd-dashboard-container -->

<!-- Edit Modal -->
<div id="sd-edit-modal" class="sd-modal" style="display: none;">
    <div class="sd-modal-backdrop"></div>
    <div class="sd-modal-content sd-modal-large">
        <div class="sd-modal-header">
            <h3><?php _e( 'Eintrag bearbeiten', 'spezialist-directory' ); ?></h3>
            <button type="button" class="sd-modal-close">&times;</button>
        </div>
        <div class="sd-modal-body">
            <!-- Loading state -->
            <div id="sd-edit-loading" class="sd-modal-loading">
                <div class="sd-spinner"></div>
                <p><?php _e( 'Lade Daten...', 'spezialist-directory' ); ?></p>
            </div>

            <!-- Edit form (hidden initially) -->
            <form id="sd-edit-form" class="sd-edit-form" style="display: none;">
                <input type="hidden" name="post_id" id="sd-edit-post-id" value="">

                <!-- Basic Info Section -->
                <div class="sd-form-section">
                    <h4><?php _e( 'Grundinformationen', 'spezialist-directory' ); ?></h4>

                    <div class="sd-form-group">
                        <label for="sd-edit-title"><?php _e( 'Name / Firmenname', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                        <input type="text" id="sd-edit-title" name="title" required class="sd-form-input">
                    </div>

                    <div class="sd-form-group">
                        <label for="sd-edit-description"><?php _e( 'Beschreibung', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                        <textarea id="sd-edit-description" name="description" rows="6" required class="sd-form-textarea"></textarea>
                    </div>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-category"><?php _e( 'Kategorie', 'spezialist-directory' ); ?></label>
                            <select id="sd-edit-category" name="category[]" multiple class="sd-form-select">
                                <!-- Options loaded via JS -->
                            </select>
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-location"><?php _e( 'Standort', 'spezialist-directory' ); ?></label>
                            <select id="sd-edit-location" name="location[]" multiple class="sd-form-select">
                                <!-- Options loaded via JS -->
                            </select>
                        </div>
                    </div>

                    <div class="sd-form-group">
                        <label for="sd-edit-profile-image"><?php _e( 'Profilbild / Logo', 'spezialist-directory' ); ?></label>
                        <div class="sd-profile-image-edit">
                            <div class="sd-current-profile-image" id="sd-edit-current-profile-image">
                                <!-- Current image will be populated via JavaScript -->
                            </div>
                            <input type="file" id="sd-edit-profile-image" name="profile_image" class="sd-form-input" accept="image/jpeg,image/png,image/webp">
                            <p class="sd-form-hint"><?php _e( 'Max. 5 MB. Erlaubte Formate: JPG, PNG, WebP', 'spezialist-directory' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Contact Info Section -->
                <div class="sd-form-section">
                    <h4><?php _e( 'Kontaktdaten', 'spezialist-directory' ); ?></h4>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-phone"><?php _e( 'Telefon', 'spezialist-directory' ); ?></label>
                            <input type="tel" id="sd-edit-phone" name="phone" class="sd-form-input">
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-email"><?php _e( 'E-Mail', 'spezialist-directory' ); ?></label>
                            <input type="email" id="sd-edit-email" name="email" class="sd-form-input">
                        </div>
                    </div>

                    <div class="sd-form-group">
                        <label for="sd-edit-website"><?php _e( 'Website', 'spezialist-directory' ); ?></label>
                        <input type="url" id="sd-edit-website" name="website" class="sd-form-input" placeholder="https://">
                    </div>
                </div>

                <!-- Address Section -->
                <div class="sd-form-section">
                    <h4><?php _e( 'Adresse', 'spezialist-directory' ); ?></h4>

                    <div class="sd-form-group">
                        <label for="sd-edit-address"><?php _e( 'Straße & Hausnummer', 'spezialist-directory' ); ?></label>
                        <input type="text" id="sd-edit-address" name="address" class="sd-form-input">
                    </div>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-third">
                            <label for="sd-edit-zip"><?php _e( 'PLZ', 'spezialist-directory' ); ?></label>
                            <input type="text" id="sd-edit-zip" name="zip" class="sd-form-input">
                        </div>
                        <div class="sd-form-group sd-form-two-thirds">
                            <label for="sd-edit-city"><?php _e( 'Stadt', 'spezialist-directory' ); ?></label>
                            <input type="text" id="sd-edit-city" name="city" class="sd-form-input">
                        </div>
                    </div>
                </div>

                <!-- Services Section (Premium) -->
                <div class="sd-form-section">
                    <h4>
                        <?php _e( 'Angebotene Leistungen', 'spezialist-directory' ); ?>
                        <span class="sd-premium-badge-inline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Premium
                        </span>
                    </h4>
                    <p class="sd-premium-note"><?php _e( 'Diese Funktion ist nur für Premium-Einträge sichtbar.', 'spezialist-directory' ); ?></p>
                    <p class="sd-form-section-desc"><?php _e( 'Liste deine angebotenen Dienstleistungen auf.', 'spezialist-directory' ); ?></p>

                    <div class="sd-services-form" id="sd-edit-services-container">
                        <div class="sd-services-list" id="sd-edit-services-list">
                            <!-- Services will be populated via JavaScript -->
                        </div>
                        <div class="sd-service-add-row">
                            <input type="text" id="sd-edit-new-service" class="sd-form-input" placeholder="<?php esc_attr_e( 'z.B. Beratung, Webdesign, Reparatur...', 'spezialist-directory' ); ?>">
                            <button type="button" class="sd-button sd-button-secondary sd-add-service-edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/>
                                </svg>
                                <?php _e( 'Hinzufügen', 'spezialist-directory' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Business Hours Section (Premium) -->
                <div class="sd-form-section">
                    <h4>
                        <?php _e( 'Öffnungszeiten', 'spezialist-directory' ); ?>
                        <span class="sd-premium-badge-inline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Premium
                        </span>
                    </h4>
                    <p class="sd-premium-note"><?php _e( 'Diese Funktion ist nur für Premium-Einträge sichtbar.', 'spezialist-directory' ); ?></p>
                    <p class="sd-form-section-desc"><?php _e( 'Gib deine Öffnungszeiten an.', 'spezialist-directory' ); ?></p>

                    <div class="sd-business-hours-form" id="sd-edit-business-hours">
                        <?php
                        $days = array(
                            'monday'    => __( 'Montag', 'spezialist-directory' ),
                            'tuesday'   => __( 'Dienstag', 'spezialist-directory' ),
                            'wednesday' => __( 'Mittwoch', 'spezialist-directory' ),
                            'thursday'  => __( 'Donnerstag', 'spezialist-directory' ),
                            'friday'    => __( 'Freitag', 'spezialist-directory' ),
                            'saturday'  => __( 'Samstag', 'spezialist-directory' ),
                            'sunday'    => __( 'Sonntag', 'spezialist-directory' ),
                        );
                        foreach ( $days as $day_key => $day_name ) :
                        ?>
                            <div class="sd-hours-row">
                                <div class="sd-hours-day">
                                    <label class="sd-hours-checkbox">
                                        <input type="checkbox" name="business_hours[<?php echo esc_attr( $day_key ); ?>][open]" value="1" id="sd-edit-hours-<?php echo esc_attr( $day_key ); ?>-open">
                                        <span class="sd-hours-day-name"><?php echo esc_html( $day_name ); ?></span>
                                    </label>
                                </div>
                                <div class="sd-hours-times">
                                    <div class="sd-hours-time-group">
                                        <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][from]" class="sd-form-input sd-input-time" id="sd-edit-hours-<?php echo esc_attr( $day_key ); ?>-from">
                                        <span class="sd-hours-separator">-</span>
                                        <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][to]" class="sd-form-input sd-input-time" id="sd-edit-hours-<?php echo esc_attr( $day_key ); ?>-to">
                                    </div>
                                    <div class="sd-hours-break">
                                        <label class="sd-hours-break-label"><?php _e( 'Pause:', 'spezialist-directory' ); ?></label>
                                        <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][break_from]" class="sd-form-input sd-input-time" id="sd-edit-hours-<?php echo esc_attr( $day_key ); ?>-break-from">
                                        <span class="sd-hours-separator">-</span>
                                        <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][break_to]" class="sd-form-input sd-input-time" id="sd-edit-hours-<?php echo esc_attr( $day_key ); ?>-break-to">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Social Media Section (Premium) -->
                <div class="sd-form-section">
                    <h4>
                        <?php _e( 'Social Media', 'spezialist-directory' ); ?>
                        <span class="sd-premium-badge-inline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Premium
                        </span>
                    </h4>
                    <p class="sd-premium-note"><?php _e( 'Diese Funktion ist nur für Premium-Einträge sichtbar.', 'spezialist-directory' ); ?></p>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-facebook">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">facebook.com/</span>
                                <input type="text" id="sd-edit-facebook" name="facebook" class="sd-form-input sd-input-with-addon" placeholder="deinefirma">
                            </div>
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-instagram">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                Instagram
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">instagram.com/</span>
                                <input type="text" id="sd-edit-instagram" name="instagram" class="sd-form-input sd-input-with-addon" placeholder="deinprofil">
                            </div>
                        </div>
                    </div>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-linkedin">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                LinkedIn
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">linkedin.com/in/</span>
                                <input type="text" id="sd-edit-linkedin" name="linkedin" class="sd-form-input sd-input-with-addon" placeholder="vorname-nachname">
                            </div>
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-xing">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/></svg>
                                Xing
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">xing.com/profile/</span>
                                <input type="text" id="sd-edit-xing" name="xing" class="sd-form-input sd-input-with-addon" placeholder="Vorname_Nachname">
                            </div>
                        </div>
                    </div>

                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-twitter">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                X (ehemals Twitter)
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">x.com/</span>
                                <input type="text" id="sd-edit-twitter" name="twitter" class="sd-form-input sd-input-with-addon" placeholder="deinprofil">
                            </div>
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-edit-youtube">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                YouTube
                            </label>
                            <div class="sd-input-group">
                                <span class="sd-input-addon">youtube.com/@</span>
                                <input type="text" id="sd-edit-youtube" name="youtube" class="sd-form-input sd-input-with-addon" placeholder="deinkanal">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gallery Section (Premium Only) -->
                <div class="sd-form-section sd-gallery-section" id="sd-edit-gallery-section" style="display: none;">
                    <h4>
                        <?php _e( 'Bildergalerie', 'spezialist-directory' ); ?>
                        <span class="sd-premium-badge-inline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Premium
                        </span>
                    </h4>
                    <p class="sd-premium-note"><?php _e( 'Diese Funktion ist nur für Premium-Einträge sichtbar.', 'spezialist-directory' ); ?></p>
                    <p class="sd-form-section-desc"><?php _e( 'Lade bis zu 10 Bilder für deine Galerie hoch.', 'spezialist-directory' ); ?></p>

                    <div class="sd-gallery-edit-container">
                        <div class="sd-gallery-current" id="sd-edit-gallery-current">
                            <!-- Existing gallery images will be populated via JavaScript -->
                        </div>
                        <div class="sd-form-group">
                            <label for="sd-edit-gallery"><?php _e( 'Neue Bilder hinzufügen', 'spezialist-directory' ); ?></label>
                            <input type="file" id="sd-edit-gallery" name="gallery[]" class="sd-form-input" accept="image/*" multiple>
                            <p class="sd-form-hint"><?php _e( 'Max. 5 MB pro Bild. Erlaubte Formate: JPG, PNG, WebP', 'spezialist-directory' ); ?></p>
                        </div>
                    </div>

                    <div class="sd-gallery-not-premium" id="sd-edit-gallery-not-premium" style="display: none;">
                        <div class="sd-premium-upgrade-prompt">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                            </svg>
                            <p><?php _e( 'Die Bildergalerie ist nur für Premium-Einträge verfügbar.', 'spezialist-directory' ); ?></p>
                            <button type="button" class="sd-button sd-button-premium sd-upgrade-btn-inline">
                                <?php _e( 'Jetzt upgraden', 'spezialist-directory' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Video Section (Premium Only) -->
                <div class="sd-form-section sd-video-section" id="sd-edit-video-section" style="display: none;">
                    <h4>
                        <?php _e( 'Video hochladen', 'spezialist-directory' ); ?>
                        <span class="sd-premium-badge-inline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Premium
                        </span>
                    </h4>
                    <p class="sd-premium-note"><?php _e( 'Diese Funktion ist nur für Premium-Einträge sichtbar.', 'spezialist-directory' ); ?></p>
                    <p class="sd-form-section-desc"><?php _e( 'Lade ein Video für deinen Eintrag hoch.', 'spezialist-directory' ); ?></p>

                    <div class="sd-video-edit-container">
                        <div class="sd-video-current" id="sd-edit-video-current">
                            <!-- Current video will be populated via JavaScript -->
                        </div>
                        <div class="sd-form-group">
                            <label for="sd-edit-video"><?php _e( 'Neues Video hochladen', 'spezialist-directory' ); ?></label>
                            <input type="file" id="sd-edit-video" name="video" class="sd-form-input" accept="video/mp4,video/webm,video/quicktime">
                            <p class="sd-form-hint"><?php _e( 'Max. 10 MB. Erlaubte Formate: MP4, WebM, MOV', 'spezialist-directory' ); ?></p>
                        </div>
                    </div>

                    <div class="sd-video-not-premium" id="sd-edit-video-not-premium" style="display: none;">
                        <div class="sd-premium-upgrade-prompt">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                            </svg>
                            <p><?php _e( 'Das Video-Feature ist nur für Premium-Einträge verfügbar.', 'spezialist-directory' ); ?></p>
                            <button type="button" class="sd-button sd-button-premium sd-upgrade-btn-inline">
                                <?php _e( 'Jetzt upgraden', 'spezialist-directory' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="sd-form-actions">
                    <button type="button" class="sd-button sd-button-secondary sd-modal-cancel">
                        <?php _e( 'Abbrechen', 'spezialist-directory' ); ?>
                    </button>
                    <button type="submit" class="sd-button sd-button-primary" id="sd-edit-submit">
                        <span class="sd-btn-text"><?php _e( 'Speichern', 'spezialist-directory' ); ?></span>
                        <span class="sd-btn-loading" style="display: none;">
                            <span class="sd-spinner-small"></span>
                            <?php _e( 'Speichern...', 'spezialist-directory' ); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgrade Modal Placeholder -->
<div id="sd-upgrade-modal" class="sd-modal" style="display: none;">
    <div class="sd-modal-backdrop"></div>
    <div class="sd-modal-content">
        <div class="sd-modal-header">
            <h3><?php _e( 'Premium Upgrade', 'spezialist-directory' ); ?></h3>
            <button type="button" class="sd-modal-close">&times;</button>
        </div>
        <div class="sd-modal-body">
            <!-- Upgrade options will be loaded here via JavaScript -->
        </div>
    </div>
</div>

<!-- Profile Edit Modal -->
<div id="sd-profile-modal" class="sd-modal" style="display: none;">
    <div class="sd-modal-backdrop"></div>
    <div class="sd-modal-content">
        <div class="sd-modal-header">
            <h3><?php _e( 'Profil bearbeiten', 'spezialist-directory' ); ?></h3>
            <button type="button" class="sd-modal-close">&times;</button>
        </div>
        <div class="sd-modal-body">
            <form id="sd-profile-form" class="sd-edit-form">
                <div class="sd-form-section">
                    <div class="sd-form-row">
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-profile-first-name"><?php _e( 'Vorname', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                            <input type="text" id="sd-profile-first-name" name="first_name" required class="sd-form-input">
                        </div>
                        <div class="sd-form-group sd-form-half">
                            <label for="sd-profile-last-name"><?php _e( 'Nachname', 'spezialist-directory' ); ?></label>
                            <input type="text" id="sd-profile-last-name" name="last_name" class="sd-form-input">
                        </div>
                    </div>

                    <div class="sd-form-group">
                        <label for="sd-profile-email-input"><?php _e( 'E-Mail-Adresse', 'spezialist-directory' ); ?> <span class="required">*</span></label>
                        <input type="email" id="sd-profile-email-input" name="email" required class="sd-form-input">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="sd-form-actions">
                    <button type="button" class="sd-button sd-button-secondary sd-modal-cancel">
                        <?php _e( 'Abbrechen', 'spezialist-directory' ); ?>
                    </button>
                    <button type="submit" class="sd-button sd-button-primary" id="sd-profile-submit">
                        <span class="sd-btn-text"><?php _e( 'Speichern', 'spezialist-directory' ); ?></span>
                        <span class="sd-btn-loading" style="display: none;">
                            <span class="sd-spinner-small"></span>
                            <?php _e( 'Speichern...', 'spezialist-directory' ); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Template: Listing Grid
 *
 * Displays specialist listings in a grid layout with search and filter
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<?php
// Fetch categories (Bundesländer) for dropdown filter - only with listings
$all_categories = get_terms( array(
    'taxonomy'   => 'spezialist_category',
    'hide_empty' => true, // Only show categories with listings
    'orderby'    => 'name',
    'order'      => 'ASC',
) );

// Fetch tags for quick chips (top 8 by count)
$all_tags = get_terms( array(
    'taxonomy'   => 'spezialist_tag',
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
) );
$top_tags = ! is_wp_error( $all_tags ) ? array_slice( $all_tags, 0, 8 ) : array();

// Fetch ALL location terms with at least 1 listing (Nürnberg, Bezirke, Stadtteile)
$all_location_terms = get_terms( array(
    'taxonomy'   => 'spezialist_location',
    'hide_empty' => true, // Only show terms with listings
    'orderby'    => 'name',
    'order'      => 'ASC',
) );

// Create arrays for dropdown
$all_neighborhoods = array();
$neighborhood_names = array();
if ( ! is_wp_error( $all_location_terms ) ) {
    foreach ( $all_location_terms as $term ) {
        // Skip "Nürnberg" as it's the default "Ganz Nürnberg" option
        if ( $term->slug === 'nuernberg' ) {
            continue;
        }
        $all_neighborhoods[] = $term->slug;
        $neighborhood_names[ $term->slug ] = $term->name;
    }
}

// Check if any filters are active
$has_active_filters = ! empty( $_GET['sd_search'] ) ||
                      ! empty( $_GET['sd_category'] ) ||
                      ! empty( $_GET['sd_location'] ) ||
                      ! empty( $_GET['sd_tag'] ) ||
                      ! empty( $_GET['sd_premium'] ) ||
                      ! empty( $_GET['sd_min_rating'] ) ||
                      ( isset( $_GET['sd_orderby'] ) && $_GET['sd_orderby'] !== 'date_desc' );

$current_category = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
$current_location = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';
$current_tag = isset( $_GET['sd_tag'] ) ? sanitize_text_field( $_GET['sd_tag'] ) : '';
$current_orderby = isset( $_GET['sd_orderby'] ) ? sanitize_text_field( $_GET['sd_orderby'] ) : 'date_desc';
$current_min_rating = isset( $_GET['sd_min_rating'] ) ? floatval( $_GET['sd_min_rating'] ) : 0;
$current_per_page = isset( $_GET['sd_per_page'] ) ? intval( $_GET['sd_per_page'] ) : 12;
// Validate per_page to allowed values
if ( ! in_array( $current_per_page, array( 12, 50, 100 ), true ) ) {
    $current_per_page = 12;
}

// Generate dynamic H1 title based on active filters (SEO optimization)
$hero_title = __( 'Hofläden in Deiner Nähe finden!', 'spezialist-directory' );
if ( $current_category && $current_location ) {
    // Get category term name for display
    $cat_term = get_term_by( 'slug', $current_category, 'spezialist_category' );
    $cat_name = $cat_term ? $cat_term->name : ucfirst( $current_category );
    $hero_title = sprintf( __( '%s in %s entdecken!', 'spezialist-directory' ), esc_html( $cat_name ), esc_html( $current_location ) );
} elseif ( $current_category ) {
    // Get category term name for display
    $cat_term = get_term_by( 'slug', $current_category, 'spezialist_category' );
    $cat_name = $cat_term ? $cat_term->name : ucfirst( $current_category );
    $hero_title = sprintf( __( 'Hofläden in %s entdecken!', 'spezialist-directory' ), esc_html( $cat_name ) );
} elseif ( $current_location ) {
    $hero_title = sprintf( __( 'Hofläden in %s entdecken!', 'spezialist-directory' ), esc_html( $current_location ) );
}
?>

<div class="sd-listings-container">
    <!-- Hero Section with Background Image -->
    <div class="sd-hero-wrapper">
        <div class="sd-hero-overlay"></div>
        <h1 class="sd-hero-title"><?php echo $hero_title; ?></h1>
        <!-- Hero Search Section (Yelp-style) -->
        <div class="sd-hero-search">
        <form class="sd-hero-form" method="get" action="" id="sd-filter-form">
            <!-- Dual Search Fields -->
            <div class="sd-hero-search-bar">
                <div class="sd-hero-field sd-hero-field-what sd-autocomplete-wrapper">
                    <?php if ( $current_category ) :
                        $cat_term_obj = get_term_by( 'slug', $current_category, 'spezialist_category' );
                        $cat_display_name = $cat_term_obj ? $cat_term_obj->name : ucfirst( $current_category );
                    ?>
                    <div class="sd-category-tag" id="sd-category-tag">
                        <span class="sd-category-tag-text"><?php echo esc_html( $cat_display_name ); ?></span>
                        <a href="<?php echo esc_url( remove_query_arg( 'sd_category' ) ); ?>" class="sd-category-tag-remove" title="<?php esc_attr_e( 'Kategorie entfernen', 'spezialist-directory' ); ?>">×</a>
                    </div>
                    <?php endif; ?>
                    <input
                        type="text"
                        id="sd_search"
                        name="sd_search"
                        placeholder="<?php echo $current_category ? '' : esc_attr__( 'Hofladen, Produkt...', 'spezialist-directory' ); ?>"
                        value="<?php echo isset( $_GET['sd_search'] ) ? esc_attr( $_GET['sd_search'] ) : ''; ?>"
                        class="sd-hero-input<?php echo $current_category ? ' has-category-tag' : ''; ?>"
                        autocomplete="off"
                    >
                    <!-- Autocomplete Dropdown -->
                    <div class="sd-autocomplete-dropdown" id="sd-autocomplete" style="display: none;"></div>
                </div>
                <div class="sd-hero-divider"></div>
                <div class="sd-hero-field sd-hero-field-where">
                    <select id="sd_category_dropdown" name="sd_category" class="sd-hero-select">
                        <option value=""><?php _e( 'Ganz Deutschland', 'spezialist-directory' ); ?></option>
                        <?php if ( ! is_wp_error( $all_categories ) && ! empty( $all_categories ) ) :
                            foreach ( $all_categories as $category ) :
                                $selected = $current_category === $category->slug ? 'selected' : '';
                        ?>
                            <option value="<?php echo esc_attr( $category->slug ); ?>" <?php echo $selected; ?>>
                                <?php echo esc_html( $category->name ); ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <button type="submit" class="sd-hero-submit">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                    </svg>
                    <span class="sd-hero-submit-text"><?php _e( 'Suchen', 'spezialist-directory' ); ?></span>
                </button>
            </div>
        </form>

        <!-- Map Search Link & Mobile Filter Button -->
        <div class="sd-hero-map-link-wrapper">
            <a href="<?php echo esc_url( home_url( '/karte/' ) ); ?>" class="sd-hero-map-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z" fill="currentColor"/>
                </svg>
                <span><?php _e( 'Kartensuche', 'spezialist-directory' ); ?></span>
            </a>
            <?php
            // Mobile Filter Button - calculate active filter count
            $active_filter_count_hero = 0;
            if ( ! empty( $_GET['sd_search'] ) ) $active_filter_count_hero++;
            if ( ! empty( $current_category ) ) $active_filter_count_hero++;
            if ( ! empty( $current_location ) ) $active_filter_count_hero++;
            if ( ! empty( $current_tag ) ) $active_filter_count_hero++;
            if ( isset( $_GET['sd_premium'] ) && '1' === $_GET['sd_premium'] ) $active_filter_count_hero++;
            if ( isset( $_GET['sd_orderby'] ) && $_GET['sd_orderby'] !== 'date_desc' ) $active_filter_count_hero++;
            ?>
            <button type="button" class="sd-mobile-filter-btn" id="sd-mobile-filter-btn" aria-label="<?php esc_attr_e( 'Filter öffnen', 'spezialist-directory' ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z" fill="currentColor"/>
                </svg>
                <span><?php _e( 'Filter', 'spezialist-directory' ); ?></span>
                <?php if ( $active_filter_count_hero > 0 ) : ?>
                    <span class="sd-filter-count"><?php echo $active_filter_count_hero; ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Tag Quick Chips -->
        <?php if ( ! empty( $top_tags ) ) : ?>
            <div class="sd-chips-scroll-container">
                <button class="sd-chips-chevron sd-chips-chevron--left hidden" aria-label="<?php esc_attr_e( 'Nach links scrollen', 'spezialist-directory' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <div class="sd-chips-viewport">
                    <div class="sd-category-chips">
                        <span class="sd-chips-label"><?php _e( 'Beliebt:', 'spezialist-directory' ); ?></span>
                        <?php foreach ( $top_tags as $tag ) :
                            $chip_class = ( $current_tag === $tag->slug ) ? 'sd-chip active' : 'sd-chip';
                            $chip_url = add_query_arg( 'sd_tag', $tag->slug, strtok( $_SERVER['REQUEST_URI'], '?' ) );
                        ?>
                            <a href="<?php echo esc_url( $chip_url ); ?>" class="<?php echo esc_attr( $chip_class ); ?>">
                                <?php echo esc_html( $tag->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="sd-chips-chevron sd-chips-chevron--right" aria-label="<?php esc_attr_e( 'Nach rechts scrollen', 'spezialist-directory' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Bar -->
    <div class="sd-filter-bar">
        <div class="sd-filter-bar-left">
            <!-- Sort Dropdown -->
            <div class="sd-filter-dropdown">
                <label for="sd_orderby" class="sd-sr-only"><?php _e( 'Sortierung', 'spezialist-directory' ); ?></label>
                <select id="sd_orderby" name="sd_orderby" class="sd-filter-select sd-auto-submit">
                    <?php
                    $sort_options = array(
                        'date_desc'  => __( 'Neueste zuerst', 'spezialist-directory' ),
                        'date_asc'   => __( 'Älteste zuerst', 'spezialist-directory' ),
                        'popular'    => __( 'Beliebteste zuerst', 'spezialist-directory' ),
                        'title_asc'  => __( 'Name A-Z', 'spezialist-directory' ),
                        'title_desc' => __( 'Name Z-A', 'spezialist-directory' ),
                        'premium'    => __( 'Premium zuerst', 'spezialist-directory' ),
                        'random'     => __( 'Zufällig', 'spezialist-directory' ),
                    );
                    foreach ( $sort_options as $value => $label ) :
                        $selected = $current_orderby === $value ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Per Page Dropdown -->
            <div class="sd-filter-dropdown">
                <label for="sd_per_page" class="sd-sr-only"><?php _e( 'Pro Seite', 'spezialist-directory' ); ?></label>
                <select id="sd_per_page" name="sd_per_page" class="sd-filter-select sd-auto-submit">
                    <?php
                    $per_page_options = array(
                        12  => '12 ' . __( 'pro Seite', 'spezialist-directory' ),
                        50  => '50 ' . __( 'pro Seite', 'spezialist-directory' ),
                        100 => '100 ' . __( 'pro Seite', 'spezialist-directory' ),
                    );
                    foreach ( $per_page_options as $value => $label ) :
                        $selected = $current_per_page === $value ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Rating Filter Dropdown -->
            <div class="sd-filter-dropdown">
                <label for="sd_min_rating" class="sd-sr-only"><?php _e( 'Mindestbewertung', 'spezialist-directory' ); ?></label>
                <select id="sd_min_rating" name="sd_min_rating" class="sd-filter-select sd-auto-submit">
                    <option value=""><?php _e( 'Alle Bewertungen', 'spezialist-directory' ); ?></option>
                    <option value="4" <?php selected( $current_min_rating, 4 ); ?>>
                        <?php _e( '4+ Sterne', 'spezialist-directory' ); ?>
                    </option>
                    <option value="3" <?php selected( $current_min_rating, 3 ); ?>>
                        <?php _e( '3+ Sterne', 'spezialist-directory' ); ?>
                    </option>
                </select>
            </div>

            <!-- Premium Toggle (iOS-style switch) -->
            <label class="sd-premium-toggle">
                <input
                    type="checkbox"
                    name="sd_premium"
                    value="1"
                    class="sd-toggle-input sd-auto-submit"
                    <?php checked( isset( $_GET['sd_premium'] ) && '1' === $_GET['sd_premium'] ); ?>
                >
                <span class="sd-toggle-slider"></span>
                <span class="sd-toggle-label"><?php _e( 'Nur Premium', 'spezialist-directory' ); ?></span>
            </label>
        </div>

        <div class="sd-filter-bar-right">
            <!-- Active Filters Display -->
            <?php if ( $has_active_filters ) : ?>
                <div class="sd-active-filters">
                    <?php if ( ! empty( $_GET['sd_search'] ) ) : ?>
                        <span class="sd-active-chip">
                            "<?php echo esc_html( $_GET['sd_search'] ); ?>"
                            <a href="<?php echo esc_url( remove_query_arg( 'sd_search' ) ); ?>" class="sd-chip-remove">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ( ! empty( $current_category ) ) :
                        $cat_obj = get_term_by( 'slug', $current_category, 'spezialist_category' );
                        if ( $cat_obj ) :
                    ?>
                        <span class="sd-active-chip">
                            <?php echo esc_html( $cat_obj->name ); ?>
                            <a href="<?php echo esc_url( remove_query_arg( 'sd_category' ) ); ?>" class="sd-chip-remove">×</a>
                        </span>
                    <?php endif; endif; ?>
                    <?php if ( ! empty( $current_location ) ) :
                        // Get the location term name from slug
                        $location_term = get_term_by( 'slug', $current_location, 'spezialist_location' );
                        $location_display_name = $location_term ? $location_term->name : $current_location;
                    ?>
                        <span class="sd-active-chip">
                            <?php echo esc_html( $location_display_name ); ?>
                            <a href="<?php echo esc_url( remove_query_arg( 'sd_location' ) ); ?>" class="sd-chip-remove">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ( ! empty( $current_tag ) ) :
                        $tag_obj = get_term_by( 'slug', $current_tag, 'spezialist_tag' );
                        if ( $tag_obj ) :
                    ?>
                        <span class="sd-active-chip">
                            <?php echo esc_html( $tag_obj->name ); ?>
                            <a href="<?php echo esc_url( remove_query_arg( 'sd_tag' ) ); ?>" class="sd-chip-remove">×</a>
                        </span>
                    <?php endif; endif; ?>
                    <?php if ( $current_min_rating > 0 ) : ?>
                        <span class="sd-active-chip">
                            <?php echo esc_html( $current_min_rating ); ?>+ ★
                            <a href="<?php echo esc_url( remove_query_arg( 'sd_min_rating' ) ); ?>" class="sd-chip-remove">×</a>
                        </span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'], '?' ) ); ?>" class="sd-clear-filters">
                        <?php _e( 'Alle löschen', 'spezialist-directory' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div><!-- /.sd-hero-wrapper -->

    <!-- Results Bar with View Toggle -->
    <?php if ( $listings->have_posts() ) : ?>
        <div class="sd-results-bar">
            <p class="sd-results-count">
                <?php
                printf(
                    _n(
                        '%d Hofladen gefunden',
                        '%d Hofläden gefunden',
                        $listings->found_posts,
                        'spezialist-directory'
                    ),
                    $listings->found_posts
                );
                ?>
            </p>

            <!-- View Toggle Buttons -->
            <div class="sd-view-toggle" role="group" aria-label="<?php esc_attr_e( 'Ansicht wechseln', 'spezialist-directory' ); ?>">
                <button type="button"
                        class="sd-view-btn sd-view-btn-grid active"
                        data-view="grid"
                        aria-pressed="true"
                        title="<?php esc_attr_e( 'Rasteransicht', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3h8v8H3V3zm0 10h8v8H3v-8zm10-10h8v8h-8V3zm0 10h8v8h-8v-8z" fill="currentColor"/>
                    </svg>
                </button>
                <button type="button"
                        class="sd-view-btn sd-view-btn-list"
                        data-view="list"
                        aria-pressed="false"
                        title="<?php esc_attr_e( 'Listenansicht', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" fill="currentColor"/>
                    </svg>
                </button>
                <button type="button"
                        class="sd-view-btn sd-view-btn-map"
                        data-view="map"
                        aria-pressed="false"
                        title="<?php esc_attr_e( 'Kartenansicht', 'spezialist-directory' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Content with Optional Map Sidebar -->
        <div class="sd-listings-layout" id="sd-listings-layout">
            <!-- Listings Grid -->
            <div class="sd-listings-grid" id="sd-listings-grid">
            <?php while ( $listings->have_posts() ) : $listings->the_post();
                $post_id = get_the_ID();
                $is_premium = SD_Premium_Features::is_premium( $post_id );
                $is_verified = SD_Premium_Features::is_verified( $post_id );
                $phone = get_post_meta( $post_id, '_sd_phone', true );
                $email = get_post_meta( $post_id, '_sd_email', true );
                $website = get_post_meta( $post_id, '_sd_website', true );
                $city = get_post_meta( $post_id, '_sd_city', true );
                $neighborhood = get_post_meta( $post_id, '_sd_neighborhood', true );
                $address = get_post_meta( $post_id, '_sd_address', true );
                $latitude = get_post_meta( $post_id, '_sd_latitude', true );
                $longitude = get_post_meta( $post_id, '_sd_longitude', true );
                $categories = wp_get_object_terms( $post_id, 'spezialist_category' );
                $first_category = ! empty( $categories ) ? $categories[0]->name : '';

                // Get the most specific location term (child terms before parent terms)
                $location_terms = wp_get_object_terms( $post_id, 'spezialist_location', array( 'orderby' => 'term_id' ) );
                $display_location = '';
                $display_location_slug = '';
                if ( ! is_wp_error( $location_terms ) && ! empty( $location_terms ) ) {
                    // Find the most specific term (deepest in hierarchy = has a parent)
                    // Priority: Stadtteil (parent=Bezirk) > Bezirk (parent=Nürnberg) > Nürnberg
                    $best_term = null;
                    $best_depth = -1;
                    foreach ( $location_terms as $term ) {
                        // Calculate depth by counting ancestors
                        $depth = 0;
                        $parent_id = $term->parent;
                        while ( $parent_id > 0 ) {
                            $depth++;
                            $parent_term = get_term( $parent_id, 'spezialist_location' );
                            $parent_id = $parent_term ? $parent_term->parent : 0;
                        }
                        if ( $depth > $best_depth ) {
                            $best_depth = $depth;
                            $best_term = $term;
                        }
                    }
                    if ( $best_term ) {
                        $display_location = $best_term->name;
                        $display_location_slug = $best_term->slug;
                    }
                }
                // Fallback to meta fields if no taxonomy term found
                if ( empty( $display_location ) ) {
                    if ( $city ) {
                        $display_location = $city;
                    } elseif ( $neighborhood ) {
                        $display_location = $neighborhood;
                    }
                }

                // SEO-optimized alt text for listing images
                $listing_alt_text = sprintf(
                    '%s - %s%s',
                    get_the_title(),
                    $first_category ? $first_category . ' in ' : '',
                    $city ?: ''
                );
            ?>
                <article class="sd-listing-card <?php echo $is_premium ? 'sd-listing-premium' : ''; ?>"
                         data-post-id="<?php echo esc_attr( $post_id ); ?>"
                         <?php if ( $latitude && $longitude ) : ?>
                         data-lat="<?php echo esc_attr( $latitude ); ?>"
                         data-lng="<?php echo esc_attr( $longitude ); ?>"
                         <?php endif; ?>>
                    <?php
                    /**
                     * Hook: sd_listing_card_rating
                     * Used by spezialist-ratings plugin to display rating badge
                     *
                     * @param int $post_id The listing post ID
                     */
                    do_action( 'sd_listing_card_rating', $post_id );
                    ?>

                    <!-- Bookmark Button -->
                    <button type="button"
                            class="sd-bookmark-btn"
                            data-post-id="<?php echo esc_attr( $post_id ); ?>"
                            title="<?php esc_attr_e( 'Zur Merkliste hinzufügen', 'spezialist-directory' ); ?>">
                        <svg class="sd-bookmark-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2zm0 15l-5-2.18L7 18V5h10v13z" fill="currentColor"/>
                        </svg>
                        <svg class="sd-bookmark-icon-filled" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" fill="currentColor"/>
                        </svg>
                    </button>

                    <div class="sd-listing-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', array(
                                    'class'   => 'sd-listing-thumbnail',
                                    'loading' => 'lazy',
                                    'alt'     => esc_attr( $listing_alt_text ),
                                ) ); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url( home_url( '/wp-content/uploads/2025/12/placeholder.webp' ) ); ?>"
                                     alt="<?php echo esc_attr( $listing_alt_text ); ?>"
                                     class="sd-listing-thumbnail"
                                     loading="lazy" />
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="sd-listing-content">
                        <h3 class="sd-listing-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <!-- Badges row: Verified + Premium -->
                        <?php if ( $is_verified || $is_premium ) : ?>
                        <div class="sd-listing-badges">
                            <?php if ( $is_verified ) : ?>
                                <?php echo SD_Premium_Features::get_verified_badge( $post_id ); ?>
                            <?php endif; ?>
                            <?php if ( $is_premium ) : ?>
                                <div class="sd-premium-badge-container">
                                    <?php echo SD_Premium_Features::get_premium_badge( $post_id ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Meta row: Location (most specific: Stadtteil > Bezirk > Nürnberg) -->
                        <div class="sd-listing-meta">
                            <?php if ( $display_location ) :
                                // Use slug if available, otherwise use name
                                $location_filter = $display_location_slug ? $display_location_slug : $display_location;
                                $location_url = add_query_arg( 'sd_location', urlencode( $location_filter ), home_url( '/' ) );
                            ?>
                                <a href="<?php echo esc_url( $location_url ); ?>" class="sd-listing-meta-location" title="<?php printf( esc_attr__( 'Alle Hofläden in %s anzeigen', 'spezialist-directory' ), $display_location ); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                                    </svg>
                                    <?php echo esc_html( $display_location ); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- All categories row -->
                        <div class="sd-listing-categories">
                            <?php if ( ! empty( $categories ) ) : ?>
                                <?php foreach ( $categories as $category ) :
                                    $category_url = add_query_arg( 'sd_category', urlencode( $category->slug ), home_url( '/' ) );
                                ?>
                                    <a href="<?php echo esc_url( $category_url ); ?>" class="sd-category-badge" title="<?php printf( esc_attr__( 'Alle %s anzeigen', 'spezialist-directory' ), $category->name ); ?>"><?php echo esc_html( $category->name ); ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="sd-listing-excerpt">
                            <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="sd-listing-actions">
                            <?php if ( $phone ) : ?>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Anrufen', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if ( $website ) : ?>
                                <a href="<?php echo esc_url( $website ); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Website', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if ( $address && $city ) : ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode( $address . ', ' . $city ); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="sd-action-btn"
                                   title="<?php esc_attr_e( 'Route', 'spezialist-directory' ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z" fill="currentColor"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <a href="<?php the_permalink(); ?>" class="sd-action-btn sd-action-btn-primary" title="<?php esc_attr_e( 'Details', 'spezialist-directory' ); ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
            </div>

            <!-- Map Sidebar (shown when map view is active) -->
            <aside class="sd-map-sidebar" id="sd-map-sidebar">
                <div class="sd-map-container" id="sd-map-container">
                    <div id="sd-map" class="sd-map"></div>
                    <div class="sd-map-controls">
                        <button type="button" class="sd-map-control sd-map-zoom-in" title="<?php esc_attr_e( 'Vergrößern', 'spezialist-directory' ); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/></svg>
                        </button>
                        <button type="button" class="sd-map-control sd-map-zoom-out" title="<?php esc_attr_e( 'Verkleinern', 'spezialist-directory' ); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 13H5v-2h14v2z" fill="currentColor"/></svg>
                        </button>
                        <button type="button" class="sd-map-control sd-map-fit" title="<?php esc_attr_e( 'Alle anzeigen', 'spezialist-directory' ); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 3l2.3 2.3-2.89 2.87 1.42 1.42L18.7 6.7 21 9V3h-6zM3 9l2.3-2.3 2.87 2.89 1.42-1.42L6.7 5.3 9 3H3v6zm6 12l-2.3-2.3 2.89-2.87-1.42-1.42L5.3 17.3 3 15v6h6zm12-6l-2.3 2.3-2.87-2.89-1.42 1.42 2.89 2.87L15 21h6v-6z" fill="currentColor"/></svg>
                        </button>
                    </div>
                    <div class="sd-map-overlay sd-map-loading" id="sd-map-loading">
                        <div class="sd-map-spinner"></div>
                        <span><?php _e( 'Karte wird geladen...', 'spezialist-directory' ); ?></span>
                    </div>
                </div>
            </aside>
        </div><!-- /.sd-listings-layout -->

        <!-- Pagination -->
        <?php if ( $listings->max_num_pages > 1 ) :
            // Build pagination base URL with query string (most reliable method)
            // Preserve existing filter parameters
            $current_url = home_url( '/' );
            $query_params = array();

            // Preserve filter parameters
            if ( ! empty( $_GET['sd_search'] ) ) {
                $query_params['sd_search'] = sanitize_text_field( $_GET['sd_search'] );
            }
            if ( ! empty( $_GET['sd_category'] ) ) {
                $query_params['sd_category'] = sanitize_text_field( $_GET['sd_category'] );
            }
            if ( ! empty( $_GET['sd_location'] ) ) {
                $query_params['sd_location'] = sanitize_text_field( $_GET['sd_location'] );
            }
            if ( ! empty( $_GET['sd_premium'] ) ) {
                $query_params['sd_premium'] = '1';
            }
            if ( ! empty( $_GET['sd_orderby'] ) ) {
                $query_params['sd_orderby'] = sanitize_text_field( $_GET['sd_orderby'] );
            }

            // Build base URL with existing params
            if ( ! empty( $query_params ) ) {
                $pagination_base = add_query_arg( $query_params, $current_url );
                $pagination_base = add_query_arg( 'paged', '%#%', $pagination_base );
            } else {
                $pagination_base = add_query_arg( 'paged', '%#%', $current_url );
            }
        ?>
            <div class="sd-pagination">
                <?php
                echo paginate_links( array(
                    'total'     => $listings->max_num_pages,
                    'current'   => $paged,
                    'base'      => $pagination_base,
                    'format'    => '',
                    'prev_text' => __( '&laquo; Zurück', 'spezialist-directory' ),
                    'next_text' => __( 'Weiter &raquo;', 'spezialist-directory' ),
                    'type'      => 'list',
                ) );
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="sd-no-results">
            <div class="sd-no-results-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/>
                </svg>
            </div>
            <h3><?php _e( 'Keine Hofläden gefunden', 'spezialist-directory' ); ?></h3>
            <p><?php _e( 'Versuch es mit anderen Suchbegriffen oder Filtern.', 'spezialist-directory' ); ?></p>
            <a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'], '?' ) ); ?>" class="sd-button sd-button-primary">
                <?php _e( 'Filter zurücksetzen', 'spezialist-directory' ); ?>
            </a>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

    <!-- Mobile Filter Drawer Backdrop -->
    <div class="sd-filter-drawer-backdrop" id="sd-filter-drawer-backdrop"></div>

    <!-- Mobile Filter Drawer -->
    <div class="sd-filter-drawer" id="sd-filter-drawer" role="dialog" aria-modal="true" aria-labelledby="sd-drawer-title">
        <div class="sd-drawer-handle"></div>

        <div class="sd-drawer-header">
            <h2 class="sd-drawer-title" id="sd-drawer-title"><?php _e( 'Filter & Sortierung', 'spezialist-directory' ); ?></h2>
            <button type="button" class="sd-drawer-close" id="sd-drawer-close" aria-label="<?php esc_attr_e( 'Schließen', 'spezialist-directory' ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/>
                </svg>
            </button>
        </div>

        <div class="sd-drawer-content">
            <!-- Bundesland Section -->
            <div class="sd-drawer-section">
                <div class="sd-drawer-section-title"><?php _e( 'Bundesland', 'spezialist-directory' ); ?></div>
                <select id="sd-drawer-category" class="sd-drawer-select">
                    <option value=""><?php _e( 'Ganz Deutschland', 'spezialist-directory' ); ?></option>
                    <?php if ( ! is_wp_error( $all_categories ) && ! empty( $all_categories ) ) :
                        foreach ( $all_categories as $category ) :
                            $selected = $current_category === $category->slug ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr( $category->slug ); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html( $category->name ); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <!-- Sort Section -->
            <div class="sd-drawer-section">
                <div class="sd-drawer-section-title"><?php _e( 'Sortierung', 'spezialist-directory' ); ?></div>
                <select id="sd-drawer-orderby" class="sd-drawer-select">
                    <?php
                    $drawer_sort_options = array(
                        'date_desc'  => __( 'Neueste zuerst', 'spezialist-directory' ),
                        'date_asc'   => __( 'Älteste zuerst', 'spezialist-directory' ),
                        'popular'    => __( 'Beliebteste zuerst', 'spezialist-directory' ),
                        'title_asc'  => __( 'Name A-Z', 'spezialist-directory' ),
                        'title_desc' => __( 'Name Z-A', 'spezialist-directory' ),
                        'premium'    => __( 'Premium zuerst', 'spezialist-directory' ),
                        'random'     => __( 'Zufällig', 'spezialist-directory' ),
                    );
                    foreach ( $drawer_sort_options as $value => $label ) :
                        $selected = $current_orderby === $value ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Premium Toggle Section -->
            <div class="sd-drawer-section">
                <div class="sd-drawer-section-title"><?php _e( 'Premium', 'spezialist-directory' ); ?></div>
                <label class="sd-drawer-toggle">
                    <span class="sd-drawer-toggle-label"><?php _e( 'Nur Premium Hofläden', 'spezialist-directory' ); ?></span>
                    <div class="sd-premium-toggle">
                        <input
                            type="checkbox"
                            id="sd-drawer-premium"
                            class="sd-toggle-input"
                            <?php checked( isset( $_GET['sd_premium'] ) && '1' === $_GET['sd_premium'] ); ?>
                        >
                        <span class="sd-toggle-slider"></span>
                    </div>
                </label>
            </div>
        </div>

        <div class="sd-drawer-footer">
            <button type="button" class="sd-drawer-btn sd-drawer-btn-reset" id="sd-drawer-reset">
                <?php _e( 'Zurücksetzen', 'spezialist-directory' ); ?>
            </button>
            <button type="button" class="sd-drawer-btn sd-drawer-btn-apply" id="sd-drawer-apply">
                <?php _e( 'Anwenden', 'spezialist-directory' ); ?>
            </button>
        </div>
    </div>
</div>

<?php
/**
 * AJAX Filter Handler
 *
 * Handles AJAX requests for filtering listings without page reload
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SD_Ajax_Filter
 *
 * Provides real-time filtering of specialist listings via AJAX
 */
class SD_Ajax_Filter {

    /**
     * Singleton instance
     *
     * @var SD_Ajax_Filter
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return SD_Ajax_Filter
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers for logged-in and guest users
        add_action( 'wp_ajax_sd_filter_listings', array( $this, 'handle_filter_request' ) );
        add_action( 'wp_ajax_nopriv_sd_filter_listings', array( $this, 'handle_filter_request' ) );

        // Favorites/Merkliste AJAX handler
        add_action( 'wp_ajax_sd_get_favorites_data', array( $this, 'handle_get_favorites_data' ) );
        add_action( 'wp_ajax_nopriv_sd_get_favorites_data', array( $this, 'handle_get_favorites_data' ) );

        // Autocomplete AJAX handler
        add_action( 'wp_ajax_sd_autocomplete', array( $this, 'handle_autocomplete_request' ) );
        add_action( 'wp_ajax_nopriv_sd_autocomplete', array( $this, 'handle_autocomplete_request' ) );

        // Favorites page shortcode
        add_shortcode( 'spezialist_favorites', array( $this, 'render_favorites_page' ) );

        // Extend search to include services meta field
        add_filter( 'posts_search', array( $this, 'extend_search_to_services' ), 10, 2 );
    }

    /**
     * Extend WordPress search to include services meta field
     *
     * This allows searching for listings by their offered services
     *
     * @param string $search The search SQL clause
     * @param WP_Query $query The query object
     * @return string Modified search SQL
     */
    public function extend_search_to_services( $search, $query ) {
        global $wpdb;

        // Only modify for spezialist post type searches
        if ( ! $query->is_search() || 'hofladen' !== $query->get( 'post_type' ) ) {
            return $search;
        }

        $search_term = $query->get( 's' );
        if ( empty( $search_term ) ) {
            return $search;
        }

        // Get post IDs that have matching services
        $service_post_ids = $this->get_posts_by_service( $search_term );

        if ( empty( $service_post_ids ) ) {
            return $search;
        }

        // Add OR condition to include posts with matching services
        $post_ids_str = implode( ',', array_map( 'intval', $service_post_ids ) );
        $search = preg_replace(
            "/\({$wpdb->posts}.post_title/",
            "({$wpdb->posts}.ID IN ({$post_ids_str}) OR {$wpdb->posts}.post_title",
            $search,
            1
        );

        return $search;
    }

    /**
     * Get post IDs that have a service matching the search term
     *
     * @param string $search_term The search term
     * @return array Array of post IDs
     */
    private function get_posts_by_service( $search_term ) {
        global $wpdb;

        // Search for posts where _sd_services contains the search term
        // Note: _sd_services is stored as serialized array, so we use LIKE
        $like_term = '%' . $wpdb->esc_like( $search_term ) . '%';

        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_sd_services'
                AND meta_value LIKE %s",
                $like_term
            )
        );

        return $post_ids ? $post_ids : array();
    }

    /**
     * Get post IDs within geographic bounds
     *
     * @param float $north Northern latitude boundary
     * @param float $south Southern latitude boundary
     * @param float $east Eastern longitude boundary
     * @param float $west Western longitude boundary
     * @return array Array of post IDs within bounds
     */
    private function get_posts_in_bounds( $north, $south, $east, $west ) {
        global $wpdb;

        // Query posts where lat/lng are within bounds
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} lat_meta ON p.ID = lat_meta.post_id AND lat_meta.meta_key = '_sd_latitude'
                INNER JOIN {$wpdb->postmeta} lng_meta ON p.ID = lng_meta.post_id AND lng_meta.meta_key = '_sd_longitude'
                WHERE p.post_type = 'hofladen'
                AND p.post_status = 'publish'
                AND CAST(lat_meta.meta_value AS DECIMAL(10, 7)) BETWEEN %f AND %f
                AND CAST(lng_meta.meta_value AS DECIMAL(10, 7)) BETWEEN %f AND %f",
                $south,
                $north,
                $west,
                $east
            )
        );

        return $post_ids ? $post_ids : array();
    }

    /**
     * Handle AJAX filter request
     */
    public function handle_filter_request() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_filter_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) ) );
        }

        // Get filter parameters
        $search     = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $category   = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';
        $tag        = isset( $_POST['tag'] ) ? sanitize_text_field( $_POST['tag'] ) : '';
        $location   = isset( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '';
        $premium    = isset( $_POST['premium'] ) && '1' === $_POST['premium'];
        $min_rating = isset( $_POST['min_rating'] ) ? floatval( $_POST['min_rating'] ) : 0;
        $orderby    = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'date_desc';
        $paged      = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        $per_page   = Spezialist_Directory::get_option( 'listings_per_page', 12 );

        // Map bounds filter (for Kartensuche)
        $bounds_north = isset( $_POST['bounds_north'] ) ? floatval( $_POST['bounds_north'] ) : null;
        $bounds_south = isset( $_POST['bounds_south'] ) ? floatval( $_POST['bounds_south'] ) : null;
        $bounds_east  = isset( $_POST['bounds_east'] ) ? floatval( $_POST['bounds_east'] ) : null;
        $bounds_west  = isset( $_POST['bounds_west'] ) ? floatval( $_POST['bounds_west'] ) : null;

        // Override per_page if provided via AJAX
        if ( isset( $_POST['per_page'] ) ) {
            $requested_per_page = absint( $_POST['per_page'] );
            if ( in_array( $requested_per_page, array( 12, 50, 100 ), true ) ) {
                $per_page = $requested_per_page;
            }
        }

        // For map bounds queries, load more results to show all markers in view
        // Limit to 200 for performance
        $is_bounds_query = ( $bounds_north !== null && $bounds_south !== null && $bounds_east !== null && $bounds_west !== null );
        if ( $is_bounds_query ) {
            $per_page = 200; // Load up to 200 results within bounds
        }

        // Build query args
        $query_args = array(
            'post_type'      => 'hofladen',
            'posts_per_page' => $per_page,
            'paged'          => $is_bounds_query ? 1 : $paged, // No pagination for bounds queries
            'post_status'    => 'publish',
        );

        // Tax query
        $tax_query = array();

        if ( ! empty( $category ) ) {
            $tax_query[] = array(
                'taxonomy' => 'spezialist_category',
                'field'    => 'slug',
                'terms'    => $category,
            );
        }

        if ( ! empty( $location ) ) {
            // Check if this location exists as a taxonomy term
            $location_term = get_term_by( 'slug', $location, 'spezialist_location' );
            if ( ! $location_term ) {
                $location_term = get_term_by( 'name', $location, 'spezialist_location' );
            }

            if ( $location_term ) {
                $tax_query[] = array(
                    'taxonomy'         => 'spezialist_location',
                    'field'            => 'slug',
                    'terms'            => $location_term->slug,
                    'include_children' => true,
                );
            } else {
                // Fallback: search in meta fields
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_sd_city',
                        'value'   => $location,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key'     => '_sd_neighborhood',
                        'value'   => $location,
                        'compare' => 'LIKE',
                    ),
                );
            }
        }

        if ( ! empty( $tag ) ) {
            $tax_query[] = array(
                'taxonomy' => 'spezialist_tag',
                'field'    => 'slug',
                'terms'    => $tag,
            );
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        // Search query
        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }

        // Meta query for filters
        $meta_query = array();

        // Exclude paused listings from public search
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sd_paused',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_sd_paused',
                'value'   => '1',
                'compare' => '!=',
            ),
        );

        // Premium filter
        if ( $premium ) {
            $meta_query[] = array(
                'key'     => '_sd_is_premium',
                'value'   => '1',
                'compare' => '=',
            );
        }

        // Rating filter - get post IDs with minimum rating
        if ( $min_rating > 0 && class_exists( 'SR_Ratings' ) ) {
            $rated_post_ids = SR_Ratings::get_posts_with_min_rating( $min_rating );
            if ( ! empty( $rated_post_ids ) ) {
                $query_args['post__in'] = $rated_post_ids;
            } else {
                // No posts match the rating criteria - return empty
                $query_args['post__in'] = array( 0 );
            }
        }

        // Geo bounds filter - get post IDs within map bounds
        if ( $bounds_north !== null && $bounds_south !== null && $bounds_east !== null && $bounds_west !== null ) {
            $geo_post_ids = $this->get_posts_in_bounds( $bounds_north, $bounds_south, $bounds_east, $bounds_west );
            if ( ! empty( $geo_post_ids ) ) {
                // Intersect with existing post__in if set
                if ( isset( $query_args['post__in'] ) ) {
                    $query_args['post__in'] = array_intersect( $query_args['post__in'], $geo_post_ids );
                    if ( empty( $query_args['post__in'] ) ) {
                        $query_args['post__in'] = array( 0 );
                    }
                } else {
                    $query_args['post__in'] = $geo_post_ids;
                }
            } else {
                // No posts in bounds - return empty
                $query_args['post__in'] = array( 0 );
            }
        }

        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = $meta_query;
        }

        // Sorting
        switch ( $orderby ) {
            case 'date_desc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
            case 'date_asc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'ASC';
                break;
            case 'title_asc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'title_desc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'DESC';
                break;
            case 'premium':
                // Sort premium listings first using custom SQL join
                add_filter('posts_clauses', array($this, 'sort_by_premium_clauses'), 10, 2);
                $query_args['orderby'] = 'premium_sort';
                $query_args['order'] = 'DESC';
                break;
            case 'popular':
                // Sort by views count using custom SQL join
                add_filter('posts_clauses', array($this, 'sort_by_views_clauses'), 10, 2);
                $query_args['orderby'] = 'views_sort';
                $query_args['order'] = 'DESC';
                break;
            case 'random':
                $query_args['orderby'] = 'rand';
                break;
        }

        // Run query
        $listings = new WP_Query( $query_args );

        // Prepare response
        $html = '';
        $cards = array();

        if ( $listings->have_posts() ) {
            while ( $listings->have_posts() ) {
                $listings->the_post();
                $cards[] = $this->render_card( get_the_ID() );
            }
            $html = implode( '', $cards );
        }

        wp_reset_postdata();

        // Build pagination HTML
        $pagination_html = '';
        if ( $listings->max_num_pages > 1 ) {
            $pagination_html = $this->render_pagination( $listings->max_num_pages, $paged );
        }

        wp_send_json_success( array(
            'html'        => $html,
            'pagination'  => $pagination_html,
            'found_posts' => $listings->found_posts,
            'max_pages'   => $listings->max_num_pages,
            'cards'       => $cards, // For map markers update
        ) );
    }

    /**
     * Render a single card
     *
     * @param int $post_id Post ID
     * @return string Card HTML
     */
    private function render_card( $post_id ) {
        $is_premium  = SD_Premium_Features::is_premium( $post_id );
        $is_verified = SD_Premium_Features::is_verified( $post_id );
        $phone       = get_post_meta( $post_id, '_sd_phone', true );
        $email      = get_post_meta( $post_id, '_sd_email', true );
        $website    = get_post_meta( $post_id, '_sd_website', true );
        $city         = get_post_meta( $post_id, '_sd_city', true );
        $address      = get_post_meta( $post_id, '_sd_address', true );

        // Get the most specific location term (Stadtteil > Bezirk > Nürnberg)
        $location_terms = wp_get_object_terms( $post_id, 'spezialist_location', array( 'orderby' => 'term_id' ) );
        $display_location = '';
        $display_location_slug = '';
        if ( ! is_wp_error( $location_terms ) && ! empty( $location_terms ) ) {
            $best_term = null;
            $best_depth = -1;
            foreach ( $location_terms as $term ) {
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
        $latitude   = get_post_meta( $post_id, '_sd_latitude', true );
        $longitude  = get_post_meta( $post_id, '_sd_longitude', true );
        $categories = wp_get_object_terms( $post_id, 'spezialist_category' );
        $first_category = ! empty( $categories ) ? $categories[0]->name : '';
        $permalink  = get_permalink( $post_id );
        $title      = get_the_title( $post_id );
        $excerpt    = wp_trim_words( get_the_excerpt( $post_id ), 15 );

        // Get thumbnail
        $thumbnail = '';
        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail = get_the_post_thumbnail( $post_id, 'medium', array(
                'class'   => 'sd-listing-thumbnail',
                'loading' => 'lazy'
            ) );
        } else {
            $thumbnail = '<img src="' . esc_url( home_url( '/wp-content/uploads/2025/12/placeholder.webp' ) ) . '" alt="' . esc_attr__( 'Platzhalterbild', 'spezialist-directory' ) . '" class="sd-listing-thumbnail" loading="lazy" />';
        }

        ob_start();
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
                <a href="<?php echo esc_url( $permalink ); ?>">
                    <?php echo $thumbnail; ?>
                </a>
            </div>

            <div class="sd-listing-content">
                <h3 class="sd-listing-title">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                </h3>

                <?php if ( $city ) : ?>
                <!-- City row -->
                <div class="sd-listing-city">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                    </svg>
                    <span><?php echo esc_html( $city ); ?></span>
                </div>
                <?php endif; ?>

                <!-- Meta row: Location (Stadtteil from taxonomy) -->
                <div class="sd-listing-meta">
                    <?php if ( $display_location ) :
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
                    <?php if ( $is_verified ) : ?>
                        <?php echo SD_Premium_Features::get_verified_badge( $post_id ); ?>
                    <?php endif; ?>
                    <?php if ( $is_premium ) : ?>
                        <div class="sd-premium-badge-container">
                            <?php echo SD_Premium_Features::get_premium_badge( $post_id ); ?>
                        </div>
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
                    <?php echo esc_html( $excerpt ); ?>
                </div>

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
                    <a href="<?php echo esc_url( $permalink ); ?>" class="sd-action-btn sd-action-btn-primary" title="<?php esc_attr_e( 'Details', 'spezialist-directory' ); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" fill="currentColor"/>
                        </svg>
                    </a>
                </div>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render pagination
     *
     * @param int $max_pages Maximum number of pages
     * @param int $current Current page
     * @return string Pagination HTML
     */
    private function render_pagination( $max_pages, $current ) {
        $pagination = paginate_links( array(
            'total'     => $max_pages,
            'current'   => $current,
            'prev_text' => __( '&laquo; Zurück', 'spezialist-directory' ),
            'next_text' => __( 'Weiter &raquo;', 'spezialist-directory' ),
            'type'      => 'array',
        ) );

        if ( ! $pagination ) {
            return '';
        }

        $html = '<ul class="page-numbers">';
        foreach ( $pagination as $link ) {
            // Make pagination links clickable via JS
            $link = preg_replace( '/href=["\']([^"\']+)["\']/', 'href="#" data-page="$1"', $link );
            // Extract page number from URL
            preg_match( '/page\/(\d+)|paged=(\d+)/', $link, $matches );
            $page_num = isset( $matches[1] ) && $matches[1] ? $matches[1] : ( isset( $matches[2] ) ? $matches[2] : 1 );
            $link = preg_replace( '/data-page="[^"]*"/', 'data-page="' . $page_num . '"', $link );
            $html .= '<li>' . $link . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Render favorites page shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_favorites_page( $atts ) {
        ob_start();
        include SD_PLUGIN_DIR . 'templates/favorites-page.php';
        return ob_get_clean();
    }

    /**
     * Handle AJAX request to get favorites data
     *
     * Returns listing data for post IDs stored in localStorage
     */
    public function handle_get_favorites_data() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) ) );
        }

        // Get post IDs
        $post_ids = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : array();

        if ( empty( $post_ids ) ) {
            wp_send_json_success( array( 'listings' => array() ) );
        }

        // Query posts
        $args = array(
            'post_type'      => 'hofladen',
            'post__in'       => $post_ids,
            'posts_per_page' => count( $post_ids ),
            'post_status'    => 'publish',
            'orderby'        => 'post__in', // Preserve order from localStorage
        );

        $query = new WP_Query( $args );
        $listings = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                // Get meta data
                $city = get_post_meta( $post_id, '_sd_city', true );
                $categories = wp_get_object_terms( $post_id, 'spezialist_category' );
                $first_category = ! empty( $categories ) ? $categories[0]->name : '';
                $all_categories = ! empty( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();

                // Get thumbnail
                $thumbnail = '';
                if ( has_post_thumbnail( $post_id ) ) {
                    $thumbnail = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
                } else {
                    $thumbnail = home_url( '/wp-content/uploads/2025/12/placeholder.webp' );
                }

                // Additional fields for compare feature
                $address = get_post_meta( $post_id, '_sd_address', true );
                $zip = get_post_meta( $post_id, '_sd_zip', true );
                $phone = get_post_meta( $post_id, '_sd_phone', true );
                $services = get_post_meta( $post_id, '_sd_services', true );
                $verified = get_post_meta( $post_id, '_sd_verified_listing', true ) === '1';
                $premium = get_post_meta( $post_id, '_sd_is_premium', true ) === '1';

                // Get rating data from spezialist-ratings plugin
                $rating = null;
                $rating_count = 0;
                if ( class_exists( 'SR_Ratings' ) ) {
                    $rating = SR_Ratings::get_average( $post_id );
                    $rating_count = SR_Ratings::get_count( $post_id );
                }

                // Get excerpt/description
                $description = get_the_excerpt();
                if ( empty( $description ) ) {
                    $description = wp_trim_words( get_the_content(), 20, '...' );
                }

                $listings[] = array(
                    'id'           => $post_id,
                    'title'        => get_the_title(),
                    'url'          => get_permalink(),
                    'thumbnail'    => $thumbnail,
                    'category'     => $first_category,
                    'categories'   => $all_categories,
                    'city'         => $city,
                    'address'      => $address,
                    'zip'          => $zip,
                    'phone'        => $phone,
                    'description'  => $description,
                    'services'     => is_array( $services ) ? $services : array(),
                    'verified'     => $verified,
                    'premium'      => $premium,
                    'rating'       => $rating,
                    'rating_count' => $rating_count,
                );
            }
        }

        wp_reset_postdata();

        wp_send_json_success( array( 'listings' => $listings ) );
    }

    /**
     * Handle AJAX autocomplete request
     *
     * Returns matching categories and listings for search term
     */
    public function handle_autocomplete_request() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_filter_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' ) ) );
        }

        // Get search term
        $term = isset( $_POST['term'] ) ? sanitize_text_field( $_POST['term'] ) : '';

        // Require minimum 4 characters
        if ( strlen( $term ) < 4 ) {
            wp_send_json_success( array( 'categories' => array(), 'listings' => array() ) );
        }

        // Search categories
        $categories = get_terms( array(
            'taxonomy'   => 'spezialist_category',
            'name__like' => $term,
            'hide_empty' => true,
            'number'     => 5,
        ) );

        $category_results = array();
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                $category_results[] = array(
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                );
            }
        }

        // Search listings (exclude paused)
        $listings_query = new WP_Query( array(
            'post_type'      => 'hofladen',
            'posts_per_page' => 5,
            's'              => $term,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_sd_paused',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_sd_paused',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        ) );

        $listing_results = array();
        if ( $listings_query->have_posts() ) {
            while ( $listings_query->have_posts() ) {
                $listings_query->the_post();
                $listing_results[] = array(
                    'title' => get_the_title(),
                    'id'    => get_the_ID(),
                );
            }
        }
        wp_reset_postdata();

        wp_send_json_success( array(
            'categories' => $category_results,
            'listings'   => $listing_results,
        ) );
    }

    /**
     * Modify query clauses to sort by premium status
     * Uses LEFT JOIN to include all posts, with non-premium defaulting to 0
     *
     * @param array    $clauses Query clauses
     * @param WP_Query $query   Query object
     * @return array Modified clauses
     */
    public function sort_by_premium_clauses( $clauses, $query ) {
        global $wpdb;

        // Add LEFT JOIN for premium meta
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS premium_meta ON ({$wpdb->posts}.ID = premium_meta.post_id AND premium_meta.meta_key = '_sd_is_premium')";

        // Add to fields for sorting (COALESCE defaults NULL to 0)
        $clauses['fields'] .= ", COALESCE(premium_meta.meta_value, '0') AS premium_sort";

        // Order by premium first (1 before 0), then by date
        $clauses['orderby'] = "premium_sort DESC, {$wpdb->posts}.post_date DESC";

        // Remove filter after use
        remove_filter('posts_clauses', array($this, 'sort_by_premium_clauses'), 10);

        return $clauses;
    }

    /**
     * Modify query clauses to sort by views count
     * Uses LEFT JOIN to include all posts, with no views defaulting to 0
     *
     * @param array    $clauses Query clauses
     * @param WP_Query $query   Query object
     * @return array Modified clauses
     */
    public function sort_by_views_clauses( $clauses, $query ) {
        global $wpdb;

        // Add LEFT JOIN for views meta
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS views_meta ON ({$wpdb->posts}.ID = views_meta.post_id AND views_meta.meta_key = '_sd_views_count')";

        // Add to fields for sorting (COALESCE defaults NULL to 0)
        $clauses['fields'] .= ", COALESCE(CAST(views_meta.meta_value AS UNSIGNED), 0) AS views_sort";

        // Order by views count descending, then by date
        $clauses['orderby'] = "views_sort DESC, {$wpdb->posts}.post_date DESC";

        // Remove filter after use
        remove_filter('posts_clauses', array($this, 'sort_by_views_clauses'), 10);

        return $clauses;
    }
}

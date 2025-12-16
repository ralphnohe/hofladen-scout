<?php
/**
 * Custom Post Type: Spezialist
 *
 * Registers the "spezialist" custom post type
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_CPT_Spezialist Class
 */
class SD_CPT_Spezialist {

    /**
     * Single instance
     *
     * @var SD_CPT_Spezialist
     */
    protected static $_instance = null;

    /**
     * Post type slug
     *
     * @var string
     */
    const POST_TYPE = 'spezialist';

    /**
     * Main Instance
     *
     * @return SD_CPT_Spezialist
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Post type is registered directly from main plugin init() method
        // to ensure proper timing with taxonomy registration
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_filter( 'template_include', array( $this, 'load_single_template' ) );
        add_filter( 'post_type_link', array( $this, 'custom_permalink' ), 10, 2 );
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Hofläden', 'Post Type General Name', 'spezialist-directory' ),
            'singular_name'         => _x( 'Hofladen', 'Post Type Singular Name', 'spezialist-directory' ),
            'menu_name'             => __( 'Hofläden', 'spezialist-directory' ),
            'name_admin_bar'        => __( 'Hofladen', 'spezialist-directory' ),
            'archives'              => __( 'Hofladen Archiv', 'spezialist-directory' ),
            'attributes'            => __( 'Hofladen Attribute', 'spezialist-directory' ),
            'parent_item_colon'     => __( 'Übergeordneter Hofladen:', 'spezialist-directory' ),
            'all_items'             => __( 'Alle Hofläden', 'spezialist-directory' ),
            'add_new_item'          => __( 'Neuen Hofladen hinzufügen', 'spezialist-directory' ),
            'add_new'               => __( 'Neu hinzufügen', 'spezialist-directory' ),
            'new_item'              => __( 'Neuer Hofladen', 'spezialist-directory' ),
            'edit_item'             => __( 'Hofladen bearbeiten', 'spezialist-directory' ),
            'update_item'           => __( 'Hofladen aktualisieren', 'spezialist-directory' ),
            'view_item'             => __( 'Hofladen ansehen', 'spezialist-directory' ),
            'view_items'            => __( 'Hofläden ansehen', 'spezialist-directory' ),
            'search_items'          => __( 'Hofladen suchen', 'spezialist-directory' ),
            'not_found'             => __( 'Nicht gefunden', 'spezialist-directory' ),
            'not_found_in_trash'    => __( 'Nicht im Papierkorb gefunden', 'spezialist-directory' ),
            'featured_image'        => __( 'Profilbild', 'spezialist-directory' ),
            'set_featured_image'    => __( 'Profilbild festlegen', 'spezialist-directory' ),
            'remove_featured_image' => __( 'Profilbild entfernen', 'spezialist-directory' ),
            'use_featured_image'    => __( 'Als Profilbild verwenden', 'spezialist-directory' ),
            'insert_into_item'      => __( 'In Hofladen einfügen', 'spezialist-directory' ),
            'uploaded_to_this_item' => __( 'Hochgeladen zu diesem Hofladen', 'spezialist-directory' ),
            'items_list'            => __( 'Hofläden Liste', 'spezialist-directory' ),
            'items_list_navigation' => __( 'Hofläden Listen Navigation', 'spezialist-directory' ),
            'filter_items_list'     => __( 'Hofläden Liste filtern', 'spezialist-directory' ),
        );

        $args = array(
            'label'                 => __( 'Hofladen', 'spezialist-directory' ),
            'description'           => __( 'Hofladen Einträge', 'spezialist-directory' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'author', 'revisions' ),
            'taxonomies'            => array( 'spezialist_category', 'spezialist_location', 'spezialist_tag' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-businessman',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug'       => 'spezialist',
                'with_front' => false,
            ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'spezialist_listings', array( $this, 'render_listings_shortcode' ) );
        add_shortcode( 'spezialist_detail', array( $this, 'render_detail_shortcode' ) );
    }

    /**
     * Render listings shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_listings_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'per_page'  => Spezialist_Directory::get_option( 'listings_per_page', 12 ),
            'category'  => '',
            'location'  => '',
            'premium'   => '',
            'orderby'   => 'date',
            'order'     => 'DESC',
        ), $atts, 'spezialist_listings' );

        ob_start();

        // Get current page for pagination
        // On static front page, WordPress uses 'page' query var instead of 'paged'
        // Also check $_GET['paged'] for query string pagination
        if ( isset( $_GET['paged'] ) && absint( $_GET['paged'] ) > 0 ) {
            $paged = absint( $_GET['paged'] );
        } elseif ( get_query_var( 'page' ) ) {
            $paged = absint( get_query_var( 'page' ) );
        } elseif ( get_query_var( 'paged' ) ) {
            $paged = absint( get_query_var( 'paged' ) );
        } else {
            $paged = 1;
        }

        // Build query args
        $query_args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => intval( $atts['per_page'] ),
            'paged'          => $paged,
            'post_status'    => 'publish',
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        // Handle premium sorting (premium first)
        if ( 'premium' === $query_args['orderby'] ) {
            $query_args['meta_key'] = '_sd_is_premium';
            $query_args['orderby'] = 'meta_value_num date';
            $query_args['order'] = 'DESC';
        }

        // Tax query
        $tax_query = array();

        if ( ! empty( $atts['category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'spezialist_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            );
        }

        if ( ! empty( $atts['location'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'spezialist_location',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['location'] ),
            );
        }

        // Check for GET parameters (filter form submissions)
        if ( isset( $_GET['sd_category'] ) && ! empty( $_GET['sd_category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'spezialist_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['sd_category'] ),
            );
        }

        // Location filter uses spezialist_location taxonomy (includes child terms)
        if ( isset( $_GET['sd_location'] ) && ! empty( $_GET['sd_location'] ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'spezialist_location',
                'field'            => 'slug',
                'terms'            => sanitize_text_field( $_GET['sd_location'] ),
                'include_children' => true, // Include child terms (Stadtteile under Bezirke)
            );
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        // Search query
        if ( isset( $_GET['sd_search'] ) && ! empty( $_GET['sd_search'] ) ) {
            $query_args['s'] = sanitize_text_field( $_GET['sd_search'] );
        }

        // Premium filter
        if ( ! empty( $atts['premium'] ) || ( isset( $_GET['sd_premium'] ) && '1' === $_GET['sd_premium'] ) ) {
            if ( ! isset( $query_args['meta_query'] ) ) {
                $query_args['meta_query'] = array();
            }
            $query_args['meta_query'][] = array(
                'key'     => '_sd_is_premium',
                'value'   => '1',
                'compare' => '=',
            );
        }

        // Sorting from GET parameter
        if ( isset( $_GET['sd_orderby'] ) && ! empty( $_GET['sd_orderby'] ) ) {
            $orderby = sanitize_text_field( $_GET['sd_orderby'] );

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
                    unset( $query_args['order'] );
                    break;
            }
        }

        // Run query
        $listings = new WP_Query( $query_args );

        // Load template
        include SD_PLUGIN_DIR . 'templates/listing-grid.php';

        return ob_get_clean();
    }

    /**
     * Render detail shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_detail_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => get_the_ID(),
        ), $atts, 'spezialist_detail' );

        $post_id = intval( $atts['id'] );

        if ( ! $post_id || self::POST_TYPE !== get_post_type( $post_id ) ) {
            return '<p>' . __( 'Hofladen nicht gefunden.', 'spezialist-directory' ) . '</p>';
        }

        ob_start();

        global $post;
        $post = get_post( $post_id );
        setup_postdata( $post );

        include SD_PLUGIN_DIR . 'templates/listing-detail.php';

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Load custom single template
     *
     * @param string $template
     * @return string
     */
    public function load_single_template( $template ) {
        // Single spezialist template
        if ( is_singular( self::POST_TYPE ) ) {
            $custom_template = SD_PLUGIN_DIR . 'templates/single-spezialist.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        // Taxonomy archive templates
        if ( is_tax( 'spezialist_category' ) || is_tax( 'spezialist_location' ) || is_tax( 'spezialist_tag' ) ) {
            $custom_template = SD_PLUGIN_DIR . 'templates/taxonomy-archive.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Custom permalink structure
     *
     * @param string $post_link
     * @param WP_Post $post
     * @return string
     */
    public function custom_permalink( $post_link, $post ) {
        if ( self::POST_TYPE === $post->post_type && 'publish' === $post->post_status ) {
            $permalink_structure = get_option( 'permalink_structure' );

            // Check if permalink structure includes index.php
            if ( strpos( $permalink_structure, 'index.php' ) !== false ) {
                $post_link = home_url( '/index.php/spezialist/' . $post->post_name . '/' );
            } else {
                $post_link = home_url( '/spezialist/' . $post->post_name . '/' );
            }
        }
        return $post_link;
    }

    /**
     * Get similar listings based on category and location
     *
     * @param int $post_id The current listing ID
     * @param int $limit Number of similar listings to return (default 4)
     * @return array Array of WP_Post objects
     */
    public static function get_similar_listings( $post_id, $limit = 4 ) {
        // Get the current listing's categories and location
        $categories = wp_get_object_terms( $post_id, 'spezialist_category', array( 'fields' => 'ids' ) );
        $locations = wp_get_object_terms( $post_id, 'spezialist_location', array( 'fields' => 'ids' ) );
        $neighborhood = get_post_meta( $post_id, '_sd_neighborhood', true );

        // If no categories, return empty
        if ( empty( $categories ) || is_wp_error( $categories ) ) {
            return array();
        }

        // Build query for similar listings
        $args = array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'post__not_in'   => array( $post_id ),
            'meta_query'     => array(
                // Exclude paused listings
                array(
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
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'spezialist_category',
                    'field'    => 'term_id',
                    'terms'    => $categories,
                ),
            ),
            'orderby'        => 'rand',
        );

        // Try to find listings in same category AND location first
        if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) {
            $args_with_location = $args;
            $args_with_location['tax_query'][] = array(
                'taxonomy' => 'spezialist_location',
                'field'    => 'term_id',
                'terms'    => $locations,
            );

            $query = new WP_Query( $args_with_location );

            if ( $query->have_posts() && $query->found_posts >= $limit ) {
                return $query->posts;
            }

            // If not enough results, collect what we have and fill with category-only matches
            $found_ids = wp_list_pluck( $query->posts, 'ID' );
            $results = $query->posts;

            if ( count( $results ) < $limit ) {
                // Get more listings from same category (without location filter)
                $args['posts_per_page'] = $limit - count( $results );
                $args['post__not_in'] = array_merge( array( $post_id ), $found_ids );

                $additional_query = new WP_Query( $args );
                $results = array_merge( $results, $additional_query->posts );
            }

            return $results;
        }

        // Fallback: just category matching
        $query = new WP_Query( $args );
        return $query->posts;
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

<?php
/**
 * SEO Meta Tags
 *
 * Handles meta descriptions, robots directives, and canonical URLs
 *
 * @package Spezialist_SEO
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SDSEO_Meta_Tags Class
 */
class SDSEO_Meta_Tags {

    /**
     * Single instance
     *
     * @var SDSEO_Meta_Tags
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SDSEO_Meta_Tags
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
        // Meta tags (priority 1 - early in head)
        add_action( 'wp_head', array( $this, 'output_meta_description' ), 1 );
        add_action( 'wp_head', array( $this, 'output_meta_robots' ), 1 );
        add_action( 'wp_head', array( $this, 'output_geo_meta' ), 2 );

        // Canonical URL (priority 10)
        add_action( 'wp_head', array( $this, 'output_canonical_url' ), 10 );

        // Pagination links for archives (priority 3)
        add_action( 'wp_head', array( $this, 'output_pagination_links' ), 3 );

        // Remove WordPress default canonical
        remove_action( 'wp_head', 'rel_canonical' );

        // Document title filters
        add_filter( 'pre_get_document_title', array( $this, 'custom_document_title' ), 10 );
        add_filter( 'document_title_separator', array( $this, 'title_separator' ), 10 );
    }

    /**
     * Custom document title separator
     *
     * @return string
     */
    public function title_separator() {
        return '|';
    }

    /**
     * Custom document title
     *
     * @param string $title
     * @return string
     */
    public function custom_document_title( $title ) {
        $site_name = get_bloginfo( 'name' );

        // Single specialist page
        if ( is_singular( 'spezialist' ) ) {
            $post_id = get_the_ID();
            $name = get_the_title();
            $city = get_post_meta( $post_id, '_sd_city', true );
            $categories = Spezialist_SEO::get_specialist_categories( $post_id );
            $category = ! empty( $categories ) ? $categories[0] : '';

            $parts = array( $name );
            if ( $category ) {
                $parts[] = $category;
            }
            if ( $city ) {
                $parts[] = $city;
            }
            $parts[] = $site_name;

            return implode( ' | ', $parts );
        }

        // Homepage / Directory page
        if ( is_front_page() || Spezialist_SEO::is_directory_page() ) {
            // Check for active filters
            $category_filter = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
            $location_filter = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';
            $search_query = isset( $_GET['sd_search'] ) ? sanitize_text_field( $_GET['sd_search'] ) : '';

            if ( $search_query ) {
                return sprintf( 'Suche: %s | %s', $search_query, $site_name );
            }

            if ( $category_filter && $location_filter ) {
                return sprintf( '%s in %s | %s', $category_filter, $location_filter, $site_name );
            }

            if ( $category_filter ) {
                return sprintf( '%s - Spezialisten | %s', $category_filter, $site_name );
            }

            if ( $location_filter ) {
                return sprintf( 'Spezialisten in %s | %s', $location_filter, $site_name );
            }

            return sprintf( 'Spezialisten-Verzeichnis Nürnberg | %s', $site_name );
        }

        // Category archive
        if ( is_tax( 'spezialist_category' ) ) {
            $term = get_queried_object();
            return sprintf( '%s - Spezialisten | %s', $term->name, $site_name );
        }

        // Location archive
        if ( is_tax( 'spezialist_location' ) ) {
            $term = get_queried_object();
            return sprintf( 'Spezialisten in %s | %s', $term->name, $site_name );
        }

        // Regular WordPress pages
        if ( is_page() ) {
            return get_the_title() . ' | ' . $site_name;
        }

        return $title;
    }

    /**
     * Output meta description
     */
    public function output_meta_description() {
        $description = $this->get_meta_description();

        if ( $description ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
        }
    }

    /**
     * Get meta description for current page
     *
     * @return string
     */
    private function get_meta_description() {
        // Single specialist page
        if ( is_singular( 'spezialist' ) ) {
            return $this->get_specialist_description();
        }

        // Homepage / Directory page
        if ( is_front_page() || Spezialist_SEO::is_directory_page() ) {
            return $this->get_directory_description();
        }

        // Category archive
        if ( is_tax( 'spezialist_category' ) ) {
            $term = get_queried_object();
            $count = $term->count;
            return sprintf(
                'Finden Sie %d qualifizierte %s in Deutschland. Vergleichen Sie Profile, lesen Sie Bewertungen und kontaktieren Sie den passenden Spezialisten.',
                $count,
                $term->name
            );
        }

        // Location archive
        if ( is_tax( 'spezialist_location' ) ) {
            $term = get_queried_object();
            $count = $term->count;
            return sprintf(
                '%d geprüfte Spezialisten in %s. Finden Sie lokale Experten für Ihre Anforderungen. Kostenlose Kontaktaufnahme.',
                $count,
                $term->name
            );
        }

        // Regular WordPress pages
        if ( is_page() ) {
            $excerpt = get_the_excerpt();
            if ( $excerpt ) {
                return Spezialist_SEO::truncate_text( wp_strip_all_tags( $excerpt ), 155 );
            }
            // Fallback: use page title
            return sprintf( '%s - %s', get_the_title(), get_bloginfo( 'name' ) );
        }

        return '';
    }

    /**
     * Get description for single specialist
     *
     * @return string
     */
    private function get_specialist_description() {
        $post_id = get_the_ID();
        $name = get_the_title();
        $city = get_post_meta( $post_id, '_sd_city', true );
        $phone = get_post_meta( $post_id, '_sd_phone', true );
        $categories = Spezialist_SEO::get_specialist_categories( $post_id );
        $category = ! empty( $categories ) ? $categories[0] : '';

        // Build description parts
        $parts = array();

        if ( $category && $city ) {
            $parts[] = sprintf( '%s in %s', $category, $city );
        } elseif ( $category ) {
            $parts[] = $category;
        } elseif ( $city ) {
            $parts[] = sprintf( 'Spezialist in %s', $city );
        }

        // Add excerpt or content snippet
        $excerpt = get_the_excerpt();
        if ( $excerpt ) {
            $parts[] = Spezialist_SEO::truncate_text( $excerpt, 100, '' );
        }

        // Add contact hint
        if ( $phone ) {
            $parts[] = 'Jetzt kontaktieren';
        }

        $description = implode( '. ', array_filter( $parts ) );

        return Spezialist_SEO::truncate_text( $description, 155 );
    }

    /**
     * Get description for directory page
     *
     * @return string
     */
    private function get_directory_description() {
        // Check for active filters
        $category_filter = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
        $location_filter = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';
        $search_query = isset( $_GET['sd_search'] ) ? sanitize_text_field( $_GET['sd_search'] ) : '';

        if ( $search_query ) {
            return sprintf(
                'Suchergebnisse für "%s" im Spezialisten-Verzeichnis. Finden Sie qualifizierte Experten in Deutschland.',
                $search_query
            );
        }

        if ( $category_filter && $location_filter ) {
            return sprintf(
                'Finden Sie qualifizierte %s in %s. Vergleichen Sie Profile und kontaktieren Sie den passenden Spezialisten.',
                $category_filter,
                $location_filter
            );
        }

        if ( $category_filter ) {
            return sprintf(
                '%s in ganz Deutschland finden. Durchsuchen Sie unser Verzeichnis nach Standort und Expertise.',
                $category_filter
            );
        }

        if ( $location_filter ) {
            return sprintf(
                'Spezialisten in %s - Finden Sie lokale Experten für verschiedene Fachgebiete. Kostenlose Kontaktaufnahme.',
                $location_filter
            );
        }

        // Default homepage description
        return 'Finden Sie qualifizierte Spezialisten in Deutschland. Durchsuchen Sie unser Verzeichnis nach Kategorie oder Standort. Kostenlose Kontaktaufnahme.';
    }

    /**
     * Output meta robots
     */
    public function output_meta_robots() {
        $robots = 'index, follow';

        if ( Spezialist_SEO::should_noindex() ) {
            $robots = 'noindex, follow';
        }

        echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
    }

    /**
     * Output geo meta tags for local SEO
     */
    public function output_geo_meta() {
        if ( ! is_singular( 'spezialist' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $city = get_post_meta( $post_id, '_sd_city', true );
        $latitude = get_post_meta( $post_id, '_sd_latitude', true );
        $longitude = get_post_meta( $post_id, '_sd_longitude', true );

        if ( $city ) {
            echo '<meta name="geo.placename" content="' . esc_attr( $city ) . ', Deutschland">' . "\n";
            echo '<meta name="geo.region" content="DE">' . "\n";
        }

        if ( $latitude && $longitude ) {
            echo '<meta name="geo.position" content="' . esc_attr( $latitude ) . ';' . esc_attr( $longitude ) . '">' . "\n";
            echo '<meta name="ICBM" content="' . esc_attr( $latitude ) . ', ' . esc_attr( $longitude ) . '">' . "\n";
        }
    }

    /**
     * Output canonical URL
     */
    public function output_canonical_url() {
        $canonical = '';

        // Single specialist
        if ( is_singular( 'spezialist' ) ) {
            $canonical = get_permalink();
        }

        // Homepage / Front page
        elseif ( is_front_page() ) {
            // Strip query parameters for canonical
            $canonical = home_url( '/' );
        }

        // Category archive
        elseif ( is_tax( 'spezialist_category' ) ) {
            $term = get_queried_object();
            $canonical = get_term_link( $term );
        }

        // Location archive
        elseif ( is_tax( 'spezialist_location' ) ) {
            $term = get_queried_object();
            $canonical = get_term_link( $term );
        }

        // Regular pages
        elseif ( is_singular() ) {
            $canonical = get_permalink();
        }

        if ( $canonical && ! is_wp_error( $canonical ) ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
        }
    }

    /**
     * Output rel="prev" and rel="next" for paginated archives
     *
     * Helps search engines understand pagination structure
     */
    public function output_pagination_links() {
        // Only for taxonomy archives
        if ( ! is_tax( 'spezialist_category' ) && ! is_tax( 'spezialist_location' ) && ! is_tax( 'spezialist_tag' ) ) {
            return;
        }

        global $wp_query;
        $paged = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
        $max_page = $wp_query->max_num_pages;

        // No pagination needed for single page archives
        if ( $max_page <= 1 ) {
            return;
        }

        $term = get_queried_object();
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        // rel="prev"
        if ( $paged > 1 ) {
            $prev_page = $paged - 1;
            if ( $prev_page === 1 ) {
                // First page has no /page/ in URL
                $prev_url = get_term_link( $term );
            } else {
                $prev_url = get_pagenum_link( $prev_page );
            }

            if ( $prev_url && ! is_wp_error( $prev_url ) ) {
                echo '<link rel="prev" href="' . esc_url( $prev_url ) . '">' . "\n";
            }
        }

        // rel="next"
        if ( $paged < $max_page ) {
            $next_url = get_pagenum_link( $paged + 1 );
            if ( $next_url ) {
                echo '<link rel="next" href="' . esc_url( $next_url ) . '">' . "\n";
            }
        }
    }
}

<?php
/**
 * SEO Open Graph Tags
 *
 * Handles Facebook and LinkedIn Open Graph meta tags
 *
 * @package Spezialist_SEO
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SDSEO_Open_Graph Class
 */
class SDSEO_Open_Graph {

    /**
     * Single instance
     *
     * @var SDSEO_Open_Graph
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SDSEO_Open_Graph
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
        add_action( 'wp_head', array( $this, 'output_open_graph' ), 5 );
    }

    /**
     * Output Open Graph tags
     */
    public function output_open_graph() {
        // Single specialist page
        if ( is_singular( 'hofladen' ) ) {
            $this->output_specialist_og();
            return;
        }

        // Homepage / Directory page
        if ( is_front_page() || Spezialist_SEO::is_directory_page() ) {
            $this->output_directory_og();
            return;
        }

        // Category archive
        if ( is_tax( 'spezialist_category' ) ) {
            $this->output_category_og();
            return;
        }

        // Location archive
        if ( is_tax( 'spezialist_location' ) ) {
            $this->output_location_og();
            return;
        }

        // Regular WordPress pages (Kontakt, Impressum, etc.)
        if ( is_page() ) {
            $this->output_page_og();
            return;
        }
    }

    /**
     * Output OG tags for single specialist
     */
    private function output_specialist_og() {
        $post_id = get_the_ID();
        $name = get_the_title();
        $city = get_post_meta( $post_id, '_sd_city', true );
        $address = get_post_meta( $post_id, '_sd_address', true );
        $zip = get_post_meta( $post_id, '_sd_zip', true );
        $phone = get_post_meta( $post_id, '_sd_phone', true );
        $categories = Spezialist_SEO::get_specialist_categories( $post_id );
        $category = ! empty( $categories ) ? $categories[0] : '';

        // Build title
        $og_title = $name;
        if ( $category ) {
            $og_title .= ' | ' . $category;
        }
        if ( $city ) {
            $og_title .= ' in ' . $city;
        }

        // Build description
        $og_description = $this->get_specialist_og_description( $post_id, $category, $city );

        // Get image
        $og_image = $this->get_og_image( $post_id );

        // Output base OG tags
        echo '<meta property="og:type" content="business.business">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
        echo '<meta property="og:locale" content="de_DE">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

        // Business contact data (Facebook business extension)
        if ( $address ) {
            echo '<meta property="business:contact_data:street_address" content="' . esc_attr( $address ) . '">' . "\n";
        }
        if ( $city ) {
            echo '<meta property="business:contact_data:locality" content="' . esc_attr( $city ) . '">' . "\n";
        }
        if ( $zip ) {
            echo '<meta property="business:contact_data:postal_code" content="' . esc_attr( $zip ) . '">' . "\n";
        }
        echo '<meta property="business:contact_data:country_name" content="Germany">' . "\n";
        if ( $phone ) {
            echo '<meta property="business:contact_data:phone_number" content="' . esc_attr( $phone ) . '">' . "\n";
        }

        // Profile meta for person-like businesses
        echo '<meta property="profile:username" content="' . esc_attr( sanitize_title( $name ) ) . '">' . "\n";
    }

    /**
     * Get OG description for specialist
     *
     * @param int $post_id
     * @param string $category
     * @param string $city
     * @return string
     */
    private function get_specialist_og_description( $post_id, $category, $city ) {
        $parts = array();

        if ( $category && $city ) {
            $parts[] = sprintf( '%s in %s', $category, $city );
        } elseif ( $category ) {
            $parts[] = $category;
        } elseif ( $city ) {
            $parts[] = sprintf( 'Spezialist in %s', $city );
        }

        $excerpt = get_the_excerpt( $post_id );
        if ( $excerpt ) {
            $parts[] = Spezialist_SEO::truncate_text( $excerpt, 100, '' );
        }

        $description = implode( '. ', array_filter( $parts ) );

        return Spezialist_SEO::truncate_text( $description, 200 );
    }

    /**
     * Output OG tags for directory page
     */
    private function output_directory_og() {
        $site_name = get_bloginfo( 'name' );

        // Check for active filters
        $category_filter = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
        $location_filter = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';

        // Build title
        $og_title = $site_name . ' | Deutschland';
        if ( $category_filter && $location_filter ) {
            $og_title = sprintf( '%s in %s | %s', $category_filter, $location_filter, $site_name );
        } elseif ( $category_filter ) {
            $og_title = sprintf( '%s | %s', $category_filter, $site_name );
        } elseif ( $location_filter ) {
            $og_title = sprintf( 'Hofläden in %s | %s', $location_filter, $site_name );
        }

        // Build description
        $og_description = 'Finden Sie Hofläden und regionale Erzeuger in Deutschland. Durchsuchen Sie unser Verzeichnis nach Kategorie oder Standort.';
        if ( $category_filter && $location_filter ) {
            $og_description = sprintf( 'Finden Sie %s in %s. Vergleichen Sie Profile und kontaktieren Sie Anbieter.', $category_filter, $location_filter );
        } elseif ( $category_filter ) {
            $og_description = sprintf( '%s in ganz Deutschland finden. Kostenlose Kontaktaufnahme.', $category_filter );
        } elseif ( $location_filter ) {
            $og_description = sprintf( 'Alle Hofläden in %s auf einen Blick. Finden Sie lokale Anbieter.', $location_filter );
        }

        // Get logo as image (allow filtering for OG Screenshots plugin)
        $og_image = apply_filters( 'spezialist_seo_og_image', SDSEO()->get_placeholder_image(), null );

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:locale" content="de_DE">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    }

    /**
     * Output OG tags for category archive
     */
    private function output_category_og() {
        $term = get_queried_object();
        $site_name = get_bloginfo( 'name' );

        $og_title = sprintf( '%s - Spezialisten finden', $term->name );
        $og_description = sprintf(
            'Finden Sie %d qualifizierte %s in Deutschland. Vergleichen Sie Profile und kontaktieren Sie Experten.',
            $term->count,
            $term->name
        );

        // Allow filtering for OG Screenshots plugin
        $og_image = apply_filters( 'spezialist_seo_og_image', SDSEO()->get_placeholder_image(), $term );

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . ' | ' . esc_attr( $site_name ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_term_link( $term ) ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:locale" content="de_DE">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    }

    /**
     * Output OG tags for location archive
     */
    private function output_location_og() {
        $term = get_queried_object();
        $site_name = get_bloginfo( 'name' );

        $og_title = sprintf( 'Spezialisten in %s', $term->name );
        $og_description = sprintf(
            '%d geprüfte Spezialisten in %s. Finden Sie lokale Experten für verschiedene Fachgebiete.',
            $term->count,
            $term->name
        );

        // Allow filtering for OG Screenshots plugin
        $og_image = apply_filters( 'spezialist_seo_og_image', SDSEO()->get_placeholder_image(), $term );

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . ' | ' . esc_attr( $site_name ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_term_link( $term ) ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:locale" content="de_DE">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    }

    /**
     * Output OG tags for regular WordPress pages
     */
    private function output_page_og() {
        $post_id = get_the_ID();
        $site_name = get_bloginfo( 'name' );

        $og_title = get_the_title() . ' | ' . $site_name;
        $og_description = get_the_excerpt( $post_id );
        if ( empty( $og_description ) ) {
            $og_description = $site_name;
        }
        $og_description = Spezialist_SEO::truncate_text( wp_strip_all_tags( $og_description ), 200 );

        // Allow filtering for OG Screenshots plugin
        $og_image = apply_filters( 'spezialist_seo_og_image', SDSEO()->get_placeholder_image(), $post_id );

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:locale" content="de_DE">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    }

    /**
     * Get OG image URL
     *
     * @param int $post_id
     * @return string
     */
    private function get_og_image( $post_id ) {
        // Allow filtering of OG image (for OG Screenshots plugin)
        $og_screenshot = apply_filters( 'spezialist_seo_og_image', '', $post_id );
        if ( ! empty( $og_screenshot ) ) {
            return $og_screenshot;
        }

        // Try featured image first
        if ( has_post_thumbnail( $post_id ) ) {
            $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
            if ( $image_url ) {
                return $image_url;
            }
        }

        // Fall back to placeholder
        return SDSEO()->get_placeholder_image();
    }
}

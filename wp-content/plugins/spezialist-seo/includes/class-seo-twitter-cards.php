<?php
/**
 * SEO Twitter Cards
 *
 * Handles Twitter Card meta tags for rich sharing previews
 *
 * @package Spezialist_SEO
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SDSEO_Twitter_Cards Class
 */
class SDSEO_Twitter_Cards {

    /**
     * Single instance
     *
     * @var SDSEO_Twitter_Cards
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SDSEO_Twitter_Cards
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
        add_action( 'wp_head', array( $this, 'output_twitter_cards' ), 6 );
    }

    /**
     * Output Twitter Card tags
     */
    public function output_twitter_cards() {
        // Single specialist page
        if ( is_singular( 'hofladen' ) ) {
            $this->output_specialist_twitter();
            return;
        }

        // Homepage / Directory page
        if ( is_front_page() || Spezialist_SEO::is_directory_page() ) {
            $this->output_directory_twitter();
            return;
        }

        // Category archive
        if ( is_tax( 'spezialist_category' ) ) {
            $this->output_category_twitter();
            return;
        }

        // Location archive
        if ( is_tax( 'spezialist_location' ) ) {
            $this->output_location_twitter();
            return;
        }

        // Regular WordPress pages (Kontakt, Impressum, etc.)
        if ( is_page() ) {
            $this->output_page_twitter();
            return;
        }
    }

    /**
     * Output Twitter Card for single specialist
     */
    private function output_specialist_twitter() {
        $post_id = get_the_ID();
        $name = get_the_title();
        $city = get_post_meta( $post_id, '_sd_city', true );
        $categories = Spezialist_SEO::get_specialist_categories( $post_id );
        $category = ! empty( $categories ) ? $categories[0] : '';

        // Build title
        $twitter_title = $name;
        if ( $category ) {
            $twitter_title .= ' | ' . $category;
        }
        if ( $city ) {
            $twitter_title .= ' in ' . $city;
        }

        // Build description
        $twitter_description = $this->get_specialist_twitter_description( $post_id, $category, $city );

        // Get image
        $twitter_image = $this->get_twitter_image( $post_id );

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
        echo '<meta name="twitter:image:alt" content="' . esc_attr( sprintf( 'Profil von %s', $name ) ) . '">' . "\n";
    }

    /**
     * Get Twitter description for specialist
     *
     * @param int $post_id
     * @param string $category
     * @param string $city
     * @return string
     */
    private function get_specialist_twitter_description( $post_id, $category, $city ) {
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
            $parts[] = Spezialist_SEO::truncate_text( $excerpt, 80, '' );
        }

        $description = implode( '. ', array_filter( $parts ) );

        // Twitter has a 200 char limit for descriptions
        return Spezialist_SEO::truncate_text( $description, 200 );
    }

    /**
     * Output Twitter Card for directory page
     */
    private function output_directory_twitter() {
        $site_name = get_bloginfo( 'name' );

        // Check for active filters
        $category_filter = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
        $location_filter = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';

        // Build title
        $twitter_title = $site_name . ' | Deutschland';
        if ( $category_filter && $location_filter ) {
            $twitter_title = sprintf( '%s in %s | %s', $category_filter, $location_filter, $site_name );
        } elseif ( $category_filter ) {
            $twitter_title = sprintf( '%s | %s', $category_filter, $site_name );
        } elseif ( $location_filter ) {
            $twitter_title = sprintf( 'Hofläden in %s | %s', $location_filter, $site_name );
        }

        // Build description
        $twitter_description = 'Finden Sie Hofläden und regionale Erzeuger in Deutschland. Durchsuchen Sie unser Verzeichnis nach Kategorie oder Standort.';

        // Allow filtering for OG Screenshots plugin
        $twitter_image = apply_filters( 'spezialist_seo_twitter_image', SDSEO()->get_placeholder_image(), null );

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
        echo '<meta name="twitter:image:alt" content="Spezialist-Für.de Verzeichnis">' . "\n";
    }

    /**
     * Output Twitter Card for category archive
     */
    private function output_category_twitter() {
        $term = get_queried_object();
        $site_name = get_bloginfo( 'name' );

        $twitter_title = sprintf( '%s - Spezialisten finden | %s', $term->name, $site_name );
        $twitter_description = sprintf(
            'Finden Sie %d qualifizierte %s in Deutschland. Vergleichen Sie Profile und kontaktieren Sie Experten.',
            $term->count,
            $term->name
        );

        // Allow filtering for OG Screenshots plugin
        $twitter_image = apply_filters( 'spezialist_seo_twitter_image', SDSEO()->get_placeholder_image(), $term );

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
    }

    /**
     * Output Twitter Card for location archive
     */
    private function output_location_twitter() {
        $term = get_queried_object();
        $site_name = get_bloginfo( 'name' );

        $twitter_title = sprintf( 'Spezialisten in %s | %s', $term->name, $site_name );
        $twitter_description = sprintf(
            '%d geprüfte Spezialisten in %s. Finden Sie lokale Experten für verschiedene Fachgebiete.',
            $term->count,
            $term->name
        );

        // Allow filtering for OG Screenshots plugin
        $twitter_image = apply_filters( 'spezialist_seo_twitter_image', SDSEO()->get_placeholder_image(), $term );

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
    }

    /**
     * Output Twitter Card for regular WordPress pages
     */
    private function output_page_twitter() {
        $post_id = get_the_ID();
        $site_name = get_bloginfo( 'name' );

        $twitter_title = get_the_title() . ' | ' . $site_name;
        $twitter_description = get_the_excerpt( $post_id );
        if ( empty( $twitter_description ) ) {
            $twitter_description = $site_name;
        }
        $twitter_description = Spezialist_SEO::truncate_text( wp_strip_all_tags( $twitter_description ), 200 );

        // Allow filtering for OG Screenshots plugin
        $twitter_image = apply_filters( 'spezialist_seo_twitter_image', SDSEO()->get_placeholder_image(), $post_id );

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
    }

    /**
     * Get Twitter Card image URL
     *
     * @param int $post_id
     * @return string
     */
    private function get_twitter_image( $post_id ) {
        // Allow filtering of Twitter image (for OG Screenshots plugin)
        $twitter_screenshot = apply_filters( 'spezialist_seo_twitter_image', '', $post_id );
        if ( ! empty( $twitter_screenshot ) ) {
            return $twitter_screenshot;
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
